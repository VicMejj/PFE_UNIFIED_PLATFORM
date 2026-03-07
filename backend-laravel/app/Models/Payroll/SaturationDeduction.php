<?php

namespace App\Models\Payroll;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Model;

class SaturationDeduction extends Model
{
    protected $fillable = [
        'employee_id',
        'deduction_option_id',
        'payroll_month',
        'payroll_year',
        'amount'
    ];

    protected $casts = [
        'amount' => 'decimal:2'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function deductionOption()
    {
        return $this->belongsTo(DeductionOption::class);
    }
}
