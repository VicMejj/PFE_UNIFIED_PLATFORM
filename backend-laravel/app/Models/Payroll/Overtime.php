<?php

namespace App\Models\Payroll;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Model;

class Overtime extends Model
{
    protected $fillable = [
        'employee_id',
        'overtime_date',
        'hours',
        'rate_per_hour',
        'amount',
        'payroll_month',
        'payroll_year'
    ];

    protected $casts = [
        'overtime_date' => 'date',
        'hours' => 'decimal:5,2',
        'rate_per_hour' => 'decimal:5,2',
        'amount' => 'decimal:2'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
