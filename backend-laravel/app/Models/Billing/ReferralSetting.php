<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;

class ReferralSetting extends Model
{
    protected $fillable = [
        'referrer_commission_percentage',
        'referee_discount_percentage',
        'max_referrals_per_month',
        'is_active'
    ];

    protected $casts = [
        'referrer_commission_percentage' => 'decimal:5,2',
        'referee_discount_percentage' => 'decimal:5,2',
        'is_active' => 'boolean'
    ];
}
