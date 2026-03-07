<?php

namespace App\Models\Recruitment;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Model;

class JobOnBoard extends Model
{
    protected $fillable = [
        'job_application_id',
        'employee_id',
        'onboard_date',
        'checklist_completed',
        'training_completed',
        'training_completion_date'
    ];

    protected $casts = [
        'onboard_date' => 'date',
        'checklist_completed' => 'boolean',
        'training_completed' => 'boolean',
        'training_completion_date' => 'date'
    ];

    public function jobApplication()
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
