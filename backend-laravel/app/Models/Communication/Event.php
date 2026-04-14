<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'title',
        'description',
        'event_date',
        'start_time',
        'end_time',
        'location',
        'type',
        'color',
        'is_active',
        'user_id',
    ];

    protected $casts = [
        'event_date' => 'date:Y-m-d',
        'is_active'  => 'boolean',
    ];

    public function employees()
    {
        return $this->belongsToMany(
            'App\Models\Employee\Employee',
            'event_employees',
            'event_id',
            'employee_id'
        );
    }
}
