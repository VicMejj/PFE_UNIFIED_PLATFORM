<?php

namespace App\Models\Employee;

use Illuminate\Database\Eloquent\Model;

class EmployeeScore extends Model
{
    protected $fillable = [
        'employee_id',
        'overall_score',
        'attendance_score',
        'performance_score',
        'discipline_score',
        'seniority_score',
        'engagement_score',
        'score_tier',
        'score_factors',
        'improvement_suggestions',
        'last_calculated_at',
    ];

    protected $casts = [
        'overall_score' => 'float',
        'attendance_score' => 'float',
        'performance_score' => 'float',
        'discipline_score' => 'float',
        'seniority_score' => 'float',
        'engagement_score' => 'float',
        'score_factors' => 'array',
        'improvement_suggestions' => 'array',
        'last_calculated_at' => 'datetime',
    ];

    public const TIER_EXCELLENT = 'excellent';
    public const TIER_GOOD = 'good';
    public const TIER_MEDIUM = 'medium';
    public const TIER_RISK = 'risk';

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function histories()
    {
        return $this->hasMany(EmployeeScoreHistory::class);
    }

    public static function calculateTier(float $score): string
    {
        if ($score >= 85) return self::TIER_EXCELLENT;
        if ($score >= 70) return self::TIER_GOOD;
        if ($score >= 50) return self::TIER_MEDIUM;
        return self::TIER_RISK;
    }

    public function isEligibleFor(float $minimumScore): bool
    {
        return $this->overall_score >= $minimumScore;
    }

    public function scopeExcellent($query)
    {
        return $query->where('score_tier', self::TIER_EXCELLENT);
    }

    public function scopeGood($query)
    {
        return $query->where('score_tier', self::TIER_GOOD);
    }

    public function scopeAtRisk($query)
    {
        return $query->where('score_tier', self::TIER_RISK);
    }

    public function scopeAboveScore($query, float $score)
    {
        return $query->where('overall_score', '>=', $score);
    }
}