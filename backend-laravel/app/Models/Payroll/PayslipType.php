<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Model;

class PayslipType extends Model
{
    protected $fillable = [
        'name',
        'description'
    ];

    public function payslips()
    {
        return $this->hasMany(PaySlip::class);
    }
}
