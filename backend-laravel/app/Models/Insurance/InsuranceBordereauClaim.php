<?php

namespace App\Models\Insurance;

use Illuminate\Database\Eloquent\Model;

class InsuranceBordereauClaim extends Model
{
    protected $table = 'insurance_bordereau_claims';

    public $timestamps = false;

    protected $fillable = [
        'bordereau_id',
        'claim_id',
        'amount'
    ];

    protected $casts = [
        'amount' => 'decimal:2'
    ];
}
