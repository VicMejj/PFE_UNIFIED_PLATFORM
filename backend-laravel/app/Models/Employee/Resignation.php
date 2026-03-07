<?php

namespace App\Models\Employee;

use Illuminate\Database\Eloquent\Model;

class Resignation extends Model
{
    protected $fillable = [
        'employee_id',
        'resignation_date',
        'last_working_date',
        'notice_period',
        'reason',
        'remarks'
    ];

    protected $casts = [
        'resignation_date' => 'date',
        'last_working_date' => 'date'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
