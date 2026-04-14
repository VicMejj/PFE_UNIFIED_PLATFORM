<?php

namespace App\Http\Controllers\Api\Attendance;

use App\Http\Controllers\Api\ApiController;
use App\Models\Attendance\TimeSheet;
use App\Models\Employee\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AttendanceController extends ApiController
{
    /**
     * Display attendance records
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = TimeSheet::query()->with('employee');

        if (! $this->canManageAttendance()) {
            $employeeIds = Employee::query()
                ->where('user_id', auth()->id())
                ->pluck('id');

            $query->whereIn('employee_id', $employeeIds);
        } elseif ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if ($request->has('status')) {
            if (Schema::hasColumn('time_sheets', 'status')) {
                $query->where('status', $request->status);
            }
        }

        $records = $query->orderBy('date', 'desc')->paginate(20);

        return $this->successResponse($records, 'Attendance records retrieved successfully');
    }

    /**
     * Mark attendance for an employee
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'check_in' => 'required|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'date' => 'required|date_format:Y-m-d',
            'status' => 'required|in:present,absent,late,half_day,leave',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        // Check if attendance already exists for this date and employee
        $resolvedEmployeeIds = $this->resolveAccessibleEmployeeIds();
        if (! $this->canManageAttendance()) {
            if (! in_array((int) $request->employee_id, $resolvedEmployeeIds, true)) {
                return $this->forbiddenResponse('You are not allowed to record attendance for this employee.');
            }
        }

        $existing = TimeSheet::where('employee_id', $request->employee_id)
            ->whereDate('date', $request->date)
            ->first();

        if ($existing) {
            return $this->errorResponse('Attendance already recorded for this date', 409);
        }

        $hoursWorked = null;
        if ($request->filled(['check_in', 'check_out'])) {
            $in = Carbon::createFromFormat('H:i', $request->check_in);
            $out = Carbon::createFromFormat('H:i', $request->check_out);
            if ($out->greaterThan($in)) {
                $hoursWorked = round($in->diffInMinutes($out) / 60, 2);
            }
        }

        $attendanceData = [
            'employee_id' => $request->employee_id,
            'date' => $request->date,
            'check_in' => $request->check_in,
            'check_out' => $request->check_out,
            'work_hours' => $hoursWorked,
            'overtime_hours' => 0,
            'notes' => $request->notes,
        ];

        if (Schema::hasColumn('time_sheets', 'status')) {
            $attendanceData['status'] = $request->status;
        }

        $attendance = TimeSheet::create($attendanceData);

        if (! Schema::hasColumn('time_sheets', 'status')) {
            $attendance->setAttribute('status', $request->status);
        }

        return $this->successResponse($attendance, 'Attendance recorded successfully', 201);
    }

    /**
     * Display the specified attendance record
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $attendance = TimeSheet::with('employee')->findOrFail($id);
        return $this->successResponse($attendance, 'Attendance record retrieved successfully');
    }

    /**
     * Update an attendance record
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $attendance = TimeSheet::findOrFail($id);
        if (! $this->canManageAttendance() && ! in_array((int) $attendance->employee_id, $this->resolveAccessibleEmployeeIds(), true)) {
            return $this->forbiddenResponse('You are not allowed to update this attendance record.');
        }

        $validator = Validator::make($request->all(), [
            'check_in' => 'sometimes|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'status' => 'sometimes|in:present,absent,late,half_day,leave',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $updates = [];
        if ($request->filled('check_in')) {
            $updates['check_in'] = $request->check_in;
        }

        if ($request->filled('check_out') || $request->has('check_out')) {
            $updates['check_out'] = $request->check_out;
        }

        if ($request->filled('check_in') && $request->filled('check_out')) {
            $in = Carbon::createFromFormat('H:i', $request->check_in);
            $out = Carbon::createFromFormat('H:i', $request->check_out);
            if ($out->greaterThan($in)) {
                $updates['work_hours'] = round($in->diffInMinutes($out) / 60, 2);
            }
        }

        if ($request->has('status')) {
            if (Schema::hasColumn('time_sheets', 'status')) {
                $updates['status'] = $request->status;
            }
        }

        if ($request->has('notes')) {
            $updates['notes'] = $request->notes;
        }

        $attendance->update($updates);

        return $this->successResponse($attendance, 'Attendance updated successfully');
    }

    /**
     * Delete an attendance record
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $attendance = TimeSheet::findOrFail($id);
        if (! $this->canManageAttendance() && ! in_array((int) $attendance->employee_id, $this->resolveAccessibleEmployeeIds(), true)) {
            return $this->forbiddenResponse('You are not allowed to delete this attendance record.');
        }
        $attendance->delete();

        return $this->successResponse(null, 'Attendance deleted successfully');
    }

    /**
     * Get attendance statistics for a period
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function getStatistics(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'nullable|exists:employees,id',
            'date_from' => 'sometimes|date_format:Y-m-d',
            'date_to' => 'sometimes|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $dateFrom = $request->filled('date_from') ? $request->input('date_from') : now()->toDateString();
        $dateTo = $request->filled('date_to') ? $request->input('date_to') : now()->toDateString();

        $query = TimeSheet::whereBetween('date', [$dateFrom, $dateTo]);

        if (! $this->canManageAttendance()) {
            $query->whereIn('employee_id', $this->resolveAccessibleEmployeeIds());
        } elseif ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        $records = $query->get();

        $stats = [
            'total_days' => $records->count(),
            'present_today' => Schema::hasColumn('time_sheets', 'status') ? $records->where('status', 'present')->count() : 0,
            'absent_today' => Schema::hasColumn('time_sheets', 'status') ? $records->where('status', 'absent')->count() : 0,
            'late_today' => Schema::hasColumn('time_sheets', 'status') ? $records->where('status', 'late')->count() : 0,
            'on_leave_today' => Schema::hasColumn('time_sheets', 'status') ? $records->where('status', 'leave')->count() : 0,
            'half_day_today' => Schema::hasColumn('time_sheets', 'status') ? $records->where('status', 'half_day')->count() : 0,
        ];

        return $this->successResponse($stats, 'Attendance statistics retrieved successfully');
    }

    private function canManageAttendance(): bool
    {
        $user = auth()->user();
        if (! $user || ! method_exists($user, 'getRoleNames')) {
            return false;
        }

        $roles = $user->getRoleNames()->map(fn ($role) => strtolower((string) $role))->all();

        return (bool) array_intersect($roles, ['admin', 'rh', 'rh_manager', 'hr', 'manager']);
    }

    private function resolveAccessibleEmployeeIds(): array
    {
        return Employee::query()
            ->where('user_id', auth()->id())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
