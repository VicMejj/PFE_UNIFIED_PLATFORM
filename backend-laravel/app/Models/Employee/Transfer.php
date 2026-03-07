<?php

namespace App\Models\Employee;

use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    protected $fillable = [
        'employee_id',
        'from_branch_id',
        'to_branch_id',
        'from_department_id',
        'to_department_id',
        'transfer_date',
        'reason',
        'remarks'
    ];

    protected $casts = [
        'transfer_date' => 'date'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
