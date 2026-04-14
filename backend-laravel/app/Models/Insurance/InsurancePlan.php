<?php

namespace App\Models\Insurance;

use App\Models\Organization\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InsurancePlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'plan_code',
        'provider_id',
        'coverage_type',
        'insurance_type',
        'reimbursement_percentage',
        'maximum_yearly_amount',
        'deductible_amount',
        'waiting_period_days',
        'covered_services',
        'excluded_services',
        'required_documents',
        'conditions',
        'description',
        'is_active',
        'effective_date',
        'expiration_date',
        'created_by',
    ];

    protected $casts = [
        'reimbursement_percentage' => 'decimal:2',
        'maximum_yearly_amount' => 'decimal:2',
        'deductible_amount' => 'decimal:2',
        'waiting_period_days' => 'integer',
        'covered_services' => 'array',
        'excluded_services' => 'array',
        'required_documents' => 'array',
        'conditions' => 'array',
        'is_active' => 'boolean',
        'effective_date' => 'date',
        'expiration_date' => 'date',
    ];

    public function provider()
    {
        return $this->belongsTo(InsuranceProvider::class);
    }

    public function assignments()
    {
        return $this->hasMany(InsuranceAssignment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expiration_date')
                    ->orWhere('expiration_date', '>=', now());
            });
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('insurance_type', $type);
    }

    public function scopeByCoverageType($query, string $coverageType)
    {
        return $query->where('coverage_type', $coverageType);
    }

    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();
        
        if ($this->effective_date && $now->isBefore($this->effective_date)) {
            return false;
        }

        if ($this->expiration_date && $now->isAfter($this->expiration_date)) {
            return false;
        }

        return true;
    }

    public function getActiveAssignmentsCount(): int
    {
        return $this->assignments()->where('status', 'active')->count();
    }

    public function isServiceCovered(string $service): bool
    {
        $coveredServices = $this->covered_services ?? [];
        $excludedServices = $this->excluded_services ?? [];

        if (in_array($service, $excludedServices)) {
            return false;
        }

        return empty($coveredServices) || in_array($service, $coveredServices);
    }

    public function calculateReimbursement(float $amount): float
    {
        if ($amount <= $this->deductible_amount) {
            return 0;
        }

        $eligibleAmount = $amount - $this->deductible_amount;
        $reimbursement = ($eligibleAmount * $this->reimbursement_percentage) / 100;

        return min($reimbursement, $this->maximum_yearly_amount);
    }

    public function isWaitingPeriodOver(\Carbon\Carbon $enrollmentDate): bool
    {
        return $enrollmentDate->addDays($this->waiting_period_days)->isPast();
    }

    public function generatePlanCode(): string
    {
        $prefix = strtoupper(substr($this->insurance_type, 0, 3));
        $suffix = strtoupper(substr($this->coverage_type, 0, 3));
        
        return $prefix . '-' . $suffix . '-' . now()->format('Ymd') . '-' . bin2hex(random_bytes(2));
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($plan) {
            if (empty($plan->plan_code)) {
                $plan->plan_code = $plan->generatePlanCode();
            }
        });
    }
}