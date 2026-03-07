<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class AccountList extends Model
{
    protected $fillable = [
        'account_name',
        'account_number',
        'account_type',
        'bank_name',
        'branch_name',
        'ifsc_code',
        'opening_balance',
        'is_active'
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'is_active' => 'boolean'
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
