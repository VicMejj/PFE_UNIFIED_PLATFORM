<?php

namespace App\Models\Contract;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $fillable = [
        'employee_id',
        'contract_type_id',
        'contract_name',
        'start_date',
        'end_date',
        'contract_document_path',
        'status',
        'verification_token',
        'verification_code',
        'token_expires_at',
        'signing_deadline',
        'viewed_at',
        'signed_at',
        'rejected_at',
        'rejection_reason',
        'signed_ip',
        'notes'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'token_expires_at' => 'datetime',
        'signing_deadline' => 'datetime',
        'viewed_at' => 'datetime',
        'signed_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function contractType()
    {
        return $this->belongsTo(ContractType::class);
    }

    public function attachments()
    {
        return $this->hasMany(ContractAttachment::class);
    }

    public function comments()
    {
        return $this->hasMany(ContractComment::class);
    }

    public function notes()
    {
        return $this->hasMany(ContractNote::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(ContractAuditLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
