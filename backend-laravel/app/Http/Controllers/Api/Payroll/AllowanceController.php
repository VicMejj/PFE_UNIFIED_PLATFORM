<?php

namespace App\Http\Controllers\Api\Payroll;

use App\Http\Controllers\Api\ApiController;
use App\Models\Employee\Employee;
use App\Models\Payroll\Allowance;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AllowanceController extends ApiController
{
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
        $allowance->update($request->all());

        return $this->successResponse($allowance->load('employee', 'allowanceOption'), 'Allowance updated successfully');
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
        if (! $user || ! method_exists($user, 'getRoleNames')) {
            return false;
        }

        $roles = $user->getRoleNames()->map(fn ($role) => strtolower((string) $role))->all();

        return (bool) array_intersect($roles, ['admin', 'rh', 'rh_manager', 'hr', 'manager']);
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
