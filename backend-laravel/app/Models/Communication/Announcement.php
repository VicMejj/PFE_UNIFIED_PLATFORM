<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'description',
        'announcement_date',
        'is_active'
    ];

    protected $casts = [
        'announcement_date' => 'datetime',
        'is_active' => 'boolean'
    ];

    public function employees()
    {
        return $this->belongsToMany(
            'App\Models\Employee\Employee',
            'announcement_employees',
            'announcement_id',
            'employee_id'
        );
    }
}
