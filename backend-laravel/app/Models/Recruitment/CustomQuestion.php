<?php

namespace App\Models\Recruitment;

use Illuminate\Database\Eloquent\Model;

class CustomQuestion extends Model
{
    protected $fillable = [
        'job_id',
        'question_text',
        'question_type',
        'is_required'
    ];

    protected $casts = [
        'is_required' => 'boolean'
    ];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }
}
