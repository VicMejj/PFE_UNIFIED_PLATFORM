<?php

namespace App\Models\Payroll;

use App\Models\Employee\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Allowance extends Model
{
    protected $fillable = [
        'employee_id',
        'allowance_option_id',
        'amount',
        'start_date',
        'end_date',
        'status',
        'claimed',
        'claimed_at',
        'claimed_by',
        'status_changed_at',
        'status_changed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'claimed' => 'boolean',
        'claimed_at' => 'datetime',
        'status_changed_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function allowanceOption()
    {
        return $this->belongsTo(AllowanceOption::class);
    }

    public function claimedByUser()
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }

    public function statusChangedByUser()
    {
        return $this->belongsTo(User::class, 'status_changed_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeClaimable($query)
    {
        return $query->where('status', 'active')->where('claimed', false);
    }

    public function scopeClaimed($query)
    {
        return $query->where('claimed', true);
    }

    public function isClaimable(): bool
    {
        return $this->status === 'active' && !$this->claimed;
    }

    public function canActivate(): bool
    {
        return $this->status === 'pending';
    }

    public function canDeactivate(): bool
    {
        return $this->status === 'active';
    }
}
