<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Employee\Employee;
use App\Models\Insurance\InsurancePlan;
use App\Models\Insurance\InsuranceAssignment;
use App\Models\Payroll\BenefitRequest;
use App\Models\Payroll\AllowanceOption;
use App\Services\EmployeeScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class InsuranceBenefitsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user and employee
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->employee = Employee::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Test Employee',
            'email' => 'test@example.com',
            'company_doj' => now()->subYears(2),
        ]);
    }

    /**
     * Test employee score calculation
     */
    public function test_employee_score_calculation(): void
    {
        $scoreService = new EmployeeScoreService();
        $score = $scoreService->calculateScore($this->employee);

        $this->assertNotNull($score);
        $this->assertIsNumeric($score->overall_score);
        $this->assertGreaterThanOrEqual(0, $score->overall_score);
        $this->assertLessThanOrEqual(100, $score->overall_score);
        $this->assertNotNull($score->score_tier);
        $this->assertNotNull($score->score_factors);
        $this->assertNotNull($score->improvement_suggestions);
    }

    /**
     * Test insurance plan creation
     */
    public function test_insurance_plan_creation(): void
    {
        $this->actingAs($this->user);

        $planData = [
            'name' => 'Health Insurance Plan',
            'plan_code' => 'HLP001',
            'coverage_type' => 'family',
            'insurance_type' => 'health',
            'reimbursement_percentage' => 80,
            'maximum_yearly_amount' => 50000,
            'deductible_amount' => 1000,
            'waiting_period_days' => 30,
            'description' => 'Comprehensive health insurance plan',
        ];

        $response = $this->postJson('/api/insurance/plans', $planData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Health Insurance Plan',
                    'plan_code' => 'HLP001',
                    'coverage_type' => 'family',
                    'insurance_type' => 'health',
                ]
            ]);

        $this->assertDatabaseHas('insurance_plans', [
            'name' => 'Health Insurance Plan',
            'plan_code' => 'HLP001',
        ]);
    }

    /**
     * Test insurance plan assignment
     */
    public function test_insurance_plan_assignment(): void
    {
        $plan = InsurancePlan::factory()->create([
            'name' => 'Test Plan',
            'coverage_type' => 'individual',
            'insurance_type' => 'health',
        ]);

        $this->actingAs($this->user);

        $assignmentData = [
            'employee_ids' => [$this->employee->id],
            'effective_date' => now()->toDateString(),
            'employee_contribution' => 100,
        ];

        $response = $this->postJson("/api/insurance/plans/{$plan->id}/assign-employees", $assignmentData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'assigned' => 1,
                    'skipped' => 0,
                ]
            ]);

        $this->assertDatabaseHas('insurance_assignments', [
            'insurance_plan_id' => $plan->id,
            'employee_id' => $this->employee->id,
            'status' => 'active',
        ]);
    }

    /**
     * Test benefit request submission
     */
    public function test_benefit_request_submission(): void
    {
        // Create an allowance option with eligibility rules
        $allowanceOption = AllowanceOption::factory()->create([
            'name' => 'Medical Allowance',
            'description' => 'Medical expense allowance',
        ]);

        // Create eligibility rule
        $allowanceOption->benefitEligibilityRules()->create([
            'threshold' => 70,
            'auto_approve_threshold' => 50,
            'max_amount' => 1000,
        ]);

        $this->actingAs($this->user);

        $requestData = [
            'allowance_option_id' => $allowanceOption->id,
            'requested_amount' => 200,
            'reason' => 'Medical expenses',
            'supporting_documents' => [],
        ];

        $response = $this->postJson('/api/payroll/benefits/requests', $requestData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'requested_amount' => 200,
                    'reason' => 'Medical expenses',
                    'status' => 'submitted',
                ]
            ]);

        $this->assertDatabaseHas('benefit_requests', [
            'employee_id' => $this->employee->id,
            'requested_amount' => 200,
            'status' => 'submitted',
        ]);
    }

    /**
     * Test benefit request approval workflow
     */
    public function test_benefit_request_approval_workflow(): void
    {
        $allowanceOption = AllowanceOption::factory()->create([
            'name' => 'Travel Allowance',
        ]);

        $allowanceOption->benefitEligibilityRules()->create([
            'threshold' => 0, // No threshold for this test
            'auto_approve_threshold' => 0,
        ]);

        $benefitRequest = BenefitRequest::create([
            'employee_id' => $this->employee->id,
            'allowance_option_id' => $allowanceOption->id,
            'requested_amount' => 150,
            'reason' => 'Business travel',
            'status' => 'submitted',
            'submitted_by' => $this->user->id,
        ]);

        $this->actingAs($this->user);

        // Start review
        $response = $this->postJson("/api/payroll/benefits/requests/{$benefitRequest->id}/start-review");
        $response->assertStatus(200);

        // Approve request
        $approveData = [
            'approved_amount' => 150,
            'comments' => 'Approved',
        ];

        $response = $this->postJson("/api/payroll/benefits/requests/{$benefitRequest->id}/approve", $approveData);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'approved',
                    'approved_amount' => 150,
                ]
            ]);

        // Mark as delivered
        $response = $this->postJson("/api/payroll/benefits/requests/{$benefitRequest->id}/deliver");
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'delivered',
                ]
            ]);
    }

    /**
     * Test auto-approval of small benefit requests
     */
    public function test_auto_approval(): void
    {
        $allowanceOption = AllowanceOption::factory()->create([
            'name' => 'Small Expense Allowance',
        ]);

        $allowanceOption->benefitEligibilityRules()->create([
            'threshold' => 60,
            'auto_approve_threshold' => 100,
        ]);

        // Create a score for the employee to meet the threshold
        $scoreService = new EmployeeScoreService();
        $score = $scoreService->calculateScore($this->employee);

        $benefitRequest = BenefitRequest::create([
            'employee_id' => $this->employee->id,
            'allowance_option_id' => $allowanceOption->id,
            'requested_amount' => 50, // Below auto-approve threshold
            'reason' => 'Small expense',
            'status' => 'submitted',
            'submitted_by' => $this->user->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->postJson("/api/payroll/benefits/requests/{$benefitRequest->id}/auto-approve");
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'approved',
                    'is_auto_approved' => true,
                ]
            ]);
    }

    /**
     * Test score-based eligibility
     */
    public function test_score_based_eligibility(): void
    {
        $allowanceOption = AllowanceOption::factory()->create([
            'name' => 'Premium Allowance',
        ]);

        $allowanceOption->benefitEligibilityRules()->create([
            'threshold' => 80, // High threshold
            'auto_approve_threshold' => 0,
        ]);

        $this->actingAs($this->user);

        $requestData = [
            'allowance_option_id' => $allowanceOption->id,
            'requested_amount' => 300,
            'reason' => 'Premium service',
        ];

        $response = $this->postJson('/api/payroll/benefits/requests', $requestData);

        // Should succeed but may include eligibility warning
        $response->assertStatus(201);
    }

    /**
     * Test insurance plan statistics
     */
    public function test_insurance_plan_statistics(): void
    {
        $plan = InsurancePlan::factory()->create([
            'name' => 'Test Statistics Plan',
        ]);

        // Create some assignments
        InsuranceAssignment::factory()->create([
            'insurance_plan_id' => $plan->id,
            'employee_id' => $this->employee->id,
            'status' => 'active',
        ]);

        $this->actingAs($this->user);

        $response = $this->getJson("/api/insurance/plans/{$plan->id}/statistics");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_assignments' => 1,
                    'active_assignments' => 1,
                ]
            ]);
    }

    /**
     * Test reimbursement calculation
     */
    public function test_reimbursement_calculation(): void
    {
        $plan = InsurancePlan::factory()->create([
            'name' => 'Reimbursement Test Plan',
            'reimbursement_percentage' => 80,
            'maximum_yearly_amount' => 10000,
            'deductible_amount' => 500,
        ]);

        $this->actingAs($this->user);

        $response = $this->getJson("/api/insurance/plans/{$plan->id}/calculate-reimbursement?amount=2000");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'plan_id' => $plan->id,
                    'claimed_amount' => 2000,
                    'deductible' => 500,
                    'reimbursement_percentage' => 80,
                ]
            ]);

        // Expected reimbursement: (2000 - 500) * 0.8 = 1200
        $responseData = $response->json('data');
        $this->assertEquals(1200, $responseData['calculated_reimbursement']);
    }

    /**
     * Test service coverage check
     */
    public function test_service_coverage_check(): void
    {
        $plan = InsurancePlan::factory()->create([
            'name' => 'Coverage Test Plan',
            'covered_services' => ['consultation', 'medication', 'surgery'],
            'excluded_services' => ['cosmetic', 'experimental'],
        ]);

        $this->actingAs($this->user);

        // Test covered service
        $response = $this->getJson("/api/insurance/plans/{$plan->id}/check-coverage?service=consultation");
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'service' => 'consultation',
                    'is_covered' => true,
                ]
            ]);

        // Test excluded service
        $response = $this->getJson("/api/insurance/plans/{$plan->id}/check-coverage?service=cosmetic");
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'service' => 'cosmetic',
                    'is_covered' => false,
                ]
            ]);
    }

    /**
     * Test dashboard statistics
     */
    public function test_dashboard_statistics(): void
    {
        // Create multiple employees with different scores
        $employees = Employee::factory(5)->create();
        
        $scoreService = new EmployeeScoreService();
        foreach ($employees as $employee) {
            $scoreService->calculateScore($employee);
        }

        $this->actingAs($this->user);

        $response = $this->getJson('/api/employees/scores/dashboard');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_employees' => 6, // Including our test employee
                    ],
                    'at_risk_employees' => [],
                    'excellent_employees' => [],
                ]
            ]);
    }
}