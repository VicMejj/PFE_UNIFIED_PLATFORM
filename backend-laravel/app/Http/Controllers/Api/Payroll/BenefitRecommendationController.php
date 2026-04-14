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

            $fallback = $availableBenefits->map(fn ($benefit) => $this->normalizeRecommendation([
                'benefit_id' => $benefit['id'],
                'eligibility_score' => 0.45,
                'status' => 'nearly_eligible',
                'gap_actions' => ['Improve attendance and performance to qualify for this benefit'],
                'estimated_months_to_qualify' => 3,
            ]))->values();

            return $this->successResponse($fallback, 'Fallback benefit recommendations returned');
        }
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
