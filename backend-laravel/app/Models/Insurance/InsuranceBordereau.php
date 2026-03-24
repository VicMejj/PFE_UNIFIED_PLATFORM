<?php

namespace App\Models\Insurance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class InsuranceBordereau extends Model
{
    protected $table = 'insurance_bordereaux';

    protected $fillable = [
        'bordereau_number',
        'policy_id',
        'bordereau_date',
        'period_from',
        'period_to',
        'total_claims_count',
        'total_claimed_amount',
        'total_approved_amount',
        'status',
        'prepared_by',
        'prepared_at',
        'validated_by',
        'validated_at',
        'payment_date',
        'payment_reference',
        'remarks'
    ];

    protected $casts = [
        'bordereau_date' => 'date',
        'period_from' => 'date',
        'period_to' => 'date',
        'total_claimed_amount' => 'decimal:2',
        'total_approved_amount' => 'decimal:2',
        'prepared_at' => 'datetime',
        'validated_at' => 'datetime',
        'payment_date' => 'date'
    ];

    public function policy()
    {
        return $this->belongsTo(InsurancePolicy::class);
    }

    public function claims()
    {
        return $this->belongsToMany(
            InsuranceClaim::class,
            'insurance_bordereau_claims',
            'bordereau_id',
            'claim_id'
        )->withPivot('amount');
    }

    public function preparedBy()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeValidated($query)
    {
        return $query->where('status', 'validated');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function addClaims($claimIds)
    {
        $this->claims()->attach($claimIds);
    }

    public function removeClaim($claimId)
    {
        $this->claims()->detach($claimId);
    }

    public function calculateTotals()
    {
        $this->update([
            'total_claims_count' => $this->claims->count(),
            'total_claimed_amount' => $this->claims->sum('claimed_amount')
        ]);
    }

    public function submit($userId)
    {
        $this->update([
            'status' => 'submitted',
            'prepared_by' => $userId,
            'prepared_at' => now()
        ]);
    }

    public function validate($userId)
    {
        $this->update([
            'status' => 'validated',
            'validated_by' => $userId,
            'validated_at' => now()
        ]);
    }

    public function markAsPaid($date, $reference)
    {
        $this->update([
            'status' => 'paid',
            'payment_date' => $date,
            'payment_reference' => $reference
        ]);
    }

    public function generateBordereauNumber()
    {
        return 'BRD-' . date('Y') . '-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }
}
