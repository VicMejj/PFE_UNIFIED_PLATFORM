<?php

namespace App\Http\Controllers\Api\Payroll;

use App\Http\Controllers\Api\ApiController;
use App\Models\Employee\Employee;
use App\Models\Payroll\Allowance;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class AllowanceController extends ApiController
{
    public function __construct(
        private \App\Services\EmployeeScoreService $scoreService
    ) {}

    /**
     * Get all allowances (optionally filtered by employee)
     */
    public function index(Request $request)
    {
        $query = Allowance::query()->with('employee', 'allowanceOption');

        if (! $this->canManageBenefits()) {
            $employeeIds = Employee::query()
                ->where('user_id', auth()->id())
                ->pluck('id');

            $query->whereIn('employee_id', $employeeIds);
        }

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $allowances = $query->paginate($request->get('per_page', 15));
        return $this->successResponse($allowances, 'Allowances retrieved successfully');
    }

    /**
     * Assign benefit/allowance to employee
     */
    public function store(Request $request)
    {
        if (! $this->canManageBenefits()) {
            return $this->forbiddenResponse('You are not allowed to assign benefits.');
        }

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'allowance_option_id' => 'required|exists:allowance_options,id',
            'amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'sometimes|string|in:active,inactive,pending',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $employee = Employee::findOrFail($request->employee_id);
        $score = $this->scoreService->getScore($employee);

        if ($score->overall_score < 70 && ! $request->boolean('force')) {
            return $this->errorResponse(
                'This employee has a holistic score below the required threshold (70%). Assignment requires manual confirmation.',
                422,
                [
                    'score' => $score->overall_score,
                    'requires_force' => true,
                    'employee_name' => $employee->name
                ]
            );
        }

        $allowance = Allowance::create([
            'employee_id' => $request->employee_id,
            'allowance_option_id' => $request->allowance_option_id,
            'amount' => $request->amount,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => $request->input('status', 'active'),
        ]);

        $allowance->load('employee', 'allowanceOption');
        $userId = $allowance->employee?->user_id;

        if ($userId) {
            Notification::firstOrCreate(
                ['dedup_key' => 'benefit_assigned_' . $allowance->id],
                [
                    'type' => 'benefit_assigned',
                    'payload' => [
                        'title' => 'Benefit assigned',
                        'message' => sprintf(
                            '%s was assigned to you starting %s.',
                            $allowance->allowanceOption?->name ?? 'A benefit',
                            optional($allowance->start_date)->format('M d, Y') ?? $request->start_date
                        ),
                        'action' => '/social/benefits',
                    ],
                    'target_user_ids' => [$userId],
                    'target_roles' => ['admin', 'rh_manager', 'rh', 'hr'],
                    'channel' => 'in_app',
                ]
            );
        }

        return $this->successResponse($allowance, 'Allowance assigned successfully', 201);
    }

    /**
     * Get specific allowance
     */
    public function show($id)
    {
        $allowance = $this->resolveVisibleAllowance($id);
        return $this->successResponse($allowance, 'Allowance retrieved successfully');
    }

    /**
     * Update allowance
     */
    public function update(Request $request, $id)
    {
        if (! $this->canManageBenefits()) {
            return $this->forbiddenResponse('You are not allowed to update benefits.');
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'sometimes|numeric|min:0',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|nullable|date|after_or_equal:start_date',
            'status' => 'sometimes|string|in:active,inactive,pending',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $allowance = $this->resolveVisibleAllowance($id);
        $oldStatus = $allowance->status;
        $allowance->update($request->all());

        if ($request->has('status') && $oldStatus !== $request->status) {
            $allowance->update([
                'status_changed_at' => now(),
                'status_changed_by' => auth()->id(),
            ]);

            $this->sendStatusChangeNotification($allowance, $oldStatus, $request->status);
        }

        return $this->successResponse($allowance->load('employee', 'allowanceOption'), 'Allowance updated successfully');
    }

    /**
     * Update allowance status only (convenience endpoint)
     */
    public function updateStatus(Request $request, $id)
    {
        if (! $this->canManageBenefits()) {
            return $this->forbiddenResponse('You are not allowed to update benefit status. Required roles: admin, rh_manager, rh, hr, or manager.');
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:active,inactive,pending',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $allowance = $this->resolveVisibleAllowance($id);
        $oldStatus = $allowance->status;
        $newStatus = $request->status;

        $allowance->update([
            'status' => $newStatus,
            'status_changed_at' => now(),
            'status_changed_by' => auth()->id(),
        ]);

        $this->sendStatusChangeNotification($allowance, $oldStatus, $newStatus);

        return $this->successResponse(
            $allowance->load('employee', 'allowanceOption', 'statusChangedByUser'),
            "Benefit status changed from '{$oldStatus}' to '{$newStatus}'"
        );
    }

    /**
     * Claim a benefit (Employee action)
     */
    public function claim($id)
    {
        $allowance = Allowance::whereHas('employee', function ($query) {
            $query->where('user_id', auth()->id());
        })->findOrFail($id);

        if (!$allowance->isClaimable()) {
            return $this->errorResponse(
                'This benefit cannot be claimed. It may already be claimed or is not active.',
                422
            );
        }

        $allowance->update([
            'claimed' => true,
            'claimed_at' => now(),
            'claimed_by' => auth()->id(),
        ]);

        Notification::firstOrCreate(
            ['dedup_key' => 'benefit_claimed_' . $allowance->id],
            [
                'type' => 'benefit_claimed',
                'payload' => [
                    'title' => 'Benefit Claimed',
                    'message' => sprintf(
                        'You have successfully claimed %s.',
                        $allowance->allowanceOption?->name ?? 'the benefit'
                    ),
                    'action' => '/social/benefits',
                ],
                'target_user_ids' => [auth()->id()],
                'channel' => 'in_app',
            ]
        );

        Notification::firstOrCreate(
            ['dedup_key' => 'benefit_claimed_admin_' . $allowance->id],
            [
                'type' => 'benefit_claimed_admin',
                'payload' => [
                    'title' => 'Employee Claimed Benefit',
                    'message' => sprintf(
                        '%s has claimed benefit: %s',
                        $allowance->employee?->name ?? 'An employee',
                        $allowance->allowanceOption?->name ?? 'a benefit'
                    ),
                    'action' => '/social/employee-benefits',
                ],
                'target_roles' => ['admin', 'rh_manager', 'rh', 'hr'],
                'channel' => 'in_app',
            ]
        );

        return $this->successResponse(
            $allowance->load('employee', 'allowanceOption'),
            'Benefit claimed successfully'
        );
    }

    /**
     * Send notification when benefit status changes
     */
    private function sendStatusChangeNotification(Allowance $allowance, string $oldStatus, string $newStatus): void
    {
        $userId = $allowance->employee?->user_id;
        
        $title = 'Benefit Status Updated';
        $message = '';

        if ($newStatus === 'active' && $oldStatus === 'pending') {
            $message = sprintf(
                'Your benefit "%s" is now active! You can now claim it.',
                $allowance->allowanceOption?->name ?? 'A benefit'
            );
        } elseif ($newStatus === 'pending' && $oldStatus === 'active') {
            $message = sprintf(
                'Your benefit "%s" status has been changed to pending.',
                $allowance->allowanceOption?->name ?? 'A benefit'
            );
        } elseif ($newStatus === 'inactive') {
            $message = sprintf(
                'Your benefit "%s" has been deactivated.',
                $allowance->allowanceOption?->name ?? 'A benefit'
            );
        } elseif ($newStatus === 'active') {
            $message = sprintf(
                'Your benefit "%s" is now active.',
                $allowance->allowanceOption?->name ?? 'A benefit'
            );
        }

        if ($userId) {
            Notification::firstOrCreate(
                ['dedup_key' => 'benefit_status_' . $allowance->id . '_' . $newStatus],
                [
                    'type' => 'benefit_status_changed',
                    'payload' => [
                        'title' => $title,
                        'message' => $message,
                        'action' => '/social/benefits',
                    ],
                    'target_user_ids' => [$userId],
                    'channel' => 'in_app',
                ]
            );
        }

        Notification::firstOrCreate(
            ['dedup_key' => 'benefit_status_admin_' . $allowance->id . '_' . $newStatus],
            [
                'type' => 'benefit_status_changed_admin',
                'payload' => [
                    'title' => 'Benefit Status Changed',
                    'message' => sprintf(
                        '%s\'s benefit "%s" was changed from %s to %s by admin.',
                        $allowance->employee?->name ?? 'An employee',
                        $allowance->allowanceOption?->name ?? 'a benefit',
                        $oldStatus,
                        $newStatus
                    ),
                    'action' => '/social/employee-benefits',
                ],
                'target_roles' => ['admin', 'rh_manager', 'rh', 'hr'],
                'channel' => 'in_app',
            ]
        );
    }

    /**
     * Remove allowance from employee
     */
    public function destroy($id)
    {
        if (! $this->canManageBenefits()) {
            return $this->forbiddenResponse('You are not allowed to remove benefits.');
        }

        $allowance = $this->resolveVisibleAllowance($id);
        $userId = $allowance->employee?->user_id;

        if ($userId) {
            Notification::firstOrCreate(
                ['dedup_key' => 'benefit_removed_' . $allowance->id],
                [
                    'type' => 'benefit_removed',
                    'payload' => [
                        'title' => 'Benefit removed',
                        'message' => sprintf(
                            '%s is no longer assigned to your profile.',
                            $allowance->allowanceOption?->name ?? 'A benefit'
                        ),
                        'action' => '/social/benefits',
                    ],
                    'target_user_ids' => [$userId],
                    'channel' => 'in_app',
                ]
            );
        }

        $allowance->delete();

        return $this->successResponse(null, 'Allowance removed successfully');
    }

    private function canManageBenefits(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        if (method_exists($user, 'getRoleNames')) {
            $roles = $user->getRoleNames()->map(fn ($role) => strtolower((string) $role))->all();
            if (array_intersect($roles, ['admin', 'rh', 'rh_manager', 'hr', 'manager'])) {
                return true;
            }
        }

        if (method_exists($user, 'hasRole')) {
            return $user->hasRole(['admin', 'rh_manager', 'rh', 'hr', 'manager']);
        }

        if (property_exists($user, 'role')) {
            $role = strtolower((string) $user->role);
            return in_array($role, ['admin', 'rh', 'rh_manager', 'hr', 'manager']);
        }

        return false;
    }

    private function resolveVisibleAllowance(int $id): Allowance
    {
        $query = Allowance::query()->with('employee', 'allowanceOption');

        if (! $this->canManageBenefits()) {
            $employeeIds = Employee::query()
                ->where('user_id', auth()->id())
                ->pluck('id');

            $query->whereIn('employee_id', $employeeIds);
        }

        return $query->findOrFail($id);
    }
}
