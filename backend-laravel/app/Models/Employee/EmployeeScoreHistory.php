<?php

namespace App\Models\Employee;

use Illuminate\Database\Eloquent\Model;

class EmployeeScoreHistory extends Model
{
    protected $fillable = [
        'employee_id',
        'employee_score_id',
        'overall_score',
        'score_tier',
        'change_reason',
        'previous_scores',
    ];

    protected $casts = [
        'overall_score' => 'decimal:2',
        'previous_scores' => 'array',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function score()
    {
        return $this->belongsTo(EmployeeScore::class, 'employee_score_id');
    }
}