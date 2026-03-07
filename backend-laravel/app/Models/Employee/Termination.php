<?php

namespace App\Models\Employee;

use Illuminate\Database\Eloquent\Model;

class Termination extends Model
{
    protected $fillable = [
        'employee_id',
        'termination_type_id',
        'termination_date',
        'reason',
        'remarks'
    ];

    protected $casts = [
        'termination_date' => 'date'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function terminationType()
    {
        return $this->belongsTo(TerminationType::class);
    }
}
