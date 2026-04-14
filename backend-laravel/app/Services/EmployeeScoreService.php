<?php

namespace App\Services;

use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeScore;
use App\Models\Employee\EmployeeScoreHistory;
use App\Models\Attendance\AttendanceRecord;
use App\Models\Leave\Leave;
use App\Models\Performance\Appraisal;
use App\Models\Employee\Warning;
use Carbon\Carbon;

class EmployeeScoreService
{
    /**
     * Calculate comprehensive employee score (0-100)
     */
    public function calculateScore(Employee $employee): EmployeeScore
    {
        $attendanceScore = $this->calculateAttendanceScore($employee);
        $performanceScore = $this->calculatePerformanceScore($employee);
        $disciplineScore = $this->calculateDisciplineScore($employee);
        $seniorityScore = $this->calculateSeniorityScore($employee);
        $engagementScore = $this->calculateEngagementScore($employee);

        $overallScore = $this->calculateOverallScore(
            $attendanceScore,
            $performanceScore,
            $disciplineScore,
            $seniorityScore,
            $engagementScore
        );

        $scoreTier = EmployeeScore::calculateTier($overallScore);
        $scoreFactors = $this->generateScoreFactors(
            $attendanceScore,
            $performanceScore,
            $disciplineScore,
            $seniorityScore,
            $engagementScore
        );

        $improvementSuggestions = $this->generateImprovementSuggestions(
            $attendanceScore,
            $performanceScore,
            $disciplineScore,
            $engagementScore
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

        $score = EmployeeScore::updateOrCreate(
            ['employee_id' => $employee->id],
            $scoreData
        );

        return $score;
    }

    /**
     * Calculate attendance score based on presence and punctuality
     */
    protected function calculateAttendanceScore(Employee $employee): float
    {
        $score = 100;
        $threeMonthsAgo = Carbon::now()->subMonths(3);

        // Get attendance records for last 3 months
        $attendanceRecords = AttendanceRecord::where('employee_id', $employee->id)
            ->where('date', '>=', $threeMonthsAgo)
            ->get();

        if ($attendanceRecords->isEmpty()) {
            return 70; // Default score if no data
        }

        $totalDays = $attendanceRecords->count();
        $presentDays = $attendanceRecords->where('status', 'present')->count();
        $lateDays = $attendanceRecords->where('status', 'late')->count();
        $absentDays = $attendanceRecords->where('status', 'absent')->count();

        // Calculate attendance rate
        $attendanceRate = ($presentDays + ($lateDays * 0.5)) / $totalDays;
        
        // Deduct for absences
        $absenceDeduction = ($absentDays / $totalDays) * 40;
        
        // Deduct for late arrivals
        $lateDeduction = ($lateDays / $totalDays) * 15;

        $score = ($attendanceRate * 60) - $absenceDeduction - $lateDeduction + 40;

        return max(0, min(100, $score));
    }

    /**
     * Calculate performance score based on appraisals and task completion
     */
    protected function calculatePerformanceScore(Employee $employee): float
    {
        $score = 75; // Default score

        // Get recent appraisals
        $appraisals = Appraisal::where('employee_id', $employee->id)
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        if ($appraisals->isNotEmpty()) {
            $avgRating = $appraisals->avg('rating') ?? 3;
            $score = ($avgRating / 5) * 100;
        }

        return max(0, min(100, $score));
    }

    /**
     * Calculate discipline score based on warnings and violations
     */
    protected function calculateDisciplineScore(Employee $employee): float
    {
        $score = 100;
        $oneYearAgo = Carbon::now()->subYear();

        // Get warnings for last year
        $warnings = Warning::where('employee_id', $employee->id)
            ->where('created_at', '>=', $oneYearAgo)
            ->get();

        // Deduct points for warnings
        foreach ($warnings as $warning) {
            $warningAge = $warning->created_at->diffInMonths();
            if ($warningAge <= 1) $score -= 20;
            elseif ($warningAge <= 3) $score -= 15;
            elseif ($warningAge <= 6) $score -= 10;
            else $score -= 5;
        }

        // Incorporate Workplace Incidents (Accidencies)
        $incidents = \App\Models\Employee\WorkplaceIncident::where('employee_id', $employee->id)
            ->where('incident_date', '>=', $oneYearAgo)
            ->get();

        foreach ($incidents as $incident) {
            $incidentAge = Carbon::parse($incident->incident_date)->diffInMonths();
            $deduction = 0;

            // Higher severity = higher deduction
            switch ($incident->severity) {
                case 'critical': $deduction = 40; break;
                case 'high': $deduction = 25; break;
                case 'medium': $deduction = 15; break;
                case 'low': $deduction = 5; break;
            }

            // Recency factor (more recent = more impact)
            if ($incidentAge <= 3) $score -= $deduction;
            elseif ($incidentAge <= 6) $score -= ($deduction * 0.7);
            else $score -= ($deduction * 0.4);
        }

        return max(0, min(100, $score));
    }

    /**
     * Calculate seniority score based on tenure
     */
    protected function calculateSeniorityScore(Employee $employee): float
    {
        if (!$employee->company_doj) {
            return 50;
        }

        $tenureYears = $employee->tenure_years;

        // Score increases with tenure, capped at 100
        $score = min(100, 30 + ($tenureYears * 10));

        return $score;
    }

    /**
     * Calculate engagement score based on participation
     */
    protected function calculateEngagementScore(Employee $employee): float
    {
        // This would ideally be based on:
        // - Meeting participation
        // - Training completion
        // - Team collaboration metrics
        // For now, use a baseline score
        return 75;
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
        // Weights for each component
        $weights = [
            'attendance' => 0.25,
            'performance' => 0.30,
            'discipline' => 0.20,
            'seniority' => 0.10,
            'engagement' => 0.15,
        ];

        $overall = ($attendance * $weights['attendance'])
            + ($performance * $weights['performance'])
            + ($discipline * $weights['discipline'])
            + ($seniority * $weights['seniority'])
            + ($engagement * $weights['engagement']);

        return $overall;
    }

    /**
     * Generate detailed score factors
     */
    protected function generateScoreFactors(
        float $attendance,
        float $performance,
        float $discipline,
        float $seniority,
        float $engagement
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
        ];
    }

    /**
     * Generate improvement suggestions based on low scores
     */
    protected function generateImprovementSuggestions(
        float $attendance,
        float $performance,
        float $discipline,
        float $engagement
    ): array {
        $suggestions = [];

        if ($attendance < 70) {
            $suggestions[] = 'Improve attendance by reducing absences and arriving on time.';
            $suggestions[] = 'Consider setting up calendar reminders for work hours.';
        }

        if ($performance < 70) {
            $suggestions[] = 'Focus on completing assigned tasks within deadlines.';
            $suggestions[] = 'Seek feedback from manager on areas for improvement.';
            $suggestions[] = 'Consider enrolling in relevant training programs.';
        }

        if ($discipline < 70) {
            $suggestions[] = 'Review and adhere to company policies and procedures.';
            $suggestions[] = 'Maintain professional conduct in the workplace.';
        }

        if ($engagement < 70) {
            $suggestions[] = 'Participate more actively in team meetings and discussions.';
            $suggestions[] = 'Engage in company events and team building activities.';
        }

        if (empty($suggestions)) {
            $suggestions[] = 'Maintain current performance levels to continue excellent standing.';
            $suggestions[] = 'Consider mentoring colleagues who may need support.';
        }

        return $suggestions;
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

        // Recalculate if no score exists or if last calculation is older than 30 days
        if (!$score || $score->last_calculated_at?->diffInDays(now()) > 30) {
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