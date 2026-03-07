<?php

namespace App\Models\Insurance;

use Illuminate\Database\Eloquent\Model;

class InsuranceCoverageLimit extends Model
{
    protected $fillable = [
        'policy_id',
        'coverage_type',
        'annual_limit',
        'lifetime_limit',
        'deductible_amount',
        'co_insurance_percentage',
        'is_active'
    ];

    protected $casts = [
        'annual_limit' => 'decimal:2',
        'lifetime_limit' => 'decimal:2',
        'deductible_amount' => 'decimal:2',
        'co_insurance_percentage' => 'decimal:5,2',
        'is_active' => 'boolean'
    ];

    public function policy()
    {
        return $this->belongsTo(InsurancePolicy::class);
    }

    public function checkLimit($amount, $employeeId, $year)
    {
        return $amount <= $this->annual_limit;
    }

    public function getRemainingLimit($employeeId, $year)
    {
        return $this->annual_limit;
    }
}
