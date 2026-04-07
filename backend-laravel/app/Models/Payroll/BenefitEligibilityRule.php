<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Model;

class BenefitEligibilityRule extends Model
{
    protected $fillable = [
        'allowance_option_id',
        'rule_type',
        'threshold',
        'weight',
    ];

    public function allowanceOption()
    {
        return $this->belongsTo(AllowanceOption::class);
    }
}
