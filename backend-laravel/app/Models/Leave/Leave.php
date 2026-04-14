<?php

namespace App\Models\Leave;

use App\Models\Employee\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use App\Models\Leave\LeaveAttachment;

class Leave extends Model
{
    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'duration_type',
        'days_requested',
        'total_days',
        'approval_probability',
        'ai_suggestion_score',
        'status',
        'reason',
        'policy_violations',
        'approved_by',
        'approval_date',
        'rejection_reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approval_date' => 'datetime',
        'policy_violations' => 'array',
        'approval_probability' => 'float',
        'ai_suggestion_score' => 'float',
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

    public function attachments()
    {
        return $this->hasMany(LeaveAttachment::class);
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
        $updates = [
            'status' => 'approved',
            'approved_by' => $userId,
        ];

        if (Schema::hasColumn('leaves', 'approval_date')) {
            $updates['approval_date'] = $approvalDate ?? now();
        }

        $this->update($updates);
    }

    public function reject($rejection_reason)
    {
        $updates = [
            'status' => 'rejected',
        ];

        if (Schema::hasColumn('leaves', 'rejection_reason')) {
            $updates['rejection_reason'] = $rejection_reason;
        }

        $this->update($updates);
    }
}
