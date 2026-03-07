<?php

namespace App\Models\Payroll;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    protected $fillable = [
        'employee_id',
        'loan_option_id',
        'principal_amount',
        'rate_of_interest',
        'approved_amount',
        'loan_start_date',
        'loan_end_date',
        'emi_amount',
        'remaining_balance',
        'status',
        'notes'
    ];

    protected $casts = [
        'principal_amount' => 'decimal:2',
        'rate_of_interest' => 'decimal:5,2',
        'approved_amount' => 'decimal:2',
        'loan_start_date' => 'date',
        'loan_end_date' => 'date',
        'emi_amount' => 'decimal:2',
        'remaining_balance' => 'decimal:2'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function loanOption()
    {
        return $this->belongsTo(LoanOption::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
