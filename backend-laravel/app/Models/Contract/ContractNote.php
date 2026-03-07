<?php

namespace App\Models\Contract;

use Illuminate\Database\Eloquent\Model;

class ContractNote extends Model
{
    protected $fillable = [
        'contract_id',
        'note_text',
        'created_by'
    ];

    protected $casts = [
        'created_at' => 'datetime'
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo('App\Models\User', 'created_by');
    }
}
