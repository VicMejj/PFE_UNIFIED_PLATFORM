<?php

namespace App\Models\Attendance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceRecord extends Model
{
    use SoftDeletes;

    protected $table = 'attendance_records';

    protected $fillable = [
        'employee_id',
        'date',
        'check_in',
        'check_out',
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in' => 'datetime:H:i',
        'check_out' => 'datetime:H:i',
    ];

    /**
     * Get the employee that has this attendance record
     */
    public function employee()
    {
        return $this->belongsTo(\App\Models\Employee::class);
    }

    /**
     * Scope to get records for a specific date
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    /**
     * Scope to get records for a date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope to get present employees
     */
    public function scopePresent($query)
    {
        return $query->where('status', 'present');
    }

    /**
     * Scope to get absent employees
     */
    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }
}
