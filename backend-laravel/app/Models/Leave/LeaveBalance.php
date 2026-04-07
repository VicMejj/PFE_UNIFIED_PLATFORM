<?php

namespace App\Models\Leave;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'balance',
        'year',
        'opening_balance',
        'used_days',
        'closing_balance',
    ];

    protected $appends = [
        'remaining',
    ];

    protected $casts = [
        'balance' => 'integer',
        'opening_balance' => 'integer',
        'used_days' => 'integer',
        'closing_balance' => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function getRemainingAttribute(): int
    {
        if (array_key_exists('closing_balance', $this->attributes) && ! is_null($this->attributes['closing_balance'])) {
            return max(0, (int) $this->attributes['closing_balance']);
        }

        if (array_key_exists('balance', $this->attributes) && ! is_null($this->attributes['balance'])) {
            return max(0, (int) $this->attributes['balance']);
        }

        $openingBalance = (int) ($this->attributes['opening_balance'] ?? 0);
        $usedDays = (int) ($this->attributes['used_days'] ?? 0);

        return max(0, $openingBalance - $usedDays);
    }
}
