<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    protected $fillable = [
        'account_id',
        'payer_id',
        'amount',
        'deposit_date',
        'payment_type_id',
        'reference_number',
        'remarks'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'deposit_date' => 'date'
    ];

    public function account()
    {
        return $this->belongsTo(AccountList::class);
    }

    public function payer()
    {
        return $this->belongsTo(Payer::class);
    }

    public function paymentType()
    {
        return $this->belongsTo(PaymentType::class);
    }
}
