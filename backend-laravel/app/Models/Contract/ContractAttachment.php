<?php

namespace App\Models\Contract;

use Illuminate\Database\Eloquent\Model;

class ContractAttachment extends Model
{
    protected $fillable = [
        'contract_id',
        'file_name',
        'file_path',
        'file_type'
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
}
