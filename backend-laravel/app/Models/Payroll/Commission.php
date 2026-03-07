<?php

namespace App\Models\Payroll;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Model;

class Commission extends Model
{
    protected $fillable = [
        'employee_id',
        'commission_month',
        'commission_year',
        'amount',
        'status',
        'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeByMonth($query, $month, $year)
    {
        return $query->where('commission_month', $month)->where('commission_year', $year);
    }
}
