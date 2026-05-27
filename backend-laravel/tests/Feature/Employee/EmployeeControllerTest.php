<?php

namespace Tests\Feature\Employee;

use App\Models\Employee\Employee;
use App\Models\Organization\Branch;
use App\Models\Organization\Department;
use App\Models\Organization\Designation;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class EmployeeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_an_employee_profile_for_an_existing_user_and_sync_roles(): void
    {
        $adminRole = Role::findOrCreate('admin', 'api');
        $employeeRole = Role::findOrCreate('user', 'api');
        $managerRole = Role::findOrCreate('manager', 'api');

        $createEmployeesPermission = Permission::findOrCreate('create employees', 'api');
        $adminRole->givePermissionTo($createEmployeesPermission);

        $admin = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('secret123'),
            'type' => 'admin',
            'avatar' => 'avatars/default.png',
            'lang' => 'en',
            'is_active' => true,
            'created_by' => 1,
        ]);
        $admin->assignRole($adminRole);

        $pendingUser = User::query()->create([
            'name' => 'Pending Manager',
            'email' => 'pending.manager@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('secret123'),
            'type' => 'user',
            'avatar' => 'avatars/default.png',
            'lang' => 'en',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
        $pendingUser->assignRole($employeeRole);

        $branch = Branch::query()->create([
            'name' => 'HQ',
            'code' => 'HQ',
            'description' => 'Main office',
        ]);
        $department = Department::query()->create([
            'name' => 'Operations',
            'code' => 'OPS',
            'description' => 'Operations team',
        ]);
        $designation = Designation::query()->create([
            'title' => 'Manager',
            'code' => 'MGR',
            'description' => 'Team manager',
        ]);

        $response = $this
            ->withToken(JWTAuth::fromUser($admin))
            ->postJson('/api/employees', [
                'user_id' => $pendingUser->id,
                'name' => 'Pending Manager',
                'email' => 'pending.manager@example.com',
                'gender' => 'female',
                'address' => '123 Employee Lane',
                'branch_id' => $branch->id,
                'department_id' => $department->id,
                'designation_id' => $designation->id,
                'salary' => 64000,
                'roles' => [$managerRole->name],
            ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user_id', $pendingUser->id)
            ->assertJsonPath('data.email', 'pending.manager@example.com')
            ->assertJsonPath('data.branch_id', $branch->id)
            ->assertJsonPath('data.department_id', $department->id)
            ->assertJsonPath('data.designation_id', $designation->id);

        $this->assertDatabaseHas('employees', [
            'user_id' => $pendingUser->id,
            'email' => 'pending.manager@example.com',
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
        ]);

        $this->assertTrue($pendingUser->fresh()->hasRole($managerRole->name));
        $this->assertCount(1, Employee::query()->where('user_id', $pendingUser->id)->get());
    }
}
