<?php

namespace App\Models\Payroll;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Model;

class OtherPayment extends Model
{
    protected $fillable = [
        'employee_id',
        'payment_type',
        'amount',
        'payment_date',
        'description'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
