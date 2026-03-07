<?php

namespace App\Models\Insurance;

use Illuminate\Database\Eloquent\Model;

class InsurancePremiumPayment extends Model
{
    protected $fillable = [
        'enrollment_id',
        'payment_month',
        'payment_year',
        'employee_contribution',
        'employer_contribution',
        'total_amount',
        'payment_date',
        'payment_reference',
        'status'
    ];

    protected $casts = [
        'employee_contribution' => 'decimal:2',
        'employer_contribution' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'payment_date' => 'date'
    ];

    public function enrollment()
    {
        return $this->belongsTo(InsuranceEnrollment::class);
    }

    public function markAsPaid($date, $reference)
    {
        $this->update([
            'status' => 'paid',
            'payment_date' => $date,
            'payment_reference' => $reference
        ]);
    }
}
