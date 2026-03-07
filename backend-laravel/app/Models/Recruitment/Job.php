<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    protected $fillable = [
        'job_title',
        'job_category_id',
        'description',
        'required_experience',
        'required_qualifications',
        'salary_from',
        'salary_to',
        'positions_available',
        'job_location',
        'posted_date',
        'application_deadline',
        'status'
    ];

    protected $casts = [
        'salary_from' => 'decimal:2',
        'salary_to' => 'decimal:2',
        'posted_date' => 'date',
        'application_deadline' => 'date'
    ];

    public function jobCategory()
    {
        return $this->belongsTo(JobCategory::class);
    }

    public function applications()
    {
        return $this->hasMany(JobApplication::class);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }
}
