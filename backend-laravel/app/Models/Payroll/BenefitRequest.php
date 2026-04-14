<?php

namespace App\Models\Payroll;

use App\Models\Employee\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class BenefitRequest extends Model
{
    protected $fillable = [
        'employee_id',
        'allowance_option_id',
        'request_number',
        'status',
        'requested_amount',
        'approved_amount',
        'reason',
        'supporting_documents',
        'submitted_by',
        'reviewed_by',
        'approved_by',
        'reviewed_at',
        'approved_at',
        'delivered_at',
        'review_comments',
        'approval_comments',
        'rejection_reason',
        'workflow_history',
        'is_auto_approved',
        'auto_approval_reason',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'supporting_documents' => 'array',
        'workflow_history' => 'array',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'delivered_at' => 'datetime',
        'is_auto_approved' => 'boolean',
    ];

    // Status constants
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function allowanceOption()
    {
        return $this->belongsTo(AllowanceOption::class);
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function documents()
    {
        return $this->hasMany(BenefitRequestDocument::class);
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', self::STATUS_SUBMITTED);
    }

    public function scopeUnderReview($query)
    {
        return $query->where('status', self::STATUS_UNDER_REVIEW);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', self::STATUS_DELIVERED);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [
            self::STATUS_SUBMITTED,
            self::STATUS_UNDER_REVIEW,
        ]);
    }

    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByAllowance($query, $allowanceOptionId)
    {
        return $query->where('allowance_option_id', $allowanceOptionId);
    }

    public function isPending(): bool
    {
        return in_array($this->status, [self::STATUS_SUBMITTED, self::STATUS_UNDER_REVIEW]);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isDelivered(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    public function canBeEdited(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function canBeApproved(): bool
    {
        return in_array($this->status, [self::STATUS_SUBMITTED, self::STATUS_UNDER_REVIEW]);
    }

    public function submit(?User $submittedBy = null): void
    {
        $this->update([
            'status' => self::STATUS_SUBMITTED,
            'submitted_by' => $submittedBy?->id,
        ]);

        $this->addWorkflowHistory('submitted', 'Request submitted for approval');
    }

    public function startReview(?User $reviewedBy = null): void
    {
        $this->update([
            'status' => self::STATUS_UNDER_REVIEW,
            'reviewed_by' => $reviewedBy?->id,
            'reviewed_at' => now(),
        ]);

        $this->addWorkflowHistory('under_review', 'Request is under review');
    }

    public function approve(float $approvedAmount, ?User $approvedBy = null, ?string $comments = null): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_amount' => $approvedAmount,
            'approved_by' => $approvedBy?->id,
            'approved_at' => now(),
            'approval_comments' => $comments,
        ]);

        $this->addWorkflowHistory('approved', "Approved with amount: {$approvedAmount}", $comments);
    }

    public function reject(?User $rejectedBy = null, ?string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $rejectedBy?->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $this->addWorkflowHistory('rejected', 'Request rejected', $reason);
    }

    public function deliver(): void
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);

        $this->addWorkflowHistory('delivered', 'Benefit delivered to employee');
    }

    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);

        $this->addWorkflowHistory('cancelled', 'Request cancelled');
    }

    public function markAsAutoApproved(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'is_auto_approved' => true,
            'auto_approval_reason' => $reason,
            'approved_at' => now(),
        ]);

        $this->addWorkflowHistory('auto_approved', "Auto-approved: {$reason}");
    }

    protected function addWorkflowHistory(string $action, string $description, ?string $details = null): void
    {
        $history = $this->workflow_history ?? [];
        $history[] = [
            'action' => $action,
            'description' => $description,
            'details' => $details,
            'timestamp' => now()->toIso8601String(),
            'user_id' => auth()->id(),
        ];

        $this->update(['workflow_history' => $history]);
    }

    public function generateRequestNumber(): string
    {
        return 'BEN-' . date('Y') . '-' . str_pad($this->id ?? 0, 6, '0', STR_PAD_LEFT);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($request) {
            if (empty($request->request_number)) {
                $request->request_number = $request->generateRequestNumber();
            }
        });
    }
}