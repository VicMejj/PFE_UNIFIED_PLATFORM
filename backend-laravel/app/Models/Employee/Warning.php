<?php

namespace App\Models\Employee;

use Illuminate\Database\Eloquent\Model;

class Warning extends Model
{
    protected $fillable = [
        'employee_id',
        'warning_date',
        'warning_type',
        'reason',
        'corrective_action',
        'remarks'
    ];

    protected $casts = [
        'warning_date' => 'date'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
