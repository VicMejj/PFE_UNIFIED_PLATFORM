<?php

namespace App\Models\Performance;

use App\Models\Employee\Employee;
use Illuminate\Database\Eloquent\Model;

class GoalTracking extends Model
{
    protected $fillable = [
        'employee_id',
        'goal_type_id',
        'description',
        'target_date',
        'progress_percentage',
        'status'
    ];

    protected $casts = [
        'target_date' => 'date',
        'progress_percentage' => 'decimal:5,2'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function goalType()
    {
        return $this->belongsTo(GoalType::class);
    }
}
