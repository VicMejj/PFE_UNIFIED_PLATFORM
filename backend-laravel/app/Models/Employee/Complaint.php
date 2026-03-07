<?php

namespace App\Models\Employee;

use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    protected $fillable = [
        'employee_id',
        'complaint_date',
        'complaint_type',
        'against_employee_id',
        'description',
        'status',
        'resolution',
        'resolved_date'
    ];

    protected $casts = [
        'complaint_date' => 'date',
        'resolved_date' => 'date'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function against()
    {
        return $this->belongsTo(Employee::class, 'against_employee_id');
    }
}
