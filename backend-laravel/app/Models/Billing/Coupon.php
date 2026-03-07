<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'coupon_code',
        'discount_type',
        'discount_value',
        'max_usage',
        'used_count',
        'start_date',
        'end_date',
        'is_active'
    ];

    protected $casts = [
        'discount_value' => 'decimal:5,2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean'
    ];

    public function userCoupons()
    {
        return $this->hasMany(UserCoupon::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
