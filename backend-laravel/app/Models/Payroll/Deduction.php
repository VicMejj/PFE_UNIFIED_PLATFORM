<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deduction extends Model
{
    use SoftDeletes;

    protected $table = 'deductions';

    protected $fillable = [
        'employee_id',
        'type',
        'name',
        'amount',
        'frequency',
        'effective_date',
        'end_date',
        'description',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'effective_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the employee for this deduction
     */
    public function employee()
    {
        return $this->belongsTo(\App\Models\Employee::class);
    }

    /**
     * Scope to get active deductions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                     ->where('effective_date', '<=', now())
                     ->where(function ($q) {
                         $q->whereNull('end_date')
                           ->orWhere('end_date', '>=', now());
                     });
    }

    /**
     * Scope to get deductions for an employee
     */
    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope to get deductions by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Check if deduction is currently active
     */
    public function getIsCurrentlyActiveAttribute()
    {
        return $this->is_active 
            && $this->effective_date <= now()
            && (!$this->end_date || $this->end_date >= now());
    }
}
