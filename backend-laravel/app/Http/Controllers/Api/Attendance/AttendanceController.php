<?php

namespace App\Http\Controllers\Api\Attendance;

use App\Http\Controllers\Api\ApiController;
use App\Models\Attendance\TimeSheet;
use App\Models\Employee;
use Illuminate\Http\Request;
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
        $query = TimeSheet::query();

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $records = $query->with('employee')->orderBy('date', 'desc')->paginate(20);

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
        $existing = TimeSheet::where('employee_id', $request->employee_id)
            ->whereDate('date', $request->date)
            ->first();

        if ($existing) {
            return $this->errorResponse('Attendance already recorded for this date', 409);
        }

        $attendance = TimeSheet::create([
            'employee_id' => $request->employee_id,
            'check_in' => $request->check_in,
            'check_out' => $request->check_out,
            'date' => $request->date,
            'status' => $request->status,
            'notes' => $request->notes,
        ]);

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

        $validator = Validator::make($request->all(), [
            'check_in' => 'sometimes|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'status' => 'sometimes|in:present,absent,late,half_day,leave',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $attendance->update($request->only(['check_in', 'check_out', 'status', 'notes']));

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
            'date_from' => 'required|date_format:Y-m-d',
            'date_to' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $query = TimeSheet::whereBetween('date', [$request->date_from, $request->date_to]);

        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        $records = $query->get();

        $stats = [
            'total_days' => $records->count(),
            'present' => $records->where('status', 'present')->count(),
            'absent' => $records->where('status', 'absent')->count(),
            'late' => $records->where('status', 'late')->count(),
            'half_day' => $records->where('status', 'half_day')->count(),
            'leave' => $records->where('status', 'leave')->count(),
        ];

        return $this->successResponse($stats, 'Attendance statistics retrieved successfully');
    }
}
