<?php

namespace App\Models\Billing;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'plan_id',
        'order_number',
        'amount',
        'currency',
        'status',
        'payment_date',
        'renewal_date'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'renewal_date' => 'date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function transactions()
    {
        return $this->hasMany(TransactionOrder::class);
    }
}
