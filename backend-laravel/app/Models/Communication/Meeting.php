<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    protected $fillable = [
        'title',
        'description',
        'meeting_date',
        'start_time',
        'end_time',
        'location',
        'meeting_link',
        'is_active'
    ];

    protected $casts = [
        'meeting_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_active' => 'boolean'
    ];

    public function employees()
    {
        return $this->belongsToMany(
            'App\Models\Employee\Employee',
            'meeting_employees',
            'meeting_id',
            'employee_id'
        );
    }
}
