<?php

namespace App\Models\Attendance;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Model;

class AttendanceEmployee extends Model
{
    protected $fillable = [
        'employee_id',
        'attendance_date',
        'check_in_time',
        'check_out_time',
        'status',
        'remarks'
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopePresent($query)
    {
        return $query->where('status', 'present');
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }

    public function scopeByDate($query, $date)
    {
        return $query->where('attendance_date', $date);
    }
}
