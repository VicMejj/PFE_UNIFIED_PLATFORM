<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;

class MeetingEmployee extends Model
{
    protected $fillable = [
        'meeting_id',
        'employee_id',
        'status',
        'attended'
    ];

    protected $casts = [
        'attended' => 'boolean'
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function employee()
    {
        return $this->belongsTo('App\Models\Employee\Employee');
    }
}
