<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    protected $fillable = [
        'job_id',
        'job_stage_id',
        'applicant_name',
        'applicant_email',
        'applicant_phone',
        'resume_path',
        'application_date',
        'status',
        'score'
    ];

    protected $casts = [
        'application_date' => 'date',
        'score' => 'decimal:5,2'
    ];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function jobStage()
    {
        return $this->belongsTo(JobStage::class);
    }

    public function notes()
    {
        return $this->hasMany(JobApplicationNote::class);
    }

    public function interviewSchedules()
    {
        return $this->hasMany(InterviewSchedule::class);
    }
}
