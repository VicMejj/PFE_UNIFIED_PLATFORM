<?php

namespace App\Models\Insurance;

use Illuminate\Database\Eloquent\Model;

class InsuranceStatistic extends Model
{
    protected $fillable = [
        'policy_id',
        'period_month',
        'period_year',
        'total_enrollments',
        'total_claims',
        'total_claims_amount',
        'approved_claims',
        'approved_claims_amount',
        'rejected_claims',
        'pending_claims',
        'claim_ratio',
        'average_claim_amount',
        'active_dependents',
        'created_at'
    ];

    protected $casts = [
        'total_claims_amount' => 'decimal:2',
        'approved_claims_amount' => 'decimal:2',
        'claim_ratio' => 'decimal:5,2',
        'average_claim_amount' => 'decimal:2'
    ];

    public function policy()
    {
        return $this->belongsTo(InsurancePolicy::class);
    }

    public function updateStatistics($policyId, $period)
    {
        // Update statistics calculation logic
    }

    public function calculateMetrics()
    {
        // Calculate ratio and metrics
    }
}
