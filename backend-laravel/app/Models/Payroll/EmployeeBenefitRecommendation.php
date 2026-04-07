<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Model;

class EmployeeBenefitRecommendation extends Model
{
    protected $fillable = [
        'employee_id',
        'allowance_option_id',
        'score',
        'status',
        'gap_actions',
        'estimated_months_to_qualify',
    ];

    protected $casts = [
        'gap_actions' => 'array',
    ];

    public function allowanceOption()
    {
        return $this->belongsTo(AllowanceOption::class);
    }
}
