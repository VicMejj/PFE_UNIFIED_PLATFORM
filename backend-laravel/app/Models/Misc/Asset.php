<?php

namespace App\Models\Misc;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $fillable = [
        'asset_name',
        'asset_type',
        'purchase_date',
        'purchase_price',
        'depreciation_rate',
        'current_value',
        'status',
        'notes'
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_price' => 'decimal:2',
        'depreciation_rate' => 'decimal:5,2',
        'current_value' => 'decimal:2'
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
