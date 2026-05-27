<?php

namespace Tests\Feature;

use App\Models\Attendance\AttendanceRecord;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeScore;
use App\Models\Performance\Appraisal;
use App\Models\User;
use App\Services\EmployeeScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeScoreRealDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_employee_with_no_observed_records_starts_at_zero(): void
    {
        $employee = $this->createEmployee();

        $score = app(EmployeeScoreService::class)->calculateScore($employee);

        $this->assertSame(0.0, (float) $score->overall_score);
        $this->assertSame(EmployeeScore::TIER_NOT_STARTED, $score->score_tier);
        $this->assertFalse((bool) data_get($score->score_factors, 'meta.has_observed_work_data'));
    }

    public function test_employee_with_real_activity_receives_a_real_score(): void
    {
        $employee = $this->createEmployee([
            'company_doj' => now()->subYears(2)->toDateString(),
        ]);

        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'date' => now()->subDay()->toDateString(),
            'status' => 'present',
            'check_in' => '08:00',
            'check_out' => '17:00',
        ]);

        Appraisal::create([
            'employee_id' => $employee->id,
            'appraisal_year' => (int) now()->format('Y'),
            'rating' => 4.5,
            'status' => 'completed',
            'review_date' => now()->toDateString(),
        ]);

        $score = app(EmployeeScoreService::class)->calculateScore($employee);

        $this->assertGreaterThan(70, (float) $score->overall_score);
        $this->assertNotSame(EmployeeScore::TIER_NOT_STARTED, $score->score_tier);
        $this->assertTrue((bool) data_get($score->score_factors, 'meta.has_observed_work_data'));
    }

    public function test_legacy_placeholder_scores_are_recalculated_immediately(): void
    {
        $employee = $this->createEmployee();

        EmployeeScore::create([
            'employee_id' => $employee->id,
            'overall_score' => 76,
            'attendance_score' => 70,
            'performance_score' => 75,
            'discipline_score' => 100,
            'seniority_score' => 50,
            'engagement_score' => 75,
            'score_tier' => EmployeeScore::TIER_GOOD,
            'score_factors' => [
                'attendance' => ['score' => 70],
            ],
            'improvement_suggestions' => [
                'Maintain current performance levels to continue excellent standing.',
            ],
            'last_calculated_at' => now(),
        ]);

        $score = app(EmployeeScoreService::class)->getScore($employee);

        $this->assertSame(0.0, (float) $score->overall_score);
        $this->assertSame(EmployeeScore::TIER_NOT_STARTED, $score->score_tier);
        $this->assertSame(2, data_get($score->score_factors, 'meta.calculation_version'));
    }

    private function createEmployee(array $overrides = []): Employee
    {
        $user = User::factory()->create();

        $branchId = DB::table('branches')->insertGetId([
            'name' => 'Main Branch',
            'code' => 'BR-1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $departmentId = DB::table('departments')->insertGetId([
            'name' => 'Operations',
            'code' => 'OPS',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $designationId = DB::table('designations')->insertGetId([
            'title' => 'Analyst',
            'code' => 'ANL',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Employee::create(array_merge([
            'user_id' => $user->id,
            'name' => 'Score Test Employee',
            'email' => $user->email,
            'gender' => 'male',
            'address' => '123 Test Street',
            'password' => Hash::make('password'),
            'employee_id' => 'EMP-' . strtoupper(substr(sha1((string) $user->id), 0, 8)),
            'branch_id' => $branchId,
            'department_id' => $departmentId,
            'designation_id' => $designationId,
            'company_doj' => null,
            'salary' => 0,
            'is_active' => true,
            'created_by' => $user->id,
        ], $overrides));
    }
}
