<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [

            // Employees
            'view employees',
            'create employees',
            'edit employees',
            'delete employees',

            // Branches
            'view branches',
            'create branches',
            'edit branches',
            'delete branches',

            // Departments
            'view departments',
            'create departments',
            'edit departments',
            'delete departments',

            // Designations
            'view designations',
            'create designations',
            'edit designations',
            'delete designations',

            // Documents
            'view documents',
            'create documents',
            'edit documents',
            'delete documents',

            // Roles
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',

            // Permissions
            'view permissions',
            'assign permissions',

            // Insurance Providers
            'view insurance providers',
            'create insurance providers',
            'edit insurance providers',
            'delete insurance providers',

            // Insurance Policies
            'view insurance policies',
            'create insurance policies',
            'edit insurance policies',
            'delete insurance policies',

            // Insurance Enrollments
            'view insurance enrollments',
            'create insurance enrollments',
            'edit insurance enrollments',
            'delete insurance enrollments',

            // Insurance Claims
            'view insurance claims',
            'create insurance claims',
            'edit insurance claims',
            'delete insurance claims',

            // Insurance Dependents
            'view insurance dependents',
            'create insurance dependents',
            'edit insurance dependents',
            'delete insurance dependents',

            // Insurance Premiums
            'view insurance premiums',
            'create insurance premiums',
            'edit insurance premiums',
            'delete insurance premiums',

            // Insurance Bordereaux
            'view insurance',
            'create insurance',
            'edit insurance',
            'delete insurance',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'api'
            ]);
        }
    }
}
