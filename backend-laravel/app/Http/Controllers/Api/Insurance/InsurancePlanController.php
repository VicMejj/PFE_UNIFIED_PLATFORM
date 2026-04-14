<?php

namespace App\Http\Controllers\Api\Insurance;

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\CrudTrait;
use App\Models\Insurance\InsurancePlan;
use App\Models\Insurance\InsuranceAssignment;
use App\Models\Employee\Employee;
use Illuminate\Http\Request;

class InsurancePlanController extends ApiController
{
    use CrudTrait;

    protected $modelClass = InsurancePlan::class;

    public function index(Request $request)
    {
        $query = InsurancePlan::with(['provider']);

        // Filter by status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by insurance type
        if ($type = $request->query('type')) {
            $query->where('insurance_type', $type);
        }

        // Filter by coverage type
        if ($coverageType = $request->query('coverage_type')) {
            $query->where('coverage_type', $coverageType);
        }

        // Search by name or code
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('plan_code', 'like', "%{$search}%");
            });
        }

        // Only active plans by default
        if (!$request->has('is_active')) {
            $query->active();
        }

        $plans = $request->query('per_page', false) === 'false'
            ? $query->get()
            : $query->paginate($request->query('per_page', 15));

        return $this->successResponse($plans);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'plan_code' => 'nullable|string|max:50|unique:insurance_plans,plan_code',
            'provider_id' => 'nullable|exists:insurance_providers,id',
            'coverage_type' => 'required|in:individual,family,group',
            'insurance_type' => 'required|in:health,dental,vision,life,disability',
            'reimbursement_percentage' => 'nullable|numeric|min:0|max:100',
            'maximum_yearly_amount' => 'nullable|numeric|min:0',
            'deductible_amount' => 'nullable|numeric|min:0',
            'waiting_period_days' => 'nullable|integer|min:0',
            'covered_services' => 'nullable|array',
            'excluded_services' => 'nullable|array',
            'required_documents' => 'nullable|array',
            'conditions' => 'nullable|array',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'effective_date' => 'nullable|date',
            'expiration_date' => 'nullable|date|after:effective_date',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['is_active'] = $validated['is_active'] ?? true;

        $plan = InsurancePlan::create($validated);

        return $this->successResponse($plan, 'Insurance plan created successfully', 201);
    }

    public function show($id)
    {
        $plan = InsurancePlan::with([
            'provider',
            'assignments.employee.user',
            'assignments.department',
        ])->findOrFail($id);

        $plan->loadCount(['assignments as active_assignments_count' => function ($query) {
            $query->where('status', 'active');
        }]);

        return $this->successResponse($plan);
    }

    public function update(Request $request, $id)
    {
        $plan = InsurancePlan::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'coverage_type' => 'sometimes|required|in:individual,family,group',
            'insurance_type' => 'sometimes|required|in:health,dental,vision,life,disability',
            'reimbursement_percentage' => 'sometimes|nullable|numeric|min:0|max:100',
            'maximum_yearly_amount' => 'sometimes|nullable|numeric|min:0',
            'deductible_amount' => 'sometimes|nullable|numeric|min:0',
            'waiting_period_days' => 'sometimes|nullable|integer|min:0',
            'covered_services' => 'sometimes|nullable|array',
            'excluded_services' => 'sometimes|nullable|array',
            'required_documents' => 'sometimes|nullable|array',
            'conditions' => 'sometimes|nullable|array',
            'description' => 'sometimes|nullable|string',
            'is_active' => 'sometimes|boolean',
            'effective_date' => 'sometimes|nullable|date',
            'expiration_date' => 'sometimes|nullable|date|after:effective_date',
        ]);

        $plan->update($validated);

        return $this->successResponse($plan, 'Insurance plan updated successfully');
    }

    public function destroy($id)
    {
        $plan = InsurancePlan::findOrFail($id);

        // Check if plan has active assignments
        $activeAssignments = $plan->assignments()->where('status', 'active')->count();
        if ($activeAssignments > 0) {
            return $this->errorResponse(
                "Cannot delete plan with {$activeAssignments} active assignments. Terminate assignments first.",
                422
            );
        }

        $plan->delete();

        return $this->successResponse(null, 'Insurance plan deleted successfully');
    }

    /**
     * Activate an insurance plan
     */
    public function activate($id)
    {
        $plan = InsurancePlan::findOrFail($id);
        $plan->update(['is_active' => true]);

        return $this->successResponse($plan, 'Insurance plan activated');
    }

    /**
     * Deactivate an insurance plan
     */
    public function deactivate($id)
    {
        $plan = InsurancePlan::findOrFail($id);

        $activeAssignments = $plan->assignments()->where('status', 'active')->count();
        if ($activeAssignments > 0) {
            return $this->errorResponse(
                "Cannot deactivate plan with {$activeAssignments} active assignments",
                422
            );
        }

        $plan->update(['is_active' => false]);

        return $this->successResponse($plan, 'Insurance plan deactivated');
    }

    /**
     * Get plan statistics
     */
    public function statistics($id)
    {
        $plan = InsurancePlan::findOrFail($id);

        $stats = [
            'total_assignments' => $plan->assignments()->count(),
            'active_assignments' => $plan->assignments()->where('status', 'active')->count(),
            'total_claims' => 0, // Would need to query through enrollments
            'total_reimbursed' => 0,
        ];

        return $this->successResponse($stats);
    }

    /**
     * Calculate reimbursement for a given amount
     */
    public function calculateReimbursement($id, Request $request)
    {
        $plan = InsurancePlan::findOrFail($id);
        $amount = $request->query('amount', 0);

        $reimbursement = $plan->calculateReimbursement($amount);

        return $this->successResponse([
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'claimed_amount' => $amount,
            'deductible' => $plan->deductible_amount,
            'reimbursement_percentage' => $plan->reimbursement_percentage,
            'calculated_reimbursement' => $reimbursement,
            'maximum_yearly' => $plan->maximum_yearly_amount,
        ]);
    }

    /**
     * Check if a service is covered
     */
    public function checkCoverage($id, Request $request)
    {
        $plan = InsurancePlan::findOrFail($id);
        $service = $request->query('service');

        if (!$service) {
            return $this->errorResponse('Service parameter is required', 422);
        }

        $isCovered = $plan->isServiceCovered($service);

        return $this->successResponse([
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'service' => $service,
            'is_covered' => $isCovered,
        ]);
    }

    /**
     * Assign insurance plan to employees
     */
    public function assignToEmployees($id, Request $request)
    {
        $plan = InsurancePlan::findOrFail($id);

        $validated = $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
            'effective_date' => 'required|date',
            'end_date' => 'nullable|date|after:effective_date',
            'employee_contribution' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $assignedCount = 0;
        $skippedCount = 0;

        foreach ($validated['employee_ids'] as $employeeId) {
            // Check if already assigned
            $exists = InsuranceAssignment::where('insurance_plan_id', $plan->id)
                ->where('employee_id', $employeeId)
                ->where('effective_date', $validated['effective_date'])
                ->exists();

            if ($exists) {
                $skippedCount++;
                continue;
            }

            InsuranceAssignment::create([
                'insurance_plan_id' => $plan->id,
                'employee_id' => $employeeId,
                'assigned_by' => auth()->id(),
                'assignment_type' => 'bulk',
                'effective_date' => $validated['effective_date'],
                'end_date' => $validated['end_date'] ?? null,
                'status' => 'active',
                'employee_contribution' => $validated['employee_contribution'] ?? 0,
                'notes' => $validated['notes'] ?? null,
            ]);

            $assignedCount++;
        }

        return $this->successResponse([
            'assigned' => $assignedCount,
            'skipped' => $skippedCount,
        ], "Assigned plan to {$assignedCount} employees ({$skippedCount} skipped)");
    }

    /**
     * Assign insurance plan to a department
     */
    public function assignToDepartment($id, Request $request)
    {
        $plan = InsurancePlan::findOrFail($id);

        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'effective_date' => 'required|date',
            'end_date' => 'nullable|date|after:effective_date',
            'employee_contribution' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Get all active employees in the department
        $employees = Employee::where('department_id', $validated['department_id'])
            ->where('is_active', true)
            ->pluck('id');

        $assignedCount = 0;

        foreach ($employees as $employeeId) {
            InsuranceAssignment::create([
                'insurance_plan_id' => $plan->id,
                'employee_id' => $employeeId,
                'assigned_by' => auth()->id(),
                'assignment_type' => 'department',
                'department_id' => $validated['department_id'],
                'effective_date' => $validated['effective_date'],
                'end_date' => $validated['end_date'] ?? null,
                'status' => 'active',
                'employee_contribution' => $validated['employee_contribution'] ?? 0,
                'notes' => $validated['notes'] ?? null,
            ]);

            $assignedCount++;
        }

        return $this->successResponse([
            'assigned' => $assignedCount,
            'department_id' => $validated['department_id'],
        ], "Assigned plan to {$assignedCount} employees in department");
    }
}