<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;

class AdminPaymentSetting extends Model
{
    protected $fillable = [
        'stripe_key',
        'stripe_secret',
        'paypal_client_id',
        'paypal_secret',
        'is_stripe_active',
        'is_paypal_active'
    ];

    protected $casts = [
        'is_stripe_active' => 'boolean',
        'is_paypal_active' => 'boolean'
    ];

    protected $hidden = ['stripe_secret', 'paypal_secret'];
}
