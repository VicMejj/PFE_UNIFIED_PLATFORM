<?php

namespace App\Models\Employee;

use Illuminate\Database\Eloquent\Model;

class EmployeeDocument extends Model
{
    protected $fillable = [
        'employee_id',
        'document_id',
        'file_path',
        'file_name',
        'expiry_date'
    ];

    protected $casts = [
        'expiry_date' => 'date'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
