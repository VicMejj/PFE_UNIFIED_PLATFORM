<?php

namespace Tests\Feature;

use App\Models\Contract\Contract;
use App\Models\Employee\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ContractAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_contract_index_only_returns_their_signed_contracts(): void
    {
        [$user, $employee] = $this->createUserWithEmployee('me.contracts@example.com');
        [, $otherEmployee] = $this->createUserWithEmployee('other.contracts@example.com');

        $mySignedContract = $this->createContract($employee->id, 'My Signed Contract', 'signed');
        $this->createContract($employee->id, 'My Pending Contract', 'pending');
        $this->createContract($otherEmployee->id, 'Other Signed Contract', 'signed');

        $response = $this->actingAs($user, 'api')->getJson('/api/contracts');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $mySignedContract->id)
            ->assertJsonPath('data.data.0.employee_id', $employee->id)
            ->assertJsonPath('data.data.0.status', 'signed');
    }

    public function test_employee_cannot_view_or_download_another_employees_signed_contract(): void
    {
        [$user] = $this->createUserWithEmployee('viewer.contracts@example.com');
        [, $otherEmployee] = $this->createUserWithEmployee('owner.contracts@example.com');

        $otherContract = $this->createContract($otherEmployee->id, 'Foreign Signed Contract', 'signed');

        $this->actingAs($user, 'api')
            ->getJson("/api/contracts/{$otherContract->id}")
            ->assertStatus(404);

        $this->actingAs($user, 'api')
            ->get("/api/contracts/{$otherContract->id}/download")
            ->assertStatus(404);
    }

    public function test_employee_cannot_review_another_employees_contract_even_with_verification_code(): void
    {
        [$user] = $this->createUserWithEmployee('intruder.contracts@example.com');
        [, $otherEmployee] = $this->createUserWithEmployee('owner.review@example.com');

        $otherContract = $this->createContract($otherEmployee->id, 'Pending Contract', 'pending', [
            'verification_code' => 'VERIFY42',
            'verification_token' => str_repeat('a', 64),
            'token_expires_at' => now()->addDay(),
            'signing_deadline' => now()->addDay(),
        ]);

        $response = $this->actingAs($user, 'api')->postJson("/api/contracts/{$otherContract->id}/view", [
            'verification_code' => 'VERIFY42',
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_employee_cannot_manage_contract_records(): void
    {
        [$user, $employee] = $this->createUserWithEmployee('employee.manage.contracts@example.com');
        $contract = $this->createContract($employee->id, 'Self Contract', 'signed');

        $this->actingAs($user, 'api')
            ->patchJson("/api/contracts/{$contract->id}", [
                'contract_name' => 'Tampered Contract',
            ])
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->actingAs($user, 'api')
            ->postJson("/api/contracts/{$contract->id}/assign")
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_admin_can_still_list_all_contracts(): void
    {
        $admin = $this->createRoleUser('admin', 'admin.contracts@example.com');
        [, $employee] = $this->createUserWithEmployee('employee.one@example.com');
        [, $otherEmployee] = $this->createUserWithEmployee('employee.two@example.com');

        $this->createContract($employee->id, 'Employee One Contract', 'signed');
        $this->createContract($otherEmployee->id, 'Employee Two Contract', 'signed');

        $response = $this->actingAs($admin, 'api')->getJson('/api/contracts');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.data');
    }

    private function createRoleUser(string $role, string $email): User
    {
        Role::findOrCreate($role, 'api');

        $user = User::factory()->create([
            'email' => $email,
            'password' => Hash::make('password'),
        ]);

        $user->assignRole($role);

        return $user;
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
            'address' => '123 Contract Street',
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

    private function createContract(int $employeeId, string $name, string $status, array $overrides = []): Contract
    {
        return Contract::query()->create(array_merge([
            'employee_id' => $employeeId,
            'contract_name' => $name,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => $status,
            'notes' => 'Test contract',
        ], $overrides));
    }
}
