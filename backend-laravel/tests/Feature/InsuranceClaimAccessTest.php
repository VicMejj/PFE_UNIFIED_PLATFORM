<?php

namespace Tests\Feature;

use App\Models\Employee\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InsuranceClaimAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_claims_returns_only_the_authenticated_employees_claims(): void
    {
        [$user, $employee] = $this->createUserWithEmployee('me@example.com');
        [, $otherEmployee] = $this->createUserWithEmployee('other@example.com');

        $myEnrollmentId = $this->createEnrollmentForEmployee($employee->id);
        $otherEnrollmentId = $this->createEnrollmentForEmployee($otherEmployee->id);

        $myClaimId = $this->createClaim($myEnrollmentId, $employee->id, 'CLM-ME-0001');
        $this->createClaim($otherEnrollmentId, $otherEmployee->id, 'CLM-OTHER-0001');

        $response = $this->actingAs($user, 'api')->getJson('/api/insurance/claims/my');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $myClaimId)
            ->assertJsonPath('data.0.enrollment.employee_id', $employee->id);
    }

    public function test_employee_cannot_view_another_employees_claim_details(): void
    {
        [$user] = $this->createUserWithEmployee('viewer@example.com');
        [, $otherEmployee] = $this->createUserWithEmployee('owner@example.com');

        $otherEnrollmentId = $this->createEnrollmentForEmployee($otherEmployee->id);
        $otherClaimId = $this->createClaim($otherEnrollmentId, $otherEmployee->id, 'CLM-OTHER-DETAIL');

        $response = $this->actingAs($user, 'api')->getJson("/api/insurance/claims/{$otherClaimId}");

        $response->assertStatus(404);
    }

    public function test_employee_cannot_submit_claim_for_another_employees_enrollment(): void
    {
        [$user] = $this->createUserWithEmployee('submitter@example.com');
        [, $otherEmployee] = $this->createUserWithEmployee('foreign@example.com');

        $otherEnrollmentId = $this->createEnrollmentForEmployee($otherEmployee->id);

        $response = $this->actingAs($user, 'api')->postJson('/api/insurance/claims', [
            'enrollment_id' => $otherEnrollmentId,
            'claim_date' => now()->toDateString(),
            'claimed_amount' => 150,
            'total_amount' => 150,
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    private function createUserWithEmployee(string $email): array
    {
        $user = User::factory()->create([
            'email' => $email,
            'password' => Hash::make('password'),
        ]);

        $branchId = DB::table('branches')->insertGetId([
            'name' => 'Main Branch ' . substr(md5($email), 0, 6),
            'code' => strtoupper(substr(md5($email), 0, 4)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $departmentId = DB::table('departments')->insertGetId([
            'name' => 'Operations ' . substr(md5($email), 0, 6),
            'code' => strtoupper(substr(md5($email), 6, 4)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $designationId = DB::table('designations')->insertGetId([
            'title' => 'Analyst ' . substr(md5($email), 0, 6),
            'code' => strtoupper(substr(md5($email), 10, 4)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $employee = Employee::create([
            'user_id' => $user->id,
            'name' => 'Employee ' . $email,
            'email' => $email,
            'gender' => 'male',
            'address' => '123 Claim Street',
            'password' => Hash::make('password'),
            'employee_id' => 'EMP-' . strtoupper(substr(sha1($email), 0, 8)),
            'branch_id' => $branchId,
            'department_id' => $departmentId,
            'designation_id' => $designationId,
            'company_doj' => now()->subYear()->toDateString(),
            'salary' => 0,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        return [$user, $employee];
    }

    private function createEnrollmentForEmployee(int $employeeId): int
    {
        $providerId = DB::table('insurance_providers')->insertGetId([
            'name' => 'Provider ' . $employeeId,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $policyId = DB::table('insurance_policies')->insertGetId([
            'provider_id' => $providerId,
            'name' => 'Policy ' . $employeeId,
            'premium' => 100,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('insurance_enrollments')->insertGetId([
            'employee_id' => $employeeId,
            'policy_id' => $policyId,
            'start_date' => now()->subMonth()->toDateString(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createClaim(int $enrollmentId, int $employeeId, string $claimNumber): int
    {
        return DB::table('insurance_claims')->insertGetId([
            'enrollment_id' => $enrollmentId,
            'employee_id' => $employeeId,
            'claim_number' => $claimNumber,
            'status' => 'pending',
            'claim_date' => now()->toDateString(),
            'date_filed' => now()->toDateString(),
            'claimed_amount' => 100,
            'total_amount' => 100,
            'created_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
