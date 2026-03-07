<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;

class EventEmployee extends Model
{
    protected $fillable = [
        'event_id',
        'employee_id',
        'status',
        'attended'
    ];

    protected $casts = [
        'attended' => 'boolean'
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function employee()
    {
        return $this->belongsTo('App\Models\Employee\Employee');
    }
}
