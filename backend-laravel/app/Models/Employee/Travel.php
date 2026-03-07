<?php

namespace App\Models\Employee;

use Illuminate\Database\Eloquent\Model;

class Travel extends Model
{
    protected $fillable = [
        'employee_id',
        'travel_request_date',
        'from_location',
        'to_location',
        'start_date',
        'end_date',
        'purpose',
        'transport_type',
        'status',
        'remarks'
    ];

    protected $casts = [
        'travel_request_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
