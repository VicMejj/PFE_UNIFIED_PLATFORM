<?php

namespace App\Models\Insurance;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Model;

class InsuranceEnrollment extends Model
{
    protected $fillable = [
        'employee_id',
        'policy_id',
        'enrollment_date',
        'effective_date',
        'status',
        'employee_contribution',
        'employer_contribution',
        'premium_amount',
        'enrollment_number',
        'created_by'
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'effective_date' => 'date',
        'employee_contribution' => 'decimal:2',
        'employer_contribution' => 'decimal:2',
        'premium_amount' => 'decimal:2'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function policy()
    {
        return $this->belongsTo(InsurancePolicy::class);
    }

    public function dependents()
    {
        return $this->hasMany(InsuranceDependent::class);
    }

    public function claims()
    {
        return $this->hasMany(InsuranceClaim::class);
    }

    public function premiumPayments()
    {
        return $this->hasMany(InsurancePremiumPayment::class);
    }

    public function activate()
    {
        $this->update(['status' => 'active']);
    }

    public function suspend()
    {
        $this->update(['status' => 'suspended']);
    }

    public function terminate()
    {
        $this->update(['status' => 'terminated']);
    }

    public function calculateMonthlyPremium()
    {
        return $this->premium_amount / 12;
    }
}
