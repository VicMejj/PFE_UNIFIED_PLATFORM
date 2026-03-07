<?php

namespace App\Models\Attendance;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Model;

class TimeSheet extends Model
{
    protected $fillable = [
        'employee_id',
        'timesheet_date',
        'project_name',
        'task_description',
        'hours_worked',
        'status',
        'remarks'
    ];

    protected $casts = [
        'timesheet_date' => 'date',
        'hours_worked' => 'decimal:5,2'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeByMonth($query, $month, $year)
    {
        return $query->whereYear('timesheet_date', $year)
                     ->whereMonth('timesheet_date', $month);
    }
}
