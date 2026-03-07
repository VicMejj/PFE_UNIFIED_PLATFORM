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
        'notes'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date'
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

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
