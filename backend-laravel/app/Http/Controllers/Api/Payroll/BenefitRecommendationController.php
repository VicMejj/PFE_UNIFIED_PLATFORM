<?php

namespace App\Http\Controllers\Api\Payroll;

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\CallsDjangoAI;
use App\Models\Employee\Employee;
use App\Models\Payroll\AllowanceOption;
use App\Models\Payroll\EmployeeBenefitRecommendation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BenefitRecommendationController extends ApiController
{
    use CallsDjangoAI;

    public function recommend($id)
    {
        $employee = Employee::with(['department', 'designation'])->findOrFail($id);
        $tenureMonths = $employee->tenure_months
            ?? ($employee->tenure_years ? (int) round($employee->tenure_years * 12) : 0);
        $performanceScore = $employee->performance_score ?? 3.5;
        $attendanceRate = $employee->attendance_rate ?? 95;

        $availableBenefits = AllowanceOption::query()
            ->where('is_active', true)
            ->get(['id', 'name', 'description'])
            ->map(fn ($option) => [
                'id' => $option->id,
                'name' => $option->name,
                'description' => $option->description,
            ])
            ->values();

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

        return [
            ...$item,
            'gap_actions' => $gapActions,
            'admin_guidance' => $item['admin_guidance'] ?? $this->buildAdminGuidance($status, $gapActions),
        ];
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
}
