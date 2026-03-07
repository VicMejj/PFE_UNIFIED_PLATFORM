<?php

namespace App\Models\Misc;

use Illuminate\Database\Eloquent\Model;

class ZoomMeeting extends Model
{
    protected $fillable = [
        'meeting_id',
        'topic',
        'description',
        'meeting_date',
        'start_time',
        'duration',
        'meeting_link',
        'status'
    ];

    protected $casts = [
        'meeting_date' => 'date',
        'start_time' => 'datetime',
        'duration' => 'integer'
    ];

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'upcoming');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
