<?php

namespace App\Models\Employee;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class EmployeeScoreNote extends Model
{
    protected $table = 'employee_score_notes';

    protected $fillable = [
        'employee_id',
        'author_id',
        'author_type',
        'note_type',
        'attendance_note',
        'discipline_note',
        'performance_note',
        'general_note',
        'score_adjustment',
        'is_ai_generated',
        'ai_analysis',
        'period_start',
        'period_end',
    ];

    protected $casts = [
        'attendance_note' => 'array',
        'discipline_note' => 'array',
        'performance_note' => 'array',
        'score_adjustment' => 'float',
        'is_ai_generated' => 'boolean',
        'ai_analysis' => 'array',
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    public const TYPE_MONTHLY = 'monthly';
    public const TYPE_QUARTERLY = 'quarterly';
    public const TYPE_ANNUAL = 'annual';
    public const TYPE_ADHOC = 'adhoc';

    public const TYPE_RATER_HR = 'hr';
    public const TYPE_RATER_MANAGER = 'manager';
    public const TYPE_RATER_DEPT_MANAGER = 'dept_manager';

public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }
    
    public function scopeByAuthorType($query, string $authorType)
    {
        return $query->where('author_type', $authorType);
    }

    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderByDesc('created_at')->limit($limit);
    }

    public function getAttendeeNotes(): array
    {
        return $this->attendance_note ?? [];
    }

    public function getDisciplineNotes(): array
    {
        return $this->discipline_note ?? [];
    }

    public function getPerformanceNotes(): array
    {
        return $this->performance_note ?? [];
    }
}
