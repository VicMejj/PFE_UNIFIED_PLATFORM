<?php

namespace App\Models\Contract;

use Illuminate\Database\Eloquent\Model;

class ContractComment extends Model
{
    protected $fillable = [
        'contract_id',
        'comment_by',
        'comment_text'
    ];

    protected $casts = [
        'created_at' => 'datetime'
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function commentedByUser()
    {
        return $this->belongsTo('App\Models\User', 'comment_by');
    }
}
