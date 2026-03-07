<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;

class JobApplicationNote extends Model
{
    protected $fillable = [
        'job_application_id',
        'note_text',
        'created_by'
    ];

    protected $casts = [
        'created_at' => 'datetime'
    ];

    public function jobApplication()
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo('App\Models\User', 'created_by');
    }
}
