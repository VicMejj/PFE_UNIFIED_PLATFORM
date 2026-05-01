<?php

namespace App\Services;

use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeScore;
use App\Models\Employee\EmployeeScoreNote;
use App\Models\User;

class EmployeeScoreNoteService
{
    public function createNote(
        Employee $employee,
        User $author,
        array $noteData
    ): EmployeeScoreNote {
        $authorType = $this->determineAuthorType($author);

        $note = EmployeeScoreNote::create([
            'employee_id' => $employee->id,
            'author_id' => $author->id,
            'author_type' => $authorType,
            'note_type' => $noteData['note_type'] ?? EmployeeScoreNote::TYPE_ADHOC,
            'attendance_note' => $noteData['attendance_note'] ?? null,
            'discipline_note' => $noteData['discipline_note'] ?? null,
            'performance_note' => $noteData['performance_note'] ?? null,
            'general_note' => $noteData['general_note'] ?? null,
            'score_adjustment' => $noteData['score_adjustment'] ?? 0,
            'period_start' => $noteData['period_start'] ?? null,
            'period_end' => $noteData['period_end'] ?? null,
        ]);

        $this->recalculateEmployeeScore($employee);

        return $note;
    }

    public function getNotesForEmployee(
        Employee $employee,
        ?string $authorType = null,
        int $limit = 20
    ) {
        $query = EmployeeScoreNote::forEmployee($employee->id)
            ->with('author');

        if ($authorType) {
            $query->byAuthorType($authorType);
        }

        return $query->recent($limit)->get();
    }

    public function getNotesByEmployeeForManager(
        User $manager,
        int $limit = 20
    ) {
        $managerDeptId = $this->getManagerDepartmentId($manager);
        
        if (!$managerDeptId) {
            return collect();
        }

        $employeeIds = Employee::where('department_id', $managerDeptId)
            ->where('is_active', true)
            ->pluck('id');

        return EmployeeScoreNote::whereIn('employee_id', $employeeIds)
            ->with('author', 'employee')
            ->recent($limit)
            ->get();
    }

    public function getEmployeesForNote(User $author): \Illuminate\Database\Eloquent\Collection
    {
        $authorType = $this->determineAuthorType($author);

        // Admin and HR can see all employees
        if ($authorType === EmployeeScoreNote::TYPE_RATER_HR) {
            return Employee::where('is_active', true)
                ->with('department', 'designation')
                ->get();
        }

        // Department Head (via designation) - can see only their department employees
        if ($authorType === EmployeeScoreNote::TYPE_RATER_MANAGER) {
            $deptId = $this->getManagerDepartmentId($author);
            if (!$deptId) {
                return collect();
            }
            return Employee::where('department_id', $deptId)
                ->where('is_active', true)
                ->with('department', 'designation')
                ->get();
        }

        return collect();
    }

    public function canUserAddNotes(User $user): bool
    {
        // Admin can always add notes
        if ($user->hasAnyRole(['admin'])) {
            return true;
        }

        // HR/RH can always add notes
        if ($user->hasAnyRole(['hr', 'rh'])) {
            return true;
        }

        // Employee who is Head of a department can add notes for their team
        if ($this->isDepartmentHead($user)) {
            return true;
        }

        return false;
    }

    public function canUserViewEmployeeNotes(User $user, Employee $employee): bool
    {
        if ($user->hasAnyRole(['admin'])) {
            return true;
        }

        if ($user->hasAnyRole(['hr', 'rh'])) {
            return true;
        }

        // Department Head can view notes for employees in their department
        if ($this->isDepartmentHead($user)) {
            $userDeptId = $this->getManagerDepartmentId($user);
            return $userDeptId && $employee->department_id === $userDeptId;
        }

        return false;
    }

    public function getLatestNotesSummary(Employee $employee): array
    {
        $notes = $this->getNotesForEmployee($employee, limit: 10);

        $hrNotes = $notes->where('author_type', EmployeeScoreNote::TYPE_RATER_HR);
        $managerNotes = $notes->where('author_type', EmployeeScoreNote::TYPE_RATER_MANAGER);
        $deptNotes = $notes->where('author_type', EmployeeScoreNote::TYPE_RATER_DEPT_MANAGER);

        $totalAdjustment = $notes->sum('score_adjustment');
        $attendanceRatings = $notes->flatMap(fn ($note) => $note->attendance_note ?? [])->pluck('rating')->filter()->values();
        $disciplineRatings = $notes->flatMap(fn ($note) => $note->discipline_note ?? [])->pluck('rating')->filter()->values();
        $performanceRatings = $notes->flatMap(fn ($note) => $note->performance_note ?? [])->pluck('rating')->filter()->values();
        $allRatings = $attendanceRatings->concat($disciplineRatings)->concat($performanceRatings)->filter()->values();

        return [
            'total_notes' => $notes->count(),
            'hr_notes_count' => $hrNotes->count(),
            'manager_notes_count' => $managerNotes->count(),
            'dept_manager_notes_count' => $deptNotes->count(),
            'total_adjustment' => $totalAdjustment,
            'attendance_average_rating' => round($attendanceRatings->avg() ?? 0, 2),
            'discipline_average_rating' => round($disciplineRatings->avg() ?? 0, 2),
            'performance_average_rating' => round($performanceRatings->avg() ?? 0, 2),
            'overall_note_rating' => round($allRatings->avg() ?? 0, 2),
            'overall_note_percent' => round((($allRatings->avg() ?? 0) / 10) * 100, 2),
            'latest_note' => $notes->first()?->toArray(),
            'recent_notes' => $notes->take(5)->toArray(),
        ];
    }

    protected function determineAuthorType(User $user): string
    {
        if ($user->hasAnyRole(['admin'])) {
            return EmployeeScoreNote::TYPE_RATER_HR;
        }

        if ($user->hasAnyRole(['hr', 'rh', 'rh_manager'])) {
            return EmployeeScoreNote::TYPE_RATER_HR;
        }

        if ($this->isDepartmentHead($user)) {
            return EmployeeScoreNote::TYPE_RATER_MANAGER;
        }

        return EmployeeScoreNote::TYPE_RATER_DEPT_MANAGER;
    }

    protected function isDepartmentHead(User $user): bool
    {
        $employee = $user->employee;
        
        if (!$employee || !$employee->designation) {
            return false;
        }

        $designationName = strtolower($employee->designation->name ?? '');
        
        $headTitles = ['head', 'lead', 'responsible', 'chef', 'directeur', 'supervisor', 'coordinator'];
        
        foreach ($headTitles as $title) {
            if (str_contains($designationName, $title)) {
                return true;
            }
        }
        
        return false;
    }

    protected function getManagerDepartmentId(User $manager): ?int
    {
        $employee = $manager->employee;
        
        if (!$employee) {
            return null;
        }

        return $employee->department_id;
    }

    protected function recalculateEmployeeScore(Employee $employee): void
    {
        $scoreService = app(EmployeeScoreService::class);
        $scoreService->calculateScore($employee);
    }
}
