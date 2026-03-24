<?php

namespace App\Models\Insurance;

use Illuminate\Database\Eloquent\Model;

class InsurancePolicy extends Model
{
    protected $fillable = [
        'name',
        'policy_number',
        'policy_name',
        'provider_id',
        'policy_type',
        'start_date',
        'end_date',
        'premium',
        'premium_amount',
        'coverage_amount',
        'coverage_details',
        'eligibility_criteria',
        'waiting_period_days',
        'claim_settlement_days',
        'is_family_coverage',
        'is_active',
        'remarks',
        'created_by'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'premium_amount' => 'decimal:2',
        'coverage_amount' => 'decimal:2',
        'coverage_details' => 'array',
        'eligibility_criteria' => 'array',
        'is_family_coverage' => 'boolean',
        'is_active' => 'boolean'
    ];

    public function provider()
    {
        return $this->belongsTo(InsuranceProvider::class);
    }

    public function enrollments()
    {
        return $this->hasMany(InsuranceEnrollment::class);
    }

    public function coverageLimits()
    {
        return $this->hasMany(InsuranceCoverageLimit::class);
    }

    public function bordereaux()
    {
        return $this->hasMany(InsuranceBordereau::class);
    }

    public function statistics()
    {
        return $this->hasMany(InsuranceStatistic::class);
    }

    public function isActive()
    {
        return $this->is_active && now()->between($this->start_date, $this->end_date);
    }

    public function getActiveEnrollmentsCount()
    {
        return $this->enrollments()->where('status', 'active')->count();
    }

    public function getTotalClaims()
    {
        return $this->enrollments()
            ->with('claims')
            ->get()
            ->sum(fn($e) => $e->claims->count());
    }
}
