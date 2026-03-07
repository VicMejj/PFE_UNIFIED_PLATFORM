<?php

namespace App\Models\Payroll;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Model;

class Allowance extends Model
{
    protected $fillable = [
        'employee_id',
        'allowance_option_id',
        'amount',
        'start_date',
        'end_date',
        'status'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function allowanceOption()
    {
        return $this->belongsTo(AllowanceOption::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
