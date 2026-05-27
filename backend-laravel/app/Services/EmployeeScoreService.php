<?php

namespace App\Services;

use App\Models\Attendance\AttendanceRecord;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeScore;
use App\Models\Employee\EmployeeScoreNote;
use App\Models\Employee\Warning;
use App\Models\Performance\Appraisal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EmployeeScoreService
{
    private const CALCULATION_VERSION = 2;

    /**
     * Calculate comprehensive employee score (0-100)
     */
    public function calculateScore(Employee $employee): EmployeeScore
    {
        $notes = EmployeeScoreNote::forEmployee($employee->id)
            ->where(function (Builder $query) {
                $query
                    ->whereNull('period_end')
                    ->orWhere('period_end', '>=', Carbon::now()->subMonths(3));
            })
            ->get();

        $notesAdjustment = $this->calculateNotesAdjustment($notes);
        $attendanceRecords = $this->getAttendanceRecords($employee);
        $appraisals = $this->getAppraisals($employee);
        $warnings = $this->getWarnings($employee);
        $incidents = $this->getIncidents($employee);

        $hasObservedWorkData = $this->hasObservedWorkData(
            $attendanceRecords,
            $appraisals,
            $warnings,
            $incidents,
            $notes
        );
        $hasManualAdjustment = abs($notesAdjustment) > 0.001;
        $hasRealData = $hasObservedWorkData || $hasManualAdjustment;

        $attendanceScore = 0.0;
        $performanceScore = 0.0;
        $disciplineScore = 0.0;
        $seniorityScore = 0.0;
        $engagementScore = 0.0;
        $overallScore = 0.0;

        if ($hasObservedWorkData) {
            $attendanceScore = $this->calculateAttendanceScore($attendanceRecords, $notes);
            $performanceScore = $this->calculatePerformanceScore($appraisals, $notes);
            $disciplineScore = $this->calculateDisciplineScore($warnings, $incidents, $notes, true);
            $seniorityScore = $this->calculateSeniorityScore($employee, true);
            $engagementScore = $this->calculateEngagementScore();

            $overallScore = $this->calculateOverallScore(
                $attendanceScore,
                $performanceScore,
                $disciplineScore,
                $seniorityScore,
                $engagementScore
            );
            $overallScore = min(100, max(0, $overallScore + $notesAdjustment));
        } elseif ($hasManualAdjustment) {
            $overallScore = min(100, max(0, $notesAdjustment));
        }

        $scoreTier = $hasRealData
            ? EmployeeScore::calculateTier($overallScore)
            : EmployeeScore::TIER_NOT_STARTED;

        $scoreFactors = $this->generateScoreFactors(
            $attendanceScore,
            $performanceScore,
            $disciplineScore,
            $seniorityScore,
            $engagementScore,
            $notesAdjustment,
            [
                'calculation_version' => self::CALCULATION_VERSION,
                'has_real_data' => $hasRealData,
                'has_observed_work_data' => $hasObservedWorkData,
                'attendance_records_count' => $attendanceRecords->count(),
                'appraisals_count' => $appraisals->count(),
                'warnings_count' => $warnings->count(),
                'incidents_count' => $incidents->count(),
                'notes_count' => $notes->count(),
            ]
        );

        $improvementSuggestions = $this->generateImprovementSuggestions(
            $attendanceScore,
            $performanceScore,
            $disciplineScore,
            $notes,
            $hasObservedWorkData,
            $hasManualAdjustment
        );

        $scoreData = [
            'overall_score' => (float) round($overallScore, 2),
            'attendance_score' => (float) round($attendanceScore, 2),
            'performance_score' => (float) round($performanceScore, 2),
            'discipline_score' => (float) round($disciplineScore, 2),
            'seniority_score' => (float) round($seniorityScore, 2),
            'engagement_score' => (float) round($engagementScore, 2),
            'score_tier' => $scoreTier,
            'score_factors' => $scoreFactors,
            'improvement_suggestions' => $improvementSuggestions,
            'last_calculated_at' => now(),
        ];

        return EmployeeScore::updateOrCreate(
            ['employee_id' => $employee->id],
            $scoreData
        );
    }

    protected function getAttendanceRecords(Employee $employee): Collection
    {
        return AttendanceRecord::where('employee_id', $employee->id)
            ->where('date', '>=', Carbon::now()->subMonths(3))
            ->get();
    }

    protected function getAppraisals(Employee $employee): Collection
    {
        return Appraisal::where('employee_id', $employee->id)
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();
    }

    protected function getWarnings(Employee $employee): Collection
    {
        return Warning::where('employee_id', $employee->id)
            ->where('created_at', '>=', Carbon::now()->subYear())
            ->get();
    }

    protected function getIncidents(Employee $employee): Collection
    {
        return \App\Models\Employee\WorkplaceIncident::where('employee_id', $employee->id)
            ->where('incident_date', '>=', Carbon::now()->subYear())
            ->get();
    }

    protected function hasObservedWorkData(
        Collection $attendanceRecords,
        Collection $appraisals,
        Collection $warnings,
        Collection $incidents,
        Collection $notes
    ): bool {
        return $attendanceRecords->isNotEmpty()
            || $appraisals->isNotEmpty()
            || $warnings->isNotEmpty()
            || $incidents->isNotEmpty()
            || $this->hasStructuredNoteRatings($notes);
    }

    protected function hasStructuredNoteRatings(Collection $notes): bool
    {
        return collect(['attendance_note', 'performance_note', 'discipline_note'])
            ->contains(fn (string $field) => $this->extractNoteRatings($notes, $field)->isNotEmpty());
    }

    protected function extractNoteRatings(Collection $notes, string $field): Collection
    {
        return $notes
            ->flatMap(fn ($note) => $note->{$field} ?? [])
            ->pluck('rating')
            ->filter(fn ($rating) => $rating !== null && $rating !== '')
            ->values();
    }

    protected function calculateNotesAdjustment($notes): float
    {
        $totalAdjustment = 0;

        foreach ($notes as $note) {
            $noteAdjustment = (float) ($note->score_adjustment ?? 0);
            $noteAge = $note->created_at?->diffInDays(now()) ?? 0;

            $ageFactor = match (true) {
                $noteAge <= 30 => 1.0,
                $noteAge <= 90 => 0.7,
                $noteAge <= 180 => 0.4,
                default => 0.2,
            };

            $totalAdjustment += $noteAdjustment * $ageFactor;
        }

        return min(20, max(-20, $totalAdjustment));
    }

    /**
     * Calculate attendance score based on presence and punctuality
     */
    protected function calculateAttendanceScore(Collection $attendanceRecords, ?Collection $notes = null): float
    {
        $attendanceRatings = $notes ? $this->extractNoteRatings($notes, 'attendance_note') : collect();

        if ($attendanceRecords->isEmpty()) {
            return $attendanceRatings->isNotEmpty()
                ? $this->ratingsToPercentage($attendanceRatings)
                : 0;
        }

        $totalDays = $attendanceRecords->count();
        $presentDays = $attendanceRecords->where('status', 'present')->count();
        $lateDays = $attendanceRecords->where('status', 'late')->count();
        $absentDays = $attendanceRecords->where('status', 'absent')->count();

        $attendanceRate = ($presentDays + ($lateDays * 0.5)) / max(1, $totalDays);
        $absenceDeduction = ($absentDays / max(1, $totalDays)) * 40;
        $lateDeduction = ($lateDays / max(1, $totalDays)) * 15;

        $score = ($attendanceRate * 60) - $absenceDeduction - $lateDeduction + 40;

        if ($attendanceRatings->isNotEmpty()) {
            $noteScore = $this->ratingsToPercentage($attendanceRatings);
            $score = ($score * 0.7) + ($noteScore * 0.3);
        }

        return max(0, min(100, $score));
    }

    /**
     * Calculate performance score based on appraisals and task completion
     */
    protected function calculatePerformanceScore(Collection $appraisals, ?Collection $notes = null): float
    {
        $performanceRatings = $notes ? $this->extractNoteRatings($notes, 'performance_note') : collect();

        if ($appraisals->isEmpty()) {
            return $performanceRatings->isNotEmpty()
                ? $this->ratingsToPercentage($performanceRatings)
                : 0;
        }

        $avgRating = $appraisals->avg('rating') ?? 0;
        $score = ($avgRating / 5) * 100;

        if ($performanceRatings->isNotEmpty()) {
            $noteScore = $this->ratingsToPercentage($performanceRatings);
            $score = ($score * 0.6) + ($noteScore * 0.4);
        }

        return max(0, min(100, $score));
    }

    /**
     * Calculate discipline score based on warnings and violations
     */
    protected function calculateDisciplineScore(
        Collection $warnings,
        Collection $incidents,
        ?Collection $notes = null,
        bool $hasObservedWorkData = true
    ): float {
        $disciplineRatings = $notes ? $this->extractNoteRatings($notes, 'discipline_note') : collect();

        if (! $hasObservedWorkData && $disciplineRatings->isEmpty()) {
            return 0;
        }

        $score = 100;

        foreach ($warnings as $warning) {
            $warningAge = $warning->created_at->diffInMonths();
            if ($warningAge <= 1) {
                $score -= 20;
            } elseif ($warningAge <= 3) {
                $score -= 15;
            } elseif ($warningAge <= 6) {
                $score -= 10;
            } else {
                $score -= 5;
            }
        }

        foreach ($incidents as $incident) {
            $incidentAge = Carbon::parse($incident->incident_date)->diffInMonths();
            $deduction = match ($incident->severity) {
                'critical' => 40,
                'high' => 25,
                'medium' => 15,
                default => 5,
            };

            if ($incidentAge <= 3) {
                $score -= $deduction;
            } elseif ($incidentAge <= 6) {
                $score -= ($deduction * 0.7);
            } else {
                $score -= ($deduction * 0.4);
            }
        }

        if ($disciplineRatings->isNotEmpty()) {
            $noteScore = $this->ratingsToPercentage($disciplineRatings);
            $score = ($score * 0.7) + ($noteScore * 0.3);
        }

        return max(0, min(100, $score));
    }

    protected function ratingsToPercentage($ratings): float
    {
        return max(0, min(100, (($ratings->avg() ?? 0) / 10) * 100));
    }

    /**
     * Calculate seniority score based on tenure
     */
    protected function calculateSeniorityScore(Employee $employee, bool $hasObservedWorkData): float
    {
        if (! $hasObservedWorkData || ! $employee->company_doj) {
            return 0;
        }

        $tenureYears = $employee->tenure_years;
        return min(100, 30 + ($tenureYears * 10));
    }

    /**
     * Engagement scoring stays at zero until we have measurable inputs.
     */
    protected function calculateEngagementScore(): float
    {
        return 0;
    }

    /**
     * Calculate overall weighted score
     */
    protected function calculateOverallScore(
        float $attendance,
        float $performance,
        float $discipline,
        float $seniority,
        float $engagement
    ): float {
        $weights = [
            'attendance' => 0.25,
            'performance' => 0.30,
            'discipline' => 0.20,
            'seniority' => 0.10,
            'engagement' => 0.15,
        ];

        return ($attendance * $weights['attendance'])
            + ($performance * $weights['performance'])
            + ($discipline * $weights['discipline'])
            + ($seniority * $weights['seniority'])
            + ($engagement * $weights['engagement']);
    }

    /**
     * Generate detailed score factors
     */
    protected function generateScoreFactors(
        float $attendance,
        float $performance,
        float $discipline,
        float $seniority,
        float $engagement,
        float $notesAdjustment = 0,
        array $meta = []
    ): array {
        return [
            'attendance' => [
                'score' => round($attendance, 2),
                'weight' => 0.25,
                'contribution' => round($attendance * 0.25, 2),
                'status' => $attendance >= 80 ? 'good' : ($attendance >= 60 ? 'average' : 'needs_improvement'),
            ],
            'performance' => [
                'score' => round($performance, 2),
                'weight' => 0.30,
                'contribution' => round($performance * 0.30, 2),
                'status' => $performance >= 80 ? 'good' : ($performance >= 60 ? 'average' : 'needs_improvement'),
            ],
            'discipline' => [
                'score' => round($discipline, 2),
                'weight' => 0.20,
                'contribution' => round($discipline * 0.20, 2),
                'status' => $discipline >= 80 ? 'good' : ($discipline >= 60 ? 'average' : 'needs_improvement'),
            ],
            'seniority' => [
                'score' => round($seniority, 2),
                'weight' => 0.10,
                'contribution' => round($seniority * 0.10, 2),
                'status' => $seniority >= 80 ? 'good' : ($seniority >= 60 ? 'average' : 'needs_improvement'),
            ],
            'engagement' => [
                'score' => round($engagement, 2),
                'weight' => 0.15,
                'contribution' => round($engagement * 0.15, 2),
                'status' => $engagement >= 80 ? 'good' : ($engagement >= 60 ? 'average' : 'needs_improvement'),
            ],
            'manager_adjustment' => [
                'score' => round($notesAdjustment, 2),
                'weight' => 0,
                'contribution' => round($notesAdjustment, 2),
                'status' => $notesAdjustment > 0 ? 'positive' : ($notesAdjustment < 0 ? 'negative' : 'neutral'),
            ],
            'meta' => $meta,
        ];
    }

    /**
     * Generate improvement suggestions based on low scores
     */
    protected function generateImprovementSuggestions(
        float $attendance,
        float $performance,
        float $discipline,
        ?Collection $notes = null,
        bool $hasObservedWorkData = true,
        bool $hasManualAdjustment = false
    ): array {
        if (! $hasObservedWorkData && ! $hasManualAdjustment) {
            return [
                'No performance data has been recorded yet, so your score starts at 0%.',
                'Your score will begin updating once attendance, appraisals, or manager score notes are added.',
            ];
        }

        $suggestions = [];

        if ($attendance <= 0) {
            $suggestions[] = 'Clock in consistently so the system can start measuring your attendance score.';
        } elseif ($attendance < 70) {
            $suggestions[] = 'Improve attendance by reducing absences and arriving on time.';
            $suggestions[] = 'Consider setting up calendar reminders for work hours.';
        }

        if ($performance <= 0) {
            $suggestions[] = 'Complete a first appraisal or receive a manager review to start building your performance score.';
        } elseif ($performance < 70) {
            $suggestions[] = 'Focus on completing assigned tasks within deadlines.';
            $suggestions[] = 'Seek feedback from manager on areas for improvement.';
            $suggestions[] = 'Consider enrolling in relevant training programs.';
        }

        if ($discipline > 0 && $discipline < 70) {
            $suggestions[] = 'Review and adhere to company policies and procedures.';
            $suggestions[] = 'Maintain professional conduct in the workplace.';
        }

        if ($notes && $notes->isNotEmpty()) {
            $latestNote = $notes->first();
            if ($latestNote?->general_note) {
                $suggestions[] = 'Manager note: ' . $latestNote->general_note;
            }
        }

        if (empty($suggestions)) {
            $suggestions[] = 'Maintain current performance levels to continue excellent standing.';
            $suggestions[] = 'Consider mentoring colleagues who may need support.';
        }

        return array_values(array_unique($suggestions));
    }

    protected function scoreNeedsRefresh(EmployeeScore $score): bool
    {
        return data_get($score->score_factors, 'meta.calculation_version') !== self::CALCULATION_VERSION;
    }

    /**
     * Get or calculate score for employee
     */
    public function getScore(Employee $employee, bool $recalculate = false): EmployeeScore
    {
        if ($recalculate) {
            return $this->calculateScore($employee);
        }

        $score = EmployeeScore::where('employee_id', $employee->id)->first();

        if (! $score
            || ! $score->last_calculated_at
            || $score->last_calculated_at->diffInDays(now()) > 30
            || $this->scoreNeedsRefresh($score)) {
            return $this->calculateScore($employee);
        }

        return $score;
    }

    /**
     * Check if employee is eligible for a benefit based on minimum score
     */
    public function isEligibleForBenefit(Employee $employee, float $minimumScore): bool
    {
        $score = $this->getScore($employee);
        return $score->isEligibleFor($minimumScore);
    }

    /**
     * Get employees by score tier
     */
    public function getEmployeesByTier(string $tier)
    {
        return EmployeeScore::where('score_tier', $tier)
            ->with('employee')
            ->get()
            ->pluck('employee');
    }

    /**
     * Get at-risk employees
     */
    public function getAtRiskEmployees()
    {
        return $this->getEmployeesByTier(EmployeeScore::TIER_RISK);
    }

    /**
     * Get excellent employees
     */
    public function getExcellentEmployees()
    {
        return $this->getEmployeesByTier(EmployeeScore::TIER_EXCELLENT);
    }

    /**
     * Bulk calculate scores for all employees
     */
    public function bulkCalculateScores(): int
    {
        $employees = Employee::where('is_active', true)->get();
        $updated = 0;

        foreach ($employees as $employee) {
            $this->calculateScore($employee);
            $updated++;
        }

        return $updated;
    }
}
