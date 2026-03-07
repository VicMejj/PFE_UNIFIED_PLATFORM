<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Model;

class LoanOption extends Model
{
    protected $fillable = [
        'name',
        'description',
        'rate_of_interest',
        'is_active'
    ];

    protected $casts = [
        'rate_of_interest' => 'decimal:5,2',
        'is_active' => 'boolean'
    ];

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }
}
