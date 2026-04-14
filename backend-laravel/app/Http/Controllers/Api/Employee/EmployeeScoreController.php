<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Api\ApiController;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeScore;
use App\Services\EmployeeScoreService;
use Illuminate\Http\Request;

class EmployeeScoreController extends ApiController
{
    public function __construct(
        private EmployeeScoreService $scoreService
    ) {}

    /**
     * Get all employee scores with filters
     */
    public function index(Request $request)
    {
        $query = EmployeeScore::with(['employee.user', 'employee.department', 'employee.designation']);

        // Filter by tier
        if ($tier = $request->query('tier')) {
            $query->where('score_tier', $tier);
        }

        // Filter by minimum score
        if ($minScore = $request->query('min_score')) {
            $query->where('overall_score', '>=', $minScore);
        }

        // Filter by department
        if ($departmentId = $request->query('department_id')) {
            $query->whereHas('employee', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        // Search by employee name
        if ($search = $request->query('search')) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $scores = $request->query('per_page', false) === 'false' 
            ? $query->get() 
            : $query->paginate($request->query('per_page', 15));

        return $this->successResponse($scores);
    }

    /**
     * Get score for a specific employee
     */
    public function show($employeeId)
    {
        $employee = Employee::findOrFail($employeeId);
        $score = $this->scoreService->getScore($employee);

        return $this->successResponse([
            'employee' => $employee,
            'score' => $score,
        ]);
    }

    /**
     * Get score for the authenticated employee
     */
    public function myScore()
    {
        $employee = Employee::where('user_id', auth()->id())->firstOrFail();
        $score = $this->scoreService->getScore($employee);

        return $this->successResponse($score);
    }

    /**
     * Recalculate score for an employee
     */
    public function recalculate($employeeId)
    {
        $employee = Employee::findOrFail($employeeId);
        $score = $this->scoreService->calculateScore($employee);

        return $this->successResponse([
            'message' => 'Score recalculated successfully',
            'score' => $score,
        ]);
    }

    /**
     * Get score history for an employee
     */
    public function history($employeeId)
    {
        $employee = Employee::findOrFail($employeeId);
        
        $history = $employee->scoreHistories()
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return $this->successResponse($history);
    }

    /**
     * Get dashboard statistics for scores
     */
    public function dashboard()
    {
        $totalEmployees = Employee::where('is_active', true)->count();
        
        $scores = EmployeeScore::selectRaw("
            COUNT(*) as total,
            AVG(overall_score) as avg_score,
            SUM(CASE WHEN score_tier = 'excellent' THEN 1 ELSE 0 END) as excellent_count,
            SUM(CASE WHEN score_tier = 'good' THEN 1 ELSE 0 END) as good_count,
            SUM(CASE WHEN score_tier = 'medium' THEN 1 ELSE 0 END) as medium_count,
            SUM(CASE WHEN score_tier = 'risk' THEN 1 ELSE 0 END) as risk_count
        ")->first();

        $atRiskEmployees = EmployeeScore::atRisk()
            ->with(['employee.user', 'employee.department'])
            ->limit(10)
            ->get();

        $excellentEmployees = EmployeeScore::excellent()
            ->with(['employee.user', 'employee.department'])
            ->limit(10)
            ->get();

        return $this->successResponse([
            'summary' => [
                'total_employees' => $totalEmployees,
                'average_score' => round($scores->avg_score ?? 0, 2),
                'excellent_count' => $scores->excellent_count ?? 0,
                'good_count' => $scores->good_count ?? 0,
                'medium_count' => $scores->medium_count ?? 0,
                'risk_count' => $scores->risk_count ?? 0,
            ],
            'at_risk_employees' => $atRiskEmployees,
            'excellent_employees' => $excellentEmployees,
        ]);
    }

    /**
     * Bulk recalculate all employee scores
     */
    public function bulkRecalculate()
    {
        $updated = $this->scoreService->bulkCalculateScores();

        return $this->successResponse([
            'message' => "Successfully recalculated scores for {$updated} employees",
            'updated_count' => $updated,
        ]);
    }

    /**
     * Check eligibility for a benefit
     */
    public function checkEligibility($employeeId, Request $request)
    {
        $employee = Employee::findOrFail($employeeId);
        $minScore = $request->query('min_score', 70);

        $isEligible = $this->scoreService->isEligibleForBenefit($employee, $minScore);
        $score = $this->scoreService->getScore($employee);

        return $this->successResponse([
            'employee_id' => $employeeId,
            'is_eligible' => $isEligible,
            'current_score' => $score->overall_score,
            'required_score' => $minScore,
            'score_tier' => $score->score_tier,
            'gap' => max(0, $minScore - $score->overall_score),
        ]);
    }

    /**
     * Get improvement suggestions for an employee
     */
    public function suggestions($employeeId)
    {
        $employee = Employee::findOrFail($employeeId);
        $score = $this->scoreService->getScore($employee);

        return $this->successResponse([
            'employee_id' => $employeeId,
            'current_score' => $score->overall_score,
            'score_tier' => $score->score_tier,
            'suggestions' => $score->improvement_suggestions ?? [],
            'score_factors' => $score->score_factors ?? [],
        ]);
    }
}