<?php

namespace App\Models\Billing;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserCoupon extends Model
{
    protected $fillable = [
        'user_id',
        'coupon_id',
        'used_date'
    ];

    protected $casts = [
        'used_date' => 'date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
}
