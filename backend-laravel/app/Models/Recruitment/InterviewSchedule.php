<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;

class InterviewSchedule extends Model
{
    protected $fillable = [
        'job_application_id',
        'interview_date',
        'interview_time',
        'interview_type',
        'interviewer_id',
        'location',
        'meeting_link',
        'interview_notes',
        'rating'
    ];

    protected $casts = [
        'interview_date' => 'date',
        'interview_time' => 'datetime',
        'rating' => 'decimal:5,2'
    ];

    public function jobApplication()
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function interviewer()
    {
        return $this->belongsTo('App\Models\User', 'interviewer_id');
    }
}
