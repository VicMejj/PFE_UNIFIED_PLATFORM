<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Model;

class AllowanceOption extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function allowances()
    {
        return $this->hasMany(Allowance::class);
    }

    public function benefitRecommendations()
    {
        return $this->hasMany(EmployeeBenefitRecommendation::class);
    }
}
