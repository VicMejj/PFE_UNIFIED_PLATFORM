<?php

namespace App\Http\Controllers\Api\Attendance;

use App\Http\Controllers\Api\ApiController;
use App\Models\Attendance\TimeSheet;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class TimeSheetController extends ApiController
{
    /**
     * Display timesheets
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

        if ($request->has('week')) {
            $weekStart = Carbon::parse($request->week)->startOfWeek();
            $weekEnd = $weekStart->copy()->endOfWeek();
            $query->whereBetween('date', [$weekStart, $weekEnd]);
        }

        if ($request->has('month')) {
            $query->whereYear('date', Carbon::parse($request->month)->year)
                  ->whereMonth('date', Carbon::parse($request->month)->month);
        }

        $timesheets = $query->with('employee')
                            ->orderBy('date', 'desc')
                            ->paginate(20);

        return $this->successResponse($timesheets, 'Timesheets retrieved successfully');
    }

    /**
     * Create a new timesheet entry
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date_format:Y-m-d',
            'check_in' => 'required|date_format:H:i:s',
            'check_out' => 'nullable|date_format:H:i:s',
            'work_hours' => 'nullable|numeric|min:0|max:24',
            'overtime_hours' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $timesheet = TimeSheet::create($request->all());

        return $this->successResponse($timesheet, 'Timesheet created successfully', 201);
    }

    /**
     * Display the specified timesheet
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $timesheet = TimeSheet::with('employee')->findOrFail($id);
        return $this->successResponse($timesheet, 'Timesheet retrieved successfully');
    }

    /**
     * Update the specified timesheet
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $timesheet = TimeSheet::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'check_in' => 'sometimes|date_format:H:i:s',
            'check_out' => 'nullable|date_format:H:i:s',
            'work_hours' => 'nullable|numeric|min:0|max:24',
            'overtime_hours' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
        }

        $timesheet->update($request->all());

        return $this->successResponse($timesheet, 'Timesheet updated successfully');
    }

    /**
     * Delete the specified timesheet
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $timesheet = TimeSheet::findOrFail($id);
        $timesheet->delete();

        return $this->successResponse(null, 'Timesheet deleted successfully');
    }

    /**
     * Generate summary for a period
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function generateSummary(Request $request)
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

        $summary = [
            'total_records' => $records->count(),
            'total_work_hours' => $records->sum('work_hours'),
            'total_overtime_hours' => $records->sum('overtime_hours'),
            'average_daily_hours' => $records->count() > 0 ? round($records->sum('work_hours') / $records->count(), 2) : 0,
            'by_employee' => $records->groupBy('employee_id')->map(function ($group) {
                return [
                    'work_hours' => $group->sum('work_hours'),
                    'overtime_hours' => $group->sum('overtime_hours'),
                ];
            }),
        ];

        return $this->successResponse($summary, 'Timesheet summary generated successfully');
    }
}
