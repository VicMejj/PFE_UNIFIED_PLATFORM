<?php

namespace App\Models\Contract;

use Illuminate\Database\Eloquent\Model;

class ContractAuditLog extends Model
{
    protected $table = 'contract_audit_logs';

    protected $fillable = [
        'contract_id',
        'actor_id',
        'action',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
}
