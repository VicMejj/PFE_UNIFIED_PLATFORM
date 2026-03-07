<?php

namespace App\Models\Billing;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ReferralTransaction extends Model
{
    protected $fillable = [
        'referrer_user_id',
        'referee_user_id',
        'commission_amount',
        'transaction_date',
        'status'
    ];

    protected $casts = [
        'commission_amount' => 'decimal:2',
        'transaction_date' => 'date'
    ];

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    public function referee()
    {
        return $this->belongsTo(User::class, 'referee_user_id');
    }
}
