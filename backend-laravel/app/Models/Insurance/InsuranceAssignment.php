<?php

namespace App\Models\Insurance;

use App\Models\Employee\Employee;
use App\Models\Organization\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class InsuranceAssignment extends Model
{
    protected $fillable = [
        'insurance_plan_id',
        'employee_id',
        'assigned_by',
        'assignment_type',
        'department_id',
        'effective_date',
        'end_date',
        'status',
        'dependents_covered',
        'employee_contribution',
        'notes',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'end_date' => 'date',
        'dependents_covered' => 'array',
        'employee_contribution' => 'decimal:2',
    ];

    public function plan()
    {
        return $this->belongsTo(InsurancePlan::class, 'insurance_plan_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function histories()
    {
        return $this->hasMany(InsuranceAssignmentHistory::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeByPlan($query, $planId)
    {
        return $query->where('insurance_plan_id', $planId);
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $now = now();
        
        if ($this->effective_date && $now->isBefore($this->effective_date)) {
            return false;
        }

        if ($this->end_date && $now->isAfter($this->end_date)) {
            return false;
        }

        return true;
    }

    public function isWaitingPeriodOver(): bool
    {
        if (!$this->plan) {
            return true;
        }

        $waitingDays = $this->plan->waiting_period_days;
        if ($waitingDays === 0) {
            return true;
        }

        return $this->effective_date->addDays($waitingDays)->isPast();
    }

    public function addHistory(string $action, ?User $changedBy = null, ?array $previousValues = null, ?array $newValues = null, ?string $reason = null): InsuranceAssignmentHistory
    {
        return InsuranceAssignmentHistory::create([
            'insurance_assignment_id' => $this->id,
            'changed_by' => $changedBy?->id,
            'action' => $action,
            'previous_values' => $previousValues,
            'new_values' => $newValues,
            'reason' => $reason,
        ]);
    }

    public function terminate(?string $reason = null, ?User $terminatedBy = null): void
    {
        $previousStatus = $this->status;
        $this->update(['status' => 'terminated', 'end_date' => now()]);
        
        $this->addHistory('terminated', $terminatedBy, [
            'status' => $previousStatus,
        ], [
            'status' => 'terminated',
            'end_date' => now()->toDateString(),
        ], $reason);
    }

    public function reactivate(?string $reason = null, ?User $reactivatedBy = null): void
    {
        $previousStatus = $this->status;
        $this->update(['status' => 'active']);
        
        $this->addHistory('reactivated', $reactivatedBy, [
            'status' => $previousStatus,
        ], [
            'status' => 'active',
        ], $reason);
    }

    public function getEmployeeContributionPercentageAttribute(): string
    {
        if (!$this->plan || !$this->plan->premium_amount) {
            return '0%';
        }

        $percentage = ($this->employee_contribution / $this->plan->premium_amount) * 100;
        return number_format($percentage, 1) . '%';
    }
}