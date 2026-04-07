<?php

namespace App\Http\Controllers\Api\Leave;

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\CallsDjangoAI;
use App\Models\Employee\Employee;
use App\Models\Leave\Holiday;
use App\Models\Leave\Leave;
use App\Models\Leave\LeaveBalance;
use App\Models\Leave\LeaveType;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class LeaveController extends ApiController
{
    use CallsDjangoAI;

    public function index(Request $request)
    {
        $query = Leave::query()->with(['employee', 'leaveType', 'approvedBy'])->latest('created_at');

        if (! $this->canManageLeaves()) {
            $employeeIds = Employee::query()
                ->where('user_id', auth()->id())
                ->pluck('id');

            $query->whereIn('employee_id', $employeeIds);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return $this->successResponse($query->paginate());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'nullable|exists:employees,id',
            'leave_type_id' => 'nullable|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'reason' => 'nullable|string|max:1000',
        ]);

        $data['employee_id'] = $this->resolveTargetEmployeeId($data['employee_id'] ?? null);

        if (empty($data['employee_id'])) {
            return $this->errorResponse(
                'No employee profile is linked to this account yet.',
                422,
                ['employee_id' => ['No employee profile is linked to this account yet.']]
            );
        }

        if (empty($data['leave_type_id']) && Schema::hasColumn('leaves', 'leave_type_id')) {
            $data['leave_type_id'] = LeaveType::query()
                ->when(Schema::hasColumn('leave_types', 'is_active'), fn ($query) => $query->where('is_active', true))
                ->value('id');
        }

        if (empty($data['leave_type_id']) && Schema::hasColumn('leaves', 'leave_type_id')) {
            return $this->errorResponse(
                'No leave types are configured yet. Please contact an administrator.',
                422,
                ['leave_type_id' => ['No leave types are configured yet.']]
            );
        }

        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);
        $policyCheck = $this->evaluateLeaveRequest(
            $data['employee_id'],
            $data['leave_type_id'] ?? null,
            $startDate,
            $endDate,
        );

        if (! empty($policyCheck['errors'])) {
            return $this->errorResponse('Validation failed', 422, $policyCheck['errors']);
        }

        $payload = [
            'employee_id' => $data['employee_id'],
            'leave_type_id' => $data['leave_type_id'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
            'total_days' => $policyCheck['working_days'],
            'policy_violations' => $policyCheck['policy_violations'],
        ];

        if (! empty($data['leave_type_id']) && Schema::hasColumn('leaves', 'leave_type_id')) {
            $payload['leave_type_id'] = $data['leave_type_id'];
        }

        if (Schema::hasColumn('leaves', 'days_requested')) {
            $payload['days_requested'] = $policyCheck['calendar_days'];
        }

        $leave = Leave::create($payload);

        try {
            $response = $this->djangoPost('/api/ai/leave/approval-probability/', [
                'employee_id' => $leave->employee_id,
                'leave_type_id' => $leave->leave_type_id,
                'start_date' => $leave->start_date->toDateString(),
                'end_date' => $leave->end_date->toDateString(),
                'total_days' => $leave->total_days,
                'policy_violations' => $policyCheck['policy_violations'],
            ]);

            if ($response->successful()) {
                $probability = $response->json('data.approval_probability');
                if (is_numeric($probability)) {
                    $leave->update(['approval_probability' => (float) $probability]);
                }
            }
        } catch (\Throwable $e) {
            // Silently ignore AI service failures for leave creation.
        }

        Notification::firstOrCreate(
            ['dedup_key' => 'leave_submitted_' . $leave->id],
            [
                'type' => 'leave_submitted',
                'payload' => [
                    'title' => 'Leave request submitted',
                    'message' => "A new leave request is pending review for employee ID {$leave->employee_id}.",
                    'action' => '/rh/leaves',
                ],
                'target_roles' => ['admin', 'rh_manager', 'rh', 'manager'],
                'channel' => 'in_app',
            ]
        );

        return $this->successResponse($leave->load(['employee', 'leaveType', 'approvedBy']), 'Leave request submitted successfully.', 201);
    }

    public function requestInsights(Request $request): JsonResponse
    {
        $data = $request->validate([
            'employee_id' => 'nullable|exists:employees,id',
            'leave_type_id' => 'nullable|exists:leave_types,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $employeeId = $this->resolveTargetEmployeeId($data['employee_id'] ?? null);
        if (! $employeeId) {
            return $this->successResponse([
                'employee' => null,
                'leave_balance' => null,
                'working_days' => null,
                'calendar_days' => null,
                'approval_probability' => null,
                'policy_violations' => [],
                'validation_errors' => ['No employee profile is linked to this account yet.'],
            ], 'Leave insights preview returned.');
        }

        $employee = Employee::query()->find($employeeId);
        $leaveTypeId = $data['leave_type_id'] ?? null;
        $balance = $leaveTypeId
            ? LeaveBalance::query()
                ->where('employee_id', $employeeId)
                ->where('leave_type_id', $leaveTypeId)
                ->first()
            : null;

        $insights = [
            'employee' => $this->mapEmployeeSummary($employee),
            'leave_balance' => $balance ? [
                'leave_type_id' => $balance->leave_type_id,
                'remaining' => $balance->remaining,
                'used_days' => (int) ($balance->used_days ?? 0),
                'opening_balance' => (int) ($balance->opening_balance ?? $balance->balance ?? 0),
            ] : null,
            'working_days' => null,
            'calendar_days' => null,
            'approval_probability' => null,
            'policy_violations' => [],
            'validation_errors' => [],
        ];

        if (! empty($data['start_date']) && ! empty($data['end_date'])) {
            $startDate = Carbon::parse($data['start_date']);
            $endDate = Carbon::parse($data['end_date']);
            $policyCheck = $this->evaluateLeaveRequest(
                $employeeId,
                $leaveTypeId,
                $startDate,
                $endDate,
            );

            $insights['working_days'] = $policyCheck['working_days'];
            $insights['calendar_days'] = $policyCheck['calendar_days'];
            $insights['policy_violations'] = $policyCheck['policy_violations'];
            $insights['validation_errors'] = $this->flattenValidationErrors($policyCheck['errors']);

            if (empty($policyCheck['errors'])) {
                $insights['approval_probability'] = $this->estimateApprovalProbability(
                    $employeeId,
                    $leaveTypeId,
                    $startDate,
                    $endDate,
                    $policyCheck['working_days'],
                    $policyCheck['policy_violations'],
                );
            }
        }

        return $this->successResponse($insights, 'Leave insights preview returned.');
    }

    public function show($id)
    {
        $leave = Leave::query()->with(['employee', 'leaveType', 'approvedBy'])->findOrFail($id);

        if (! $this->canViewLeave($leave)) {
            return $this->forbiddenResponse('You are not allowed to view this leave request.');
        }

        return $this->successResponse($leave);
    }

    public function update(Request $request, $id)
    {
        $leave = Leave::query()->with(['employee', 'leaveType', 'approvedBy'])->findOrFail($id);

        if (! $this->canViewLeave($leave)) {
            return $this->forbiddenResponse('You are not allowed to update this leave request.');
        }

        $data = $request->validate([
            'leave_type_id' => 'sometimes|nullable|exists:leave_types,id',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date',
            'reason' => 'sometimes|nullable|string|max:1000',
            'status' => 'sometimes|nullable|string|max:50',
            'rejection_reason' => 'sometimes|nullable|string|max:1000',
        ]);

        $startDate = Carbon::parse($data['start_date'] ?? $leave->start_date);
        $endDate = Carbon::parse($data['end_date'] ?? $leave->end_date);

        if ($startDate->gte($endDate)) {
            return $this->errorResponse('The end date must be after the start date.', 422, ['end_date' => ['The end date must be after the start date.']]);
        }

        $policyCheck = $this->evaluateLeaveRequest(
            $leave->employee_id,
            $data['leave_type_id'] ?? $leave->leave_type_id,
            $startDate,
            $endDate,
            $leave->id,
        );

        if (! empty($policyCheck['errors'])) {
            return $this->errorResponse('Validation failed', 422, $policyCheck['errors']);
        }

        if (Schema::hasColumn('leaves', 'days_requested')) {
            $data['days_requested'] = $policyCheck['calendar_days'];
        }

        if (Schema::hasColumn('leaves', 'total_days')) {
            $data['total_days'] = $policyCheck['working_days'];
        }

        if (Schema::hasColumn('leaves', 'policy_violations')) {
            $data['policy_violations'] = $policyCheck['policy_violations'];
        }

        $leave->update($data);

        return $this->successResponse($leave->fresh()->load(['employee', 'leaveType', 'approvedBy']), 'Leave request updated successfully.');
    }

    public function destroy($id)
    {
        $leave = Leave::findOrFail($id);

        if (! $this->canViewLeave($leave)) {
            return $this->forbiddenResponse('You are not allowed to delete this leave request.');
        }

        $leave->delete();

        return response()->json(null, 204);
    }

    public function approveByManager($id): JsonResponse
    {
        $leave = Leave::with('employee')->findOrFail($id);
        if (! $this->canManageLeaves()) {
            return $this->forbiddenResponse('You are not allowed to approve leave requests.');
        }
        $updates = [
            'status' => 'approved_by_manager',
            'approved_by' => auth()->id(),
        ];

        if (Schema::hasColumn('leaves', 'approval_date')) {
            $updates['approval_date'] = now();
        }

        $leave->update($updates);

        $this->notifyEmployee(
            $leave,
            'leave_manager_approved',
            "leave_manager_approved_{$leave->id}",
            'Manager approved your leave',
            'Your leave request was approved by a manager and is now waiting for HR confirmation.',
            '/leave-requests',
        );

        return $this->successResponse($leave, 'Leave approved by manager');
    }

    public function approveByHR($id): JsonResponse
    {
        $leave = Leave::with('employee')->findOrFail($id);
        if (! $this->canManageLeaves()) {
            return $this->forbiddenResponse('You are not allowed to approve leave requests.');
        }
        $updates = [
            'status' => 'approved',
            'approved_by' => auth()->id(),
        ];

        if (Schema::hasColumn('leaves', 'approval_date')) {
            $updates['approval_date'] = now();
        }

        $leave->update($updates);

        $this->notifyEmployee(
            $leave,
            'leave_approved',
            "leave_approved_{$leave->id}",
            'Leave approved',
            'Your leave request has been fully approved.',
            '/leave-requests',
        );

        return $this->successResponse($leave, 'Leave approved by HR');
    }

    public function reject(Request $request, $id): JsonResponse
    {
        $leave = Leave::with('employee')->findOrFail($id);
        if (! $this->canManageLeaves()) {
            return $this->forbiddenResponse('You are not allowed to reject leave requests.');
        }
        $data = $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);
        $updates = [
            'status' => 'rejected',
            'rejected_by' => auth()->id(),
        ];

        if (Schema::hasColumn('leaves', 'rejection_reason')) {
            $updates['rejection_reason'] = $data['reason'] ?? null;
        }

        $leave->update($updates);

        $this->notifyEmployee(
            $leave,
            'leave_rejected',
            "leave_rejected_{$leave->id}",
            'Leave rejected',
            $data['reason']
                ? "Your leave request was rejected: {$data['reason']}"
                : 'Your leave request was rejected.',
            '/leave-requests',
        );

        return $this->successResponse($leave, 'Leave rejected');
    }

    public function getOptimalDates(Request $request)
    {
        $employeeId = $this->resolveTargetEmployeeId($request->integer('employee_id'));

        try {
            $response = $this->djangoPost('/api/ai/leave/optimal-dates/', [
                'employee_id' => $employeeId,
            ]);
            return $this->forwardDjangoResponse($response);
        } catch (\Throwable $e) {
            return $this->successResponse([
                'suggested_dates' => [],
                'note' => 'AI service unavailable',
            ], 'Optimal dates fallback returned');
        }
    }

    private function canManageLeaves(): bool
    {
        $user = auth()->user();
        if (! $user || ! method_exists($user, 'getRoleNames')) {
            return false;
        }

        $roles = $user->getRoleNames()->map(fn ($role) => strtolower((string) $role))->all();

        return (bool) array_intersect($roles, ['admin', 'rh', 'rh_manager', 'hr', 'manager']);
    }

    private function canViewLeave(Leave $leave): bool
    {
        if ($this->canManageLeaves()) {
            return true;
        }

        return Employee::query()
            ->where('id', $leave->employee_id)
            ->where('user_id', auth()->id())
            ->exists();
    }

    private function evaluateLeaveRequest(
        int $employeeId,
        ?int $leaveTypeId,
        Carbon $startDate,
        Carbon $endDate,
        ?int $ignoreLeaveId = null
    ): array {
        $errors = [];
        $policyViolations = [];

        if ($startDate->gte($endDate)) {
            $errors['end_date'][] = 'The end date must be after the start date.';
        }

        $holidayMap = Holiday::query()
            ->whereBetween('holiday_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get(['name', 'holiday_date'])
            ->keyBy(fn ($holiday) => Carbon::parse($holiday->holiday_date)->toDateString());

        $weekendDates = [];
        $holidayDates = [];
        $workingDays = 0;

        for ($cursor = $startDate->copy(); $cursor->lte($endDate); $cursor->addDay()) {
            $dateKey = $cursor->toDateString();

            if ($cursor->isWeekend()) {
                $weekendDates[] = $dateKey;
                continue;
            }

            if ($holidayMap->has($dateKey)) {
                $holiday = $holidayMap->get($dateKey);
                $holidayDates[] = sprintf(
                    '%s (%s)',
                    $holiday->name ?: 'Holiday',
                    $dateKey
                );
                continue;
            }

            $workingDays++;
        }

        if ($workingDays === 0) {
            $errors['date_range'][] = 'The selected range does not include any working days.';
        }

        if ($this->hasOverlappingLeave($employeeId, $startDate, $endDate, $ignoreLeaveId)) {
            $errors['date_range'][] = 'This date range overlaps an existing leave request.';
        }

        $leaveType = $leaveTypeId ? LeaveType::find($leaveTypeId) : null;
        if ($leaveType && ! empty($leaveType->maximum_days) && $workingDays > (int) $leaveType->maximum_days) {
            $errors['leave_type_id'][] = "This leave type allows a maximum of {$leaveType->maximum_days} working days.";
        }

        if ($leaveTypeId) {
            $balance = LeaveBalance::query()
                ->where('employee_id', $employeeId)
                ->where('leave_type_id', $leaveTypeId)
                ->first();

            if ($balance && $balance->remaining < $workingDays) {
                $errors['leave_balance'][] = "Not enough leave balance. Only {$balance->remaining} working days remain.";
            }
        }

        if (! empty($weekendDates)) {
            $policyViolations[] = 'Weekend dates will not count as leave days: ' . implode(', ', $weekendDates);
        }

        if (! empty($holidayDates)) {
            $policyViolations[] = 'Company holidays were excluded from the leave count: ' . implode(', ', $holidayDates);
        }

        return [
            'errors' => $errors,
            'policy_violations' => $policyViolations,
            'working_days' => $workingDays,
            'calendar_days' => $startDate->diffInDays($endDate) + 1,
        ];
    }

    private function hasOverlappingLeave(
        int $employeeId,
        Carbon $startDate,
        Carbon $endDate,
        ?int $ignoreLeaveId = null
    ): bool {
        return Leave::query()
            ->where('employee_id', $employeeId)
            ->where('status', '!=', 'rejected')
            ->when($ignoreLeaveId, fn ($query) => $query->where('id', '!=', $ignoreLeaveId))
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->orWhereBetween('end_date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->orWhere(function ($nestedQuery) use ($startDate, $endDate) {
                        $nestedQuery
                            ->where('start_date', '<=', $startDate->toDateString())
                            ->where('end_date', '>=', $endDate->toDateString());
                    });
            })
            ->exists();
    }

    private function notifyEmployee(
        Leave $leave,
        string $type,
        string $dedupKey,
        string $title,
        string $message,
        string $action
    ): void {
        $leave->loadMissing('employee');
        $userId = $leave->employee?->user_id;

        if (! $userId) {
            return;
        }

        Notification::firstOrCreate(
            ['dedup_key' => $dedupKey],
            [
                'type' => $type,
                'payload' => [
                    'title' => $title,
                    'message' => $message,
                    'action' => $action,
                ],
                'target_user_ids' => [$userId],
                'channel' => 'in_app',
            ]
        );
    }

    private function resolveTargetEmployeeId(?int $requestedEmployeeId = null): ?int
    {
        if ($this->canManageLeaves()) {
            return $requestedEmployeeId ?: Employee::query()
                ->where('user_id', auth()->id())
                ->value('id');
        }

        return Employee::query()
            ->where('user_id', auth()->id())
            ->value('id');
    }

    private function mapEmployeeSummary(?Employee $employee): ?array
    {
        if (! $employee) {
            return null;
        }

        $fullName = trim(collect([$employee->first_name ?? null, $employee->last_name ?? null])->filter()->implode(' '));

        return [
            'id' => $employee->id,
            'name' => $employee->name
                ?: $employee->full_name
                ?: ($fullName !== '' ? $fullName : null)
                ?: $employee->email
                ?: "Employee #{$employee->id}",
            'employee_id' => $employee->employee_id ?? null,
            'email' => $employee->email ?? null,
        ];
    }

    private function flattenValidationErrors(array $errors): array
    {
        return collect($errors)
            ->flatten()
            ->filter(fn ($message) => filled($message))
            ->values()
            ->all();
    }

    private function estimateApprovalProbability(
        int $employeeId,
        ?int $leaveTypeId,
        Carbon $startDate,
        Carbon $endDate,
        int $workingDays,
        array $policyViolations
    ): ?float {
        try {
            $response = $this->djangoPost('/api/ai/leave/approval-probability/', [
                'employee_id' => $employeeId,
                'leave_type_id' => $leaveTypeId,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'total_days' => $workingDays,
                'policy_violations' => $policyViolations,
            ]);

            if (! $response->successful()) {
                return null;
            }

            $probability = $response->json('data.approval_probability');

            return is_numeric($probability) ? (float) $probability : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
