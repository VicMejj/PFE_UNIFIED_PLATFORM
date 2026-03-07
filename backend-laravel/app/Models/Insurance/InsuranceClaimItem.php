<?php

namespace App\Models\Insurance;

use Illuminate\Database\Eloquent\Model;

class InsuranceClaimItem extends Model
{
    protected $fillable = [
        'claim_id',
        'item_type',
        'description',
        'amount',
        'quantity',
        'unit_price',
        'status',
        'remarks'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'quantity' => 'decimal:5,2',
        'unit_price' => 'decimal:2'
    ];

    public function claim()
    {
        return $this->belongsTo(InsuranceClaim::class);
    }

    public function calculateCoveredAmount()
    {
        return $this->amount;
    }
}
