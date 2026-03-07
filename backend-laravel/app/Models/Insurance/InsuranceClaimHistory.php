<?php

namespace App\Models\Insurance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class InsuranceClaimHistory extends Model
{
    protected $fillable = [
        'claim_id',
        'status',
        'changed_by',
        'changed_at',
        'remarks'
    ];

    protected $casts = [
        'changed_at' => 'datetime'
    ];

    public function claim()
    {
        return $this->belongsTo(InsuranceClaim::class);
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
