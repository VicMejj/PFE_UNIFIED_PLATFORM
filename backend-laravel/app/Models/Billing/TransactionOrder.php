<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;

class TransactionOrder extends Model
{
    protected $fillable = [
        'order_id',
        'transaction_id',
        'payment_method',
        'transaction_amount',
        'transaction_date',
        'status'
    ];

    protected $casts = [
        'transaction_amount' => 'decimal:2',
        'transaction_date' => 'datetime'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
