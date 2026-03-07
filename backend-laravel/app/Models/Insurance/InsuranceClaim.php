<?php

namespace App\Models\Insurance;

use App\Models\Employee\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class InsuranceClaim extends Model
{
    protected $table = 'insurance_claims';

    protected $fillable = [
        'claim_number',
        'enrollment_id',
        'employee_id',
        'dependent_id',
        'provider_id',
        'claim_date',
        'service_date',
        'description',
        'claimed_amount',
        'approved_amount',
        'patient_type',
        'service_type',
        'treatment_details',
        'status',
        'reviewed_at',
        'reviewed_by',
        'review_comments',
        'approved_at',
        'approved_by',
        'approval_comments',
        'payment_date',
        'payment_reference',
        'rejection_reason',
        'created_by'
    ];

    protected $casts = [
        'claim_date' => 'date',
        'service_date' => 'date',
        'claimed_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'payment_date' => 'date'
    ];

    public function enrollment()
    {
        return $this->belongsTo(InsuranceEnrollment::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function dependent()
    {
        return $this->belongsTo(InsuranceDependent::class)->nullable();
    }

    public function provider()
    {
        return $this->belongsTo(InsuranceProvider::class);
    }

    public function items()
    {
        return $this->hasMany(InsuranceClaimItem::class);
    }

    public function documents()
    {
        return $this->hasMany(InsuranceClaimDocument::class);
    }

    public function history()
    {
        return $this->hasMany(InsuranceClaimHistory::class);
    }

    public function bordereaux()
    {
        return $this->belongsToMany(
            InsuranceBordereau::class,
            'insurance_bordereau_claims',
            'claim_id',
            'bordereau_id'
        );
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
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

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('claim_date', [$startDate, $endDate]);
    }

    public function review($userId, $status, $comment)
    {
        $this->update([
            'status' => $status,
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
            'review_comments' => $comment
        ]);
    }

    public function approve($userId, $approvedAmount)
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
            'approved_amount' => $approvedAmount
        ]);
    }

    public function reject($userId, $reason)
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $userId,
            'approval_comments' => $reason,
            'approved_at' => now()
        ]);
    }

    public function markAsPaid($paymentDate, $reference)
    {
        $this->update([
            'status' => 'paid',
            'payment_date' => $paymentDate,
            'payment_reference' => $reference
        ]);
    }

    public function calculateTotalAmount()
    {
        return $this->items->sum('amount');
    }

    public function canBeEdited()
    {
        return in_array($this->status, ['pending', 'reviewed']);
    }

    public function canBeApproved()
    {
        return $this->status === 'reviewed';
    }

    public function generateClaimNumber()
    {
        return 'CLM-' . date('Y') . '-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }
}
