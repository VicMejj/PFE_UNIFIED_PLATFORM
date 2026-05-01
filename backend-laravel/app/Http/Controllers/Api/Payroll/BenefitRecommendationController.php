<?php

namespace App\Http\Controllers\Api\Payroll;

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\CallsDjangoAI;
use App\Models\Employee\Employee;
use App\Models\Payroll\AllowanceOption;
use App\Models\Payroll\EmployeeBenefitRecommendation;
use App\Services\EmployeeScoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BenefitRecommendationController extends ApiController
{
    use CallsDjangoAI;

    protected $scoreService;

    public function __construct(EmployeeScoreService $scoreService)
    {
        $this->scoreService = $scoreService;
    }

    public function recommend($id)
    {
        $employee = Employee::with(['department', 'designation'])->findOrFail($id);
        $tenureMonths = $employee->tenure_months
            ?? ($employee->tenure_years ? (int) round($employee->tenure_years * 12) : 0);
        $performanceScore = $employee->performance_score ?? 3.5;
        $attendanceRate = $employee->attendance_rate ?? 95;
        $departmentName = Str::lower($employee->department?->name ?? '');
        $roleName = Str::lower($employee->designation?->name ?? $employee->job_title ?? '');
        $recentSelections = EmployeeBenefitRecommendation::query()
            ->where('employee_id', $employee->id)
            ->with('allowanceOption')
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->pluck('allowanceOption.name')
            ->filter()
            ->map(fn ($value) => Str::lower($value))
            ->values()
            ->all();

        $availableBenefits = AllowanceOption::query()
            ->where('is_active', true)
            ->get(['id', 'name', 'description'])
            ->map(fn ($option) => [
                'id' => $option->id,
                'name' => $option->name,
                'description' => $option->description,
                'assignment_count' => EmployeeBenefitRecommendation::query()->where('allowance_option_id', $option->id)->count(),
            ])
            ->values();

        $score = $this->scoreService->getScore($employee);

        $payload = [
            'employee_id' => $employee->id,
            'employee' => [
                'name' => $employee->full_name ?? $employee->name ?? null,
                'email' => $employee->email ?? null,
                'role' => $employee->designation?->name ?? $employee->job_title ?? null,
                'department' => $employee->department?->name ?? null,
                'tenure_months' => $tenureMonths,
                'performance_score' => $performanceScore,
                'attendance_rate' => $attendanceRate,
                'overall_score' => $score->overall_score,
                'score_tier' => $score->score_tier,
            ],
            'available_benefits' => $availableBenefits,
        ];

        try {
            $response = $this->djangoPost('/api/ai/benefits/recommend/', $payload);
            if ($response->successful()) {
                $recommendations = collect($response->json('data') ?? [])
                    ->map(fn (array $item) => $this->normalizeRecommendation($item))
                    ->values()
                    ->all();
                $this->persistRecommendations($employee->id, $recommendations);
                return $this->successResponse($recommendations, 'Benefit recommendations retrieved successfully');
            }

            throw new \RuntimeException('AI backend returned error: ' . $response->body());
        } catch (\Throwable $e) {
            Log::error('Benefit recommendation error: ' . $e->getMessage());

            $recommendations = $this->generateLocalRecommendations(
                $employee,
                $availableBenefits,
                $score,
                $tenureMonths,
                $performanceScore,
                $attendanceRate,
                $departmentName,
                $roleName,
                $recentSelections
            );

            $this->persistRecommendations($employee->id, $recommendations);
            return $this->successResponse($recommendations, 'Benefit recommendations generated locally');
        }
    }

    protected function generateLocalRecommendations(
        Employee $employee,
        $availableBenefits,
        $score,
        int $tenureMonths,
        float $performanceScore,
        float $attendanceRate,
        string $departmentName,
        string $roleName,
        array $recentSelections
    ): array {
        $overallScore = $score->overall_score ?? 50;
        $attendanceScore = $score->attendance_score ?? 70;
        $disciplineScore = $score->discipline_score ?? 80;
        $seniorityScore = $score->seniority_score ?? 50;
        $engagementScore = $score->engagement_score ?? 70;

        $benefitConfigs = $this->getBenefitConfigurations();

        return $availableBenefits->map(function ($benefit) use (
            $overallScore,
            $attendanceScore,
            $disciplineScore,
            $seniorityScore,
            $engagementScore,
            $performanceScore,
            $attendanceRate,
            $tenureMonths,
            $departmentName,
            $roleName,
            $recentSelections,
            $benefitConfigs
        ) {
            $benefitName = Str::lower($benefit['name']);
            $config = $benefitConfigs[$benefit['name']] ?? $this->getDefaultConfig();

            $eligibilityScore = $this->calculateBenefitEligibility(
                $benefitName,
                $overallScore,
                $attendanceScore,
                $disciplineScore,
                $seniorityScore,
                $engagementScore,
                $performanceScore,
                $attendanceRate,
                $tenureMonths,
                $departmentName,
                $roleName,
                $config,
                $recentSelections
            );

            $gapActions = $this->determineGapActions(
                $benefitName,
                $attendanceScore,
                $disciplineScore,
                $seniorityScore,
                $engagementScore,
                $performanceScore,
                $attendanceRate,
                $tenureMonths,
                $config
            );

            $status = $this->determineStatus($eligibilityScore, $gapActions);
            $estimatedMonths = $this->estimateMonthsToQualify($gapActions, $tenureMonths);

            return $this->normalizeRecommendation([
                'benefit_id' => $benefit['id'],
                'benefit_name' => $benefit['name'],
                'eligibility_score' => $eligibilityScore,
                'status' => $status,
                'gap_actions' => $gapActions,
                'estimated_months_to_qualify' => $estimatedMonths,
                'reasoning' => $this->buildReasoning($status, $eligibilityScore, $gapActions),
                'admin_guidance' => $this->buildAdminGuidance($status, $gapActions),
            ]);
        })->values()->all();
    }

    protected function getBenefitConfigurations(): array
    {
        return [
            'Health Insurance' => [
                'min_tenure_months' => 6,
                'min_performance' => 3.0,
                'min_attendance' => 85,
                'weights' => ['seniority' => 0.15, 'performance' => 0.30, 'attendance' => 0.30, 'discipline' => 0.15, 'engagement' => 0.10],
            ],
            'Dental Coverage' => [
                'min_tenure_months' => 12,
                'min_performance' => 3.0,
                'min_attendance' => 80,
                'weights' => ['seniority' => 0.20, 'performance' => 0.25, 'attendance' => 0.25, 'discipline' => 0.15, 'engagement' => 0.15],
            ],
            'Vision Insurance' => [
                'min_tenure_months' => 12,
                'min_performance' => 3.0,
                'min_attendance' => 80,
                'weights' => ['seniority' => 0.20, 'performance' => 0.25, 'attendance' => 0.25, 'discipline' => 0.15, 'engagement' => 0.15],
            ],
            'Life Insurance' => [
                'min_tenure_months' => 6,
                'min_performance' => 3.5,
                'min_attendance' => 90,
                'weights' => ['seniority' => 0.15, 'performance' => 0.30, 'attendance' => 0.25, 'discipline' => 0.25, 'engagement' => 0.05],
            ],
            'Gym Membership' => [
                'min_tenure_months' => 3,
                'min_performance' => 2.5,
                'min_attendance' => 75,
                'weights' => ['seniority' => 0.10, 'performance' => 0.20, 'attendance' => 0.30, 'discipline' => 0.15, 'engagement' => 0.25],
            ],
            'Professional Development' => [
                'min_tenure_months' => 6,
                'min_performance' => 3.5,
                'min_attendance' => 80,
                'weights' => ['seniority' => 0.10, 'performance' => 0.35, 'attendance' => 0.20, 'discipline' => 0.15, 'engagement' => 0.20],
            ],
            'Transportation Allowance' => [
                'min_tenure_months' => 6,
                'min_performance' => 3.0,
                'min_attendance' => 85,
                'weights' => ['seniority' => 0.15, 'performance' => 0.25, 'attendance' => 0.30, 'discipline' => 0.15, 'engagement' => 0.15],
            ],
            'Remote Work Stipend' => [
                'min_tenure_months' => 12,
                'min_performance' => 3.5,
                'min_attendance' => 90,
                'weights' => ['seniority' => 0.10, 'performance' => 0.35, 'attendance' => 0.25, 'discipline' => 0.20, 'engagement' => 0.10],
            ],
            'Retirement Plan (401k)' => [
                'min_tenure_months' => 24,
                'min_performance' => 3.5,
                'min_attendance' => 85,
                'weights' => ['seniority' => 0.30, 'performance' => 0.30, 'attendance' => 0.20, 'discipline' => 0.15, 'engagement' => 0.05],
            ],
            'Paid Time Off (PTO)' => [
                'min_tenure_months' => 12,
                'min_performance' => 3.0,
                'min_attendance' => 85,
                'weights' => ['seniority' => 0.25, 'performance' => 0.25, 'attendance' => 0.25, 'discipline' => 0.15, 'engagement' => 0.10],
            ],
            'Meal Vouchers' => [
                'min_tenure_months' => 3,
                'min_performance' => 2.5,
                'min_attendance' => 80,
                'weights' => ['seniority' => 0.15, 'performance' => 0.20, 'attendance' => 0.35, 'discipline' => 0.15, 'engagement' => 0.15],
            ],
            'Child Care Assistance' => [
                'min_tenure_months' => 12,
                'min_performance' => 3.0,
                'min_attendance' => 80,
                'weights' => ['seniority' => 0.15, 'performance' => 0.25, 'attendance' => 0.25, 'discipline' => 0.20, 'engagement' => 0.15],
            ],
            'Manager of the Year' => [
                'min_tenure_months' => 24,
                'min_performance' => 4.5,
                'min_attendance' => 95,
                'weights' => ['seniority' => 0.15, 'performance' => 0.40, 'attendance' => 0.20, 'discipline' => 0.15, 'engagement' => 0.10],
            ],
        ];
    }

    protected function getDefaultConfig(): array
    {
        return [
            'min_tenure_months' => 6,
            'min_performance' => 3.0,
            'min_attendance' => 80,
            'weights' => ['seniority' => 0.20, 'performance' => 0.25, 'attendance' => 0.25, 'discipline' => 0.15, 'engagement' => 0.15],
        ];
    }

    protected function calculateBenefitEligibility(
        string $benefitName,
        float $overallScore,
        float $attendanceScore,
        float $disciplineScore,
        float $seniorityScore,
        float $engagementScore,
        float $performanceScore,
        float $attendanceRate,
        int $tenureMonths,
        string $departmentName,
        string $roleName,
        array $config,
        array $recentSelections
    ): float {
        $weights = $config['weights'];
        $minTenure = $config['min_tenure_months'];
        $minPerformance = $config['min_performance'];
        $minAttendance = $config['min_attendance'];

        $weightedScore = ($attendanceScore * $weights['attendance'])
            + ($performanceScore * 20 * $weights['performance'])
            + ($disciplineScore * $weights['discipline'])
            + ($seniorityScore * $weights['seniority'])
            + ($engagementScore * $weights['engagement']);

        $tenureRatio = min(1.0, $tenureMonths / max(1, $minTenure));
        $performanceRatio = min(1.0, $performanceScore / max(1, $minPerformance));
        $attendanceRatio = min(1.0, $attendanceRate / max(1, $minAttendance));

        $baseEligibility = $weightedScore / 100;
        $tenureBonus = $tenureRatio * 0.15;
        $performanceBonus = ($performanceRatio - 0.5) * 0.15;
        $attendanceBonus = ($attendanceRatio - 0.7) * 0.10;

        $eligibility = $baseEligibility + $tenureBonus + $performanceBonus + $attendanceBonus;

        if (in_array($benefitName, $recentSelections, true)) {
            $eligibility -= 0.20;
        }

        if (Str::contains($benefitName, ['health', 'medical']) && Str::contains($departmentName, 'operations')) {
            $eligibility += 0.10;
        }
        if (Str::contains($benefitName, ['training', 'development']) && Str::contains($roleName, 'engineer')) {
            $eligibility += 0.12;
        }

        return max(0.05, min(0.98, round($eligibility, 2)));
    }

    protected function determineGapActions(
        string $benefitName,
        float $attendanceScore,
        float $disciplineScore,
        float $seniorityScore,
        float $engagementScore,
        float $performanceScore,
        float $attendanceRate,
        int $tenureMonths,
        array $config
    ): array {
        $gapActions = [];
        $minTenure = $config['min_tenure_months'];
        $minPerformance = $config['min_performance'];
        $minAttendance = $config['min_attendance'];

        if ($tenureMonths < $minTenure) {
            $monthsNeeded = $minTenure - $tenureMonths;
            $gapActions[] = "Reach {$minTenure} months tenure (currently {$tenureMonths} months, needs {$monthsNeeded} more month(s))";
        }

        if ($performanceScore < $minPerformance) {
            $gapActions[] = "Improve performance score from {$performanceScore} to {$minPerformance}+";
        }

        if ($attendanceRate < $minAttendance) {
            $gapActions[] = "Improve attendance rate from {$attendanceRate}% to {$minAttendance}%+";
        }

        if ($attendanceScore < 75) {
            $gapActions[] = "Reduce absences and late arrivals to improve attendance score";
        }

        if ($disciplineScore < 70) {
            $gapActions[] = "Maintain clean disciplinary record";
        }

        if ($engagementScore < 65) {
            $gapActions[] = "Increase team participation and engagement activities";
        }

        if (empty($gapActions)) {
            $gapActions[] = "All requirements met - ready for assignment";
        }

        return $gapActions;
    }

    protected function determineStatus(float $eligibilityScore, array $gapActions): string
    {
        if ($eligibilityScore >= 0.85) {
            return 'eligible';
        }

        if ($eligibilityScore >= 0.60) {
            return 'nearly_eligible';
        }

        return 'not_eligible';
    }

    protected function estimateMonthsToQualify(array $gapActions, int $tenureMonths): int
    {
        if (empty($gapActions) || $gapActions[0] === 'All requirements met - ready for assignment') {
            return 0;
        }

        $monthsNeeded = 0;

        foreach ($gapActions as $action) {
            if (preg_match('/needs (\d+) more month/i', $action, $matches)) {
                $monthsNeeded = max($monthsNeeded, (int) $matches[1]);
            }
        }

        foreach (['performance', 'attendance', 'disciplinary', 'engagement'] as $factor) {
            if (preg_match("/{$factor}/i", implode(' ', $gapActions))) {
                $monthsNeeded = max($monthsNeeded, 3);
            }
        }

        return max(1, min(24, $monthsNeeded));
    }

    protected function normalizeRecommendation(array $item): array
    {
        $gapActions = array_values(array_filter($item['gap_actions'] ?? []));
        $status = $item['status'] ?? 'not_eligible';
        $score = (float) ($item['eligibility_score'] ?? 0);

        return [
            ...$item,
            'gap_actions' => $gapActions,
            'benefit_name' => $item['benefit_name'] ?? null,
            'reasoning' => $item['reasoning'] ?? $this->buildReasoning($status, $score, $gapActions),
            'admin_guidance' => $item['admin_guidance'] ?? $this->buildAdminGuidance($status, $gapActions),
        ];
    }

    protected function buildReasoning(string $status, float $score, array $gapActions): string
    {
        if ($status === 'eligible') {
            return 'Strong fit with current role, tenure, and performance profile.';
        }

        if ($status === 'nearly_eligible') {
            return 'Close to eligibility. A small policy gap remains before assignment.';
        }

        if (! empty($gapActions)) {
            return 'Needs improvement in the highlighted areas before this benefit becomes a good fit.';
        }

        return 'A cautious recommendation based on the current employee profile.';
    }

    protected function buildAdminGuidance(string $status, array $gapActions): string
    {
        if ($status === 'eligible') {
            return 'This employee is ready for assignment. Grant the benefit now and monitor adoption in the next review cycle.';
        }

        if ($status === 'nearly_eligible' && ! empty($gapActions)) {
            return 'Coach the employee on the highlighted gaps and consider creating a pending assignment once policy requirements are nearly met.';
        }

        if (! empty($gapActions)) {
            return 'Do not assign this benefit yet. Share a short development plan focused on: ' . implode('; ', $gapActions) . '.';
        }

        return 'Review performance, attendance, and tenure data before assigning this benefit.';
    }

    protected function persistRecommendations(int $employeeId, array $recommendations): void
    {
        foreach ($recommendations as $item) {
            if (empty($item['benefit_id'])) {
                continue;
            }

            EmployeeBenefitRecommendation::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'allowance_option_id' => $item['benefit_id'],
                ],
                [
                    'score' => $item['eligibility_score'] ?? 0.0,
                    'status' => $item['status'] ?? 'not_eligible',
                    'gap_actions' => $item['gap_actions'] ?? [],
                    'estimated_months_to_qualify' => $item['estimated_months_to_qualify'] ?? null,
                ]
            );
        }
    }

    protected function keywordBoost(string $benefitName, string $departmentName, string $roleName, array $recentSelections): float
    {
        $benefit = Str::lower($benefitName);
        $boost = 0.0;

        $keywordMap = [
            'health' => ['health', 'medical', 'insurance', 'wellness', 'hsa', 'care'],
            'dental' => ['dental', 'teeth', 'oral'],
            'vision' => ['vision', 'eyewear', 'glasses'],
            'transport' => ['transport', 'commute', 'travel'],
            'remote' => ['remote', 'home office', 'home'],
            'training' => ['training', 'learning', 'course', 'development'],
            'family' => ['family', 'parental', 'child', 'care'],
            'fitness' => ['fitness', 'gym', 'wellness', 'health'],
        ];

        foreach ($keywordMap as $keywords) {
            foreach ($keywords as $keyword) {
                if (Str::contains($benefit, $keyword)) {
                    $boost += 0.08;
                    break 2;
                }
            }
        }

        if ($departmentName && Str::contains($benefit, $departmentName)) {
            $boost += 0.12;
        }

        if ($roleName && Str::contains($benefit, $roleName)) {
            $boost += 0.10;
        }

        if (! empty($recentSelections) && in_array($benefit, $recentSelections, true)) {
            $boost -= 0.18;
        }

        return max(-0.25, min(0.25, $boost));
    }
}
