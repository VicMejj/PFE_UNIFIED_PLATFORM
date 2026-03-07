<?php

namespace App\Models\Document;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Model;

class ExperienceCertificate extends Model
{
    protected $fillable = [
        'employee_id',
        'certificate_date',
        'document_path',
        'status'
    ];

    protected $casts = [
        'certificate_date' => 'date'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
