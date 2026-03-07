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
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'api'
            ]);
        }
    }
}