<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class PaymentType extends Model
{
    protected $fillable = [
        'name',
        'description'
    ];

    public function deposits()
    {
        return $this->hasMany(Deposit::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
