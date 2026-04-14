<?php

namespace App\Http\Controllers\Api\Contract;

use App\Http\Controllers\Api\ApiController;
use App\Mail\ContractAssignmentMail;
use App\Models\Contract\Contract;
use App\Models\Contract\ContractAuditLog;
use App\Models\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ContractController extends ApiController
{
    private const ALLOWED_STATUSES = [
        'draft',
        'active',
        'inactive',
        'terminated',
        'pending',
        'viewed',
        'signed',
        'expired',
        'rejected',
    ];

    /**
     * Display a listing of contracts
     */
    public function index(Request $request)
    {
        $this->expireDueContracts();
        $query = Contract::query();

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $contracts = $query->with('employee', 'contractType')->paginate($request->get('per_page', 15));
        return $this->successResponse($contracts, 'Contracts retrieved successfully');
    }

    /**
     * Store a newly created contract
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'contract_type_id' => 'nullable|exists:contract_types,id',
            'contract_name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'contract_document_path' => 'nullable|string',
            'status' => 'sometimes|string|in:'.implode(',', self::ALLOWED_STATUSES),
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $contract = Contract::create([
            'employee_id' => $request->employee_id,
            'contract_type_id' => $request->contract_type_id,
            'contract_name' => $request->contract_name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'contract_document_path' => $request->contract_document_path,
            'status' => $request->input('status', 'draft'),
            'notes' => $request->notes,
        ]);

        return $this->successResponse($contract->load('employee', 'contractType'), 'Contract created successfully', 201);
    }

    /**
     * Display the specified contract
     */
    public function show($id)
    {
        $this->expireDueContracts();
        $contract = Contract::with('employee.department', 'employee.designation', 'contractType', 'attachments', 'comments', 'notes')->findOrFail($id);
        return $this->successResponse($contract, 'Contract retrieved successfully');
    }

    /**
     * Update the specified contract
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'contract_name' => 'sometimes|string|max:255',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|nullable|date|after_or_equal:start_date',
            'contract_document_path' => 'sometimes|nullable|string',
            'status' => 'sometimes|string|in:'.implode(',', self::ALLOWED_STATUSES),
            'notes' => 'sometimes|nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $this->expireDueContracts();
        $contract = Contract::findOrFail($id);
        $contract->update($validator->validated());

        return $this->successResponse($contract->load('employee', 'contractType'), 'Contract updated successfully');
    }

    /**
     * Remove the specified contract
     */
    public function destroy($id)
    {
        $contract = Contract::findOrFail($id);
        $contract->delete();

        return $this->successResponse(null, 'Contract deleted successfully');
    }

    public function assign(Request $request, $id)
    {
        $this->expireDueContracts();
        $contract = Contract::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'signing_deadline' => 'sometimes|nullable|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        if (in_array($contract->status, ['signed', 'rejected'], true)) {
            return $this->errorResponse('This contract can no longer be assigned in its current state.', 422);
        }

        $signingDeadline = $request->filled('signing_deadline')
            ? Carbon::parse($request->input('signing_deadline'))->endOfDay()
            : now()->addHours(72);
        $tokenExpiresAt = now()->addHours(72)->min($signingDeadline);
        $verificationCode = $this->generateVerificationCode();
        $token = bin2hex(random_bytes(32));

        $contract->update([
            'status' => 'pending',
            'verification_token' => $token,
            'verification_code' => $verificationCode,
            'token_expires_at' => $tokenExpiresAt,
            'signing_deadline' => $signingDeadline,
        ]);

        $this->recordContractAudit($contract, 'assigned', [
            'token_expires_at' => $contract->token_expires_at?->toIso8601String(),
            'signing_deadline' => $contract->signing_deadline?->toIso8601String(),
        ]);

        $contract->loadMissing('employee');

        $emailSent = false;
        if ($contract->employee?->email) {
            try {
                Mail::to($contract->employee->email)->send(
                    new ContractAssignmentMail($contract->fresh('employee'))
                );
                $emailSent = true;
            } catch (\Throwable $exception) {
                $emailSent = false;
            }
        }

        if ($contract->employee?->user_id) {
            Notification::firstOrCreate(
                ['dedup_key' => 'contract_assigned_' . $contract->id],
                [
                    'type' => 'contract_assigned',
                    'payload' => [
                        'title' => 'Contract assigned',
                        'message' => "A contract is ready for your review. Use verification code {$verificationCode} before {$signingDeadline->toFormattedDateString()}.",
                        'action' => '/contract-review',
                    ],
                    'target_user_ids' => [$contract->employee->user_id],
                    'channel' => 'in_app',
                ]
            );
        }

        return $this->successResponse([
            'contract' => $contract->fresh('employee', 'contractType'),
            'email_sent' => $emailSent,
        ], 'Contract assigned successfully');
    }

    public function markViewed(Request $request, $id)
    {
        $request->validate([
            'token' => 'nullable|string|required_without:verification_code',
            'verification_code' => 'nullable|string|required_without:token',
        ]);

        $this->expireDueContracts();
        $contract = $this->resolveVerificationContract(
            $id,
            $request->input('token'),
            $request->input('verification_code')
        );

        if (in_array($contract->status, ['signed', 'rejected'], true)) {
            return $this->errorResponse('This contract can no longer be reviewed in its current state.', 422);
        }

        if ($this->isExpired($contract)) {
            return $this->errorResponse('The contract review window has expired.', 422);
        }

        $contract->update([
            'status' => 'viewed',
            'viewed_at' => now(),
        ]);

        $this->recordContractAudit($contract, 'viewed', [
            'token' => $request->input('token'),
            'verification_code' => $request->input('verification_code'),
        ]);

        return $this->successResponse($contract->fresh(), 'Contract marked as viewed');
    }

    public function signWithToken(Request $request)
    {
        $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'token' => 'nullable|string|required_without:verification_code',
            'verification_code' => 'nullable|string|required_without:token',
        ]);

        $this->expireDueContracts();
        $contract = $this->resolveVerificationContract(
            (int) $request->contract_id,
            $request->input('token'),
            $request->input('verification_code')
        );

        if (in_array($contract->status, ['signed', 'rejected'], true)) {
            return $this->errorResponse('This contract can no longer be signed in its current state.', 422);
        }

        if ($this->isExpired($contract)) {
            return $this->errorResponse('The verification code has expired.', 422);
        }

        $contract->update([
            'status' => 'signed',
            'signed_at' => now(),
            'signed_ip' => $request->ip(),
            'viewed_at' => $contract->viewed_at ?? now(),
        ]);

        $this->recordContractAudit($contract, 'signed', [
            'token' => $request->token,
            'verification_code' => $request->input('verification_code'),
            'signed_ip' => $request->ip(),
        ]);

        Notification::firstOrCreate(
            ['dedup_key' => 'contract_signed_' . $contract->id],
            [
                'type' => 'contract_signed',
                'payload' => [
                    'title' => 'Contract signed',
                    'message' => "Contract ID {$contract->id} has been signed.",
                    'action' => '/rh/contracts',
                ],
                'target_roles' => ['admin', 'rh_manager', 'rh'],
                'channel' => 'in_app',
            ]
        );

        return $this->successResponse($contract->fresh(), 'Contract signed successfully');
    }

    public function reject(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'sometimes|nullable|string|max:1000',
            'token' => 'nullable|string',
            'verification_code' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $this->expireDueContracts();
        $contract = $request->filled('token') || $request->filled('verification_code')
            ? $this->resolveVerificationContract($id, $request->input('token'), $request->input('verification_code'))
            : Contract::findOrFail($id);

        if ($contract->status === 'signed') {
            return $this->errorResponse('A signed contract cannot be rejected.', 422);
        }

        if ($this->isExpired($contract)) {
            return $this->errorResponse('The contract review window has expired.', 422);
        }

        $contract->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => $request->input('reason'),
        ]);

        $this->recordContractAudit($contract, 'rejected', [
            'reason' => $request->input('reason'),
            'token' => $request->input('token'),
            'verification_code' => $request->input('verification_code'),
        ]);

        Notification::firstOrCreate(
            ['dedup_key' => 'contract_rejected_' . $contract->id],
            [
                'type' => 'contract_rejected',
                'payload' => [
                    'title' => 'Contract rejected',
                    'message' => "Contract ID {$contract->id} has been rejected.",
                    'action' => '/rh/contracts',
                ],
                'target_roles' => ['admin', 'rh_manager', 'rh'],
                'channel' => 'in_app',
            ]
        );

        return $this->successResponse($contract->fresh(), 'Contract rejected successfully');
    }

    public function auditLog($id)
    {
        $this->expireDueContracts();
        $contract = Contract::findOrFail($id);
        $logs = $contract->auditLogs()->orderBy('created_at', 'desc')->get();

        return $this->successResponse($logs, 'Contract audit logs retrieved successfully');
    }

    protected function recordContractAudit(Contract $contract, string $action, array $metadata = []): void
    {
        ContractAuditLog::create([
            'contract_id' => $contract->id,
            'actor_id' => auth()->id(),
            'action' => $action,
            'metadata' => $metadata,
        ]);
    }

    protected function expireDueContracts(): void
    {
        Contract::query()
            ->whereIn('status', ['pending', 'viewed'])
            ->where(function ($query) {
                $query
                    ->where(function ($deadlineQuery) {
                        $deadlineQuery
                            ->whereNotNull('signing_deadline')
                            ->where('signing_deadline', '<', now());
                    })
                    ->orWhere(function ($tokenQuery) {
                        $tokenQuery
                            ->whereNotNull('token_expires_at')
                            ->where('token_expires_at', '<', now());
                    });
            })
            ->update(['status' => 'expired']);
    }

    protected function resolveVerificationContract(int $contractId, ?string $token, ?string $verificationCode): Contract
    {
        $query = Contract::query()->where('id', $contractId);

        if ($token) {
            $query->where('verification_token', $token);
        }

        if ($verificationCode) {
            $query->where('verification_code', strtoupper($verificationCode));
        }

        return $query->firstOrFail();
    }

    protected function isExpired(Contract $contract): bool
    {
        if ($contract->status === 'expired') {
            return true;
        }

        if ($contract->signing_deadline && now()->greaterThan($contract->signing_deadline)) {
            $contract->update(['status' => 'expired']);
            return true;
        }

        if ($contract->token_expires_at && now()->greaterThan($contract->token_expires_at)) {
            $contract->update(['status' => 'expired']);
            return true;
        }

        return false;
    }

    protected function generateVerificationCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (Contract::query()->where('verification_code', $code)->exists());

        return $code;
    }

    /**
     * Download contract as PDF
     */
    public function download(Request $request, $id)
    {
        $contract = Contract::with(['employee.department', 'employee.designation', 'contractType'])->findOrFail($id);

        $canAccess = false;
        $currentUser = auth()->user();

        if ($currentUser) {
            $currentUserId = $currentUser->id;
            $isEmployeeOwner = $contract->employee_id === $currentUserId || optional($contract->employee)->user_id === $currentUserId;
            $canAccess = $isEmployeeOwner || $currentUser->hasRole(['admin', 'rh', 'rh_manager', 'manager']);
        }

        if (! $canAccess && ($request->filled('token') || $request->filled('verification_code'))) {
            try {
                $this->resolveVerificationContract(
                    (int) $id,
                    $request->input('token'),
                    $request->input('verification_code')
                );
                $canAccess = true;
            } catch (\Throwable $exception) {
                $canAccess = false;
            }
        }

        if (! $canAccess) {
            return $this->errorResponse('Unauthorized', 403);
        }

        // Only allow download of signed contracts
        if ($contract->status !== 'signed') {
            return $this->errorResponse('Contract must be signed to download', 400);
        }

        $employee = $contract->employee;
        $companyName = config('app.company_name', config('app.name', 'Your Company'));

        $pdf = Pdf::loadView('contracts.contract-pdf', compact(
            'contract',
            'employee',
            'companyName'
        ))
        ->setPaper('a4', 'portrait')
        ->setOption('defaultFont', 'DejaVu Sans');

        $filename = "contract-{$contract->id}-" . Str::slug($contract->contract_name) . ".pdf";

        return $pdf->download($filename);
    }
}
