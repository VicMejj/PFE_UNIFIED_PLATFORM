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
        'insurance_plan_id',
        'employee_id',
        'dependent_id',
        'provider_id',
        'claim_date',
        'date_filed',
        'service_date',
        'description',
        'claimed_amount',
        'total_amount',
        'reimbursement_amount',
        'reimbursement_percentage',
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
        'missing_documents',
        'created_by'
    ];

    protected $casts = [
        'claim_date' => 'date',
        'date_filed' => 'date',
        'service_date' => 'date',
        'claimed_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'reimbursement_amount' => 'decimal:2',
        'reimbursement_percentage' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'payment_date' => 'date',
        'missing_documents' => 'array',
    ];

    public function enrollment()
    {
        return $this->belongsTo(InsuranceEnrollment::class);
    }

    public function plan()
    {
        return $this->belongsTo(InsurancePlan::class, 'insurance_plan_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function dependent()
    {
        return $this->belongsTo(InsuranceDependent::class);
    }

    public function provider()
    {
        return $this->belongsTo(InsuranceProvider::class);
    }

    public function items()
    {
        return $this->hasMany(InsuranceClaimItem::class, 'claim_id');
    }

    public function documents()
    {
        return $this->hasMany(InsuranceClaimDocument::class, 'claim_id');
    }

    public function history()
    {
        return $this->hasMany(InsuranceClaimHistory::class, 'claim_id');
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

    public function scopeMissingDocs($query)
    {
        return $query->where('status', 'missing_documents');
    }

    const STATUS_SENT_TO_PROVIDER = 'sent_to_provider';

    public function scopeSentToProvider($query)
    {
        return $query->where('status', self::STATUS_SENT_TO_PROVIDER);
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

    public function approve($userId, $approvedAmount = null)
    {
        if ($approvedAmount === null) {
            $approvedAmount = $this->calculateInsuranceReimbursement();
        }

        $employeeReimbursement = $approvedAmount * 0.90;
        $companyDiscount = $approvedAmount * 0.10;

        $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
            'approved_amount' => $approvedAmount,
            'reimbursement_amount' => $employeeReimbursement,
            'reimbursement_percentage' => 90,
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

    /**
     * Check for missing documents based on the assigned plan
     */
    public function getMissingDocuments(): array
    {
        if (!$this->plan || empty($this->plan->required_documents)) {
            return [];
        }

        $uploadedDocTypes = $this->documents->pluck('document_type')->map(fn($t) => strtolower($t))->toArray();
        $missing = [];

        foreach ($this->plan->required_documents as $requiredDoc) {
            $requiredLower = strtolower($requiredDoc);
            $found = false;
            foreach ($uploadedDocTypes as $uploaded) {
                if (str_contains($uploaded, $requiredLower)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $missing[] = $requiredDoc;
            }
        }

        return $missing;
    }

    /**
     * Calculate reimbursement based on the plan's percentage and deductible
     */
    public function calculateInsuranceReimbursement(): float
    {
        if (!$this->plan) {
            return $this->claimed_amount;
        }

        $totalAmount = $this->claimed_amount > 0 ? $this->claimed_amount : $this->calculateTotalAmount();
        return $this->plan->calculateReimbursement($totalAmount);
    }

    public function calculateTotalAmount()
    {
        return $this->items->sum('amount');
    }

    public function canBeEdited()
    {
        return in_array($this->status, ['pending', 'reviewed', 'missing_documents']);
    }

    public function canBeApproved()
    {
        return in_array($this->status, ['reviewed', 'pending']) && empty($this->getMissingDocuments());
    }

    public function generateClaimNumber()
    {
        return 'CLM-' . date('Y') . '-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }
}
