<?php

namespace App\Http\Controllers\Api\Payroll;

use App\Http\Controllers\Api\ApiController;
use App\Models\Payroll\BenefitRequest;
use App\Models\Payroll\BenefitRequestDocument;
use App\Models\Payroll\AllowanceOption;
use App\Models\Employee\Employee;
use App\Services\EmployeeScoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BenefitRequestController extends ApiController
{
    public function __construct(
        private EmployeeScoreService $scoreService
    ) {}

    /**
     * Get all benefit requests with filters
     */
    public function index(Request $request)
    {
        $query = BenefitRequest::with([
            'employee.user',
            'employee.department',
            'allowanceOption',
            'submittedBy',
            'approvedBy',
        ]);

        // Filter by status
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // Filter by allowance option
        if ($allowanceId = $request->query('allowance_option_id')) {
            $query->where('allowance_option_id', $allowanceId);
        }

        // Filter by employee
        if ($employeeId = $request->query('employee_id')) {
            $query->where('employee_id', $employeeId);
        }

        // Filter by date range
        if ($startDate = $request->query('start_date')) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate = $request->query('end_date')) {
            $query->where('created_at', '<=', $endDate);
        }

        // Search by request number or employee name
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                    ->orWhereHas('employee', function ($eq) use ($search) {
                        $eq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $requests = $request->query('per_page', false) === 'false'
            ? $query->get()
            : $query->latest()->paginate($request->query('per_page', 15));

        return $this->successResponse($requests);
    }

    /**
     * Get employee's own benefit requests
     */
    public function myRequests(Request $request)
    {
        $employee = Employee::where('user_id', auth()->id())->first();

        if (!$employee) {
            return $this->errorResponse('Employee profile not found', 404);
        }

        $query = BenefitRequest::where('employee_id', $employee->id)
            ->with(['allowanceOption', 'approvedBy'])
            ->latest();

        $requests = $request->query('per_page', false) === 'false'
            ? $query->get()
            : $query->paginate($request->query('per_page', 15));

        return $this->successResponse($requests);
    }

    /**
     * Show a specific benefit request
     */
    public function show($id)
    {
        $request = BenefitRequest::with([
            'employee.user',
            'employee.department',
            'employee.designation',
            'allowanceOption',
            'submittedBy',
            'reviewedBy',
            'approvedBy',
            'documents',
        ])->findOrFail($id);

        return $this->successResponse($request);
    }

    /**
     * Submit a new benefit request (Employee self-service)
     */
    public function store(Request $request)
    {
        $employee = Employee::where('user_id', auth()->id())->first();

        if (!$employee) {
            return $this->errorResponse('Employee profile not found', 404);
        }

        $validated = $request->validate([
            'allowance_option_id' => 'required|exists:allowance_options,id',
            'requested_amount' => 'required|numeric|min:0',
            'reason' => 'required|string',
            'supporting_documents' => 'nullable|array',
        ]);

        // Check if employee is eligible based on score
        $allowanceOption = AllowanceOption::findOrFail($validated['allowance_option_id']);
        $eligibilityRule = $allowanceOption->benefitEligibilityRules()->first();

        $isEligible = true;
        $eligibilityMessage = '';

        if ($eligibilityRule && $eligibilityRule->threshold > 0) {
            $score = $this->scoreService->getScore($employee);
            if ($score->overall_score < $eligibilityRule->threshold) {
                $isEligible = false;
                $eligibilityMessage = "Your score ({$score->overall_score}) is below the required threshold ({$eligibilityRule->threshold})";
            }
        }

        // Create the request
        $benefitRequest = BenefitRequest::create([
            'employee_id' => $employee->id,
            'allowance_option_id' => $validated['allowance_option_id'],
            'requested_amount' => $validated['requested_amount'],
            'reason' => $validated['reason'],
            'supporting_documents' => $validated['supporting_documents'] ?? [],
            'submitted_by' => auth()->id(),
            'status' => BenefitRequest::STATUS_SUBMITTED,
        ]);

        $response = $this->successResponse($benefitRequest, 'Benefit request submitted successfully', 201);

        if (!$isEligible) {
            $response->setData([
                'data' => $benefitRequest,
                'message' => 'Request submitted but may be rejected due to eligibility',
                'eligibility' => [
                    'is_eligible' => $isEligible,
                    'message' => $eligibilityMessage,
                ],
            ]);
        }

        return $response;
    }

    /**
     * Upload document for a benefit request
     */
    public function uploadDocument(Request $request, $id)
    {
        $benefitRequest = BenefitRequest::findOrFail($id);

        if (!$request->hasFile('document')) {
            return $this->errorResponse('Document file is required', 422);
        }

        $file = $request->file('document');
        $path = $file->store('benefit-requests', 'public');

        $document = BenefitRequestDocument::create([
            'benefit_request_id' => $benefitRequest->id,
            'document_type' => $request->input('document_type', $file->getClientMimeType()),
            'document_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);

        return $this->successResponse($document, 'Document uploaded successfully', 201);
    }

    /**
     * Start review process (HR/Manager action)
     */
    public function startReview($id)
    {
        $benefitRequest = BenefitRequest::findOrFail($id);

        if (!in_array($benefitRequest->status, [BenefitRequest::STATUS_SUBMITTED])) {
            return $this->errorResponse('Request cannot be reviewed in current status', 422);
        }

        $benefitRequest->startReview(auth()->user());

        return $this->successResponse($benefitRequest, 'Request moved to review');
    }

    /**
     * Approve a benefit request (HR/Manager action)
     */
    public function approve(Request $request, $id)
    {
        $benefitRequest = BenefitRequest::findOrFail($id);

        $validated = $request->validate([
            'approved_amount' => 'nullable|numeric|min:0',
            'comments' => 'nullable|string',
        ]);

        if (!$benefitRequest->canBeApproved()) {
            return $this->errorResponse('Request cannot be approved in current status', 422);
        }

        $approvedAmount = $validated['approved_amount'] ?? $benefitRequest->requested_amount;
        $benefitRequest->approve($approvedAmount, auth()->user(), $validated['comments'] ?? null);

        return $this->successResponse($benefitRequest, 'Benefit request approved successfully');
    }

    /**
     * Reject a benefit request (HR/Manager action)
     */
    public function reject(Request $request, $id)
    {
        $benefitRequest = BenefitRequest::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        if (!$benefitRequest->canBeApproved()) {
            return $this->errorResponse('Request cannot be rejected in current status', 422);
        }

        $benefitRequest->reject(auth()->user(), $validated['reason']);

        return $this->successResponse($benefitRequest, 'Benefit request rejected');
    }

    /**
     * Mark benefit as delivered
     */
    public function deliver($id)
    {
        $benefitRequest = BenefitRequest::findOrFail($id);

        if (!$benefitRequest->isApproved()) {
            return $this->errorResponse('Only approved requests can be marked as delivered', 422);
        }

        $benefitRequest->deliver();

        return $this->successResponse($benefitRequest, 'Benefit marked as delivered');
    }

    /**
     * Cancel a benefit request (Employee action for draft/submitted)
     */
    public function cancel($id)
    {
        $benefitRequest = BenefitRequest::findOrFail($id);

        if (!in_array($benefitRequest->status, [BenefitRequest::STATUS_DRAFT, BenefitRequest::STATUS_SUBMITTED])) {
            return $this->errorResponse('Only draft or submitted requests can be cancelled', 422);
        }

        $benefitRequest->cancel();

        return $this->successResponse($benefitRequest, 'Benefit request cancelled');
    }

    /**
     * Get pending requests count for dashboard
     */
    public function pendingCount()
    {
        $counts = [
            'submitted' => BenefitRequest::where('status', BenefitRequest::STATUS_SUBMITTED)->count(),
            'under_review' => BenefitRequest::where('status', BenefitRequest::STATUS_UNDER_REVIEW)->count(),
            'approved' => BenefitRequest::where('status', BenefitRequest::STATUS_APPROVED)->count(),
            'rejected' => BenefitRequest::where('status', BenefitRequest::STATUS_REJECTED)->count(),
        ];

        return $this->successResponse($counts);
    }

    /**
     * Get benefit request statistics
     */
    public function statistics()
    {
        $stats = [
            'total_requests' => BenefitRequest::count(),
            'total_approved_amount' => BenefitRequest::approved()->sum('approved_amount'),
            'total_requested_amount' => BenefitRequest::sum('requested_amount'),
            'average_approval_time_hours' => 0, // Would need calculation
            'by_status' => [
                'draft' => BenefitRequest::where('status', BenefitRequest::STATUS_DRAFT)->count(),
                'submitted' => BenefitRequest::where('status', BenefitRequest::STATUS_SUBMITTED)->count(),
                'under_review' => BenefitRequest::where('status', BenefitRequest::STATUS_UNDER_REVIEW)->count(),
                'approved' => BenefitRequest::where('status', BenefitRequest::STATUS_APPROVED)->count(),
                'rejected' => BenefitRequest::where('status', BenefitRequest::STATUS_REJECTED)->count(),
                'delivered' => BenefitRequest::where('status', BenefitRequest::STATUS_DELIVERED)->count(),
            ],
            'by_allowance' => BenefitRequest::selectRaw('allowance_option_id, COUNT(*) as count')
                ->groupBy('allowance_option_id')
                ->with('allowanceOption')
                ->get(),
        ];

        return $this->successResponse($stats);
    }

    /**
     * Auto-approve small benefit requests based on rules
     */
    public function autoApprove($id)
    {
        $benefitRequest = BenefitRequest::findOrFail($id);

        if ($benefitRequest->status !== BenefitRequest::STATUS_SUBMITTED) {
            return $this->errorResponse('Only submitted requests can be auto-approved', 422);
        }

        $allowanceOption = $benefitRequest->allowanceOption;
        $eligibilityRule = $allowanceOption->benefitEligibilityRules()->first();

        if (!$eligibilityRule || !$eligibilityRule->auto_approve_threshold) {
            return $this->errorResponse('Auto-approval not configured for this benefit', 422);
        }

        // Check if amount is below auto-approve threshold
        if ($benefitRequest->requested_amount > $eligibilityRule->auto_approve_threshold) {
            return $this->errorResponse(
                "Amount exceeds auto-approve threshold of {$eligibilityRule->auto_approve_threshold}",
                422
            );
        }

        // Check employee score
        $employee = $benefitRequest->employee;
        $score = $this->scoreService->getScore($employee);

        if ($eligibilityRule->threshold > 0 && $score->overall_score < $eligibilityRule->threshold) {
            return $this->errorResponse(
                "Employee score below required threshold",
                422
            );
        }

        $benefitRequest->markAsAutoApproved("Amount below threshold and score meets requirements");

        return $this->successResponse($benefitRequest, 'Benefit request auto-approved');
    }
}