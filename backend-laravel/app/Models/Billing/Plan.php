<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'currency',
        'billing_cycle',
        'features',
        'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
        'is_active' => 'boolean'
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function planRequests()
    {
        return $this->hasMany(PlanRequest::class);
    }
}
