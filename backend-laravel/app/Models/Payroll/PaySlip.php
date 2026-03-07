<?php

namespace App\Models\Payroll;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Model;

class PaySlip extends Model
{
    protected $fillable = [
        'employee_id',
        'payslip_type_id',
        'payroll_month',
        'payroll_year',
        'basic_salary',
        'allowance',
        'commission',
        'loan',
        'saturation_deduction',
        'other_payment',
        'overtime',
        'gross_salary',
        'deductions',
        'net_payable',
        'payment_date',
        'status',
        'notes'
    ];

    protected $casts = [
        'allowance' => 'array',
        'commission' => 'array',
        'loan' => 'array',
        'saturation_deduction' => 'array',
        'other_payment' => 'array',
        'overtime' => 'array',
        'basic_salary' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'deductions' => 'decimal:2',
        'net_payable' => 'decimal:2',
        'payment_date' => 'date'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function payslipType()
    {
        return $this->belongsTo(PayslipType::class);
    }

    public function calculateNetPayable()
    {
        return $this->gross_salary - $this->deductions;
    }

    public function scopeByMonth($query, $month, $year)
    {
        return $query->where('payroll_month', $month)->where('payroll_year', $year);
    }
}
