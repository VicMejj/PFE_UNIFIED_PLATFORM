<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'account_id',
        'payee_id',
        'expense_type_id',
        'amount',
        'expense_date',
        'payment_type_id',
        'reference_number',
        'remarks'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date'
    ];

    public function account()
    {
        return $this->belongsTo(AccountList::class);
    }

    public function payee()
    {
        return $this->belongsTo(Payee::class);
    }

    public function expenseType()
    {
        return $this->belongsTo(ExpenseType::class);
    }

    public function paymentType()
    {
        return $this->belongsTo(PaymentType::class);
    }
}
