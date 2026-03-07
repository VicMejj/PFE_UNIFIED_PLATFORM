<?php

namespace App\Models\Leave;

use App\Models\Employee\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'days_requested',
        'status',
        'reason',
        'approved_by',
        'approval_date',
        'rejection_reason'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approval_date' => 'datetime'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function approve($userId, $approvalDate = null)
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approval_date' => $approvalDate ?? now()
        ]);
    }

    public function reject($rejection_reason)
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $rejection_reason
        ]);
    }
}
