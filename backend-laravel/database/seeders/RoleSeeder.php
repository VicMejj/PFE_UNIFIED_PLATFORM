<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'api'
        ]);

        $manager = Role::firstOrCreate([
            'name' => 'manager',
            'guard_name' => 'api'
        ]);

        $rh = Role::firstOrCreate([
            'name' => 'rh',
            'guard_name' => 'api'
        ]);

        $user = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'api'
        ]);

        // Get all permissions
        $allPermissions = Permission::pluck('name')->toArray();

        /*
        |--------------------------------------------------------------------------
        | ADMIN = ALL PERMISSIONS
        |--------------------------------------------------------------------------
        */
        $admin->syncPermissions($allPermissions);

        /*
        |--------------------------------------------------------------------------
        | MANAGER
        |--------------------------------------------------------------------------
        */
        $manager->syncPermissions([
            'view employees',
            'create employees',
            'edit employees',

            'view branches',
            'view departments',
            'view documents'
        ]);

        /*
        |--------------------------------------------------------------------------
        | RH (HR)
        |--------------------------------------------------------------------------
        */
        $rh->syncPermissions([
            'view employees',
            'create employees',
            'edit employees',
            'delete employees',

            'view branches',
            'view departments',

            'view designations',
            'create designations',
            'edit designations',

            'view documents',
            'create documents',
            'edit documents',
        ]);

        /*
        |--------------------------------------------------------------------------
        | USER
        |--------------------------------------------------------------------------
        */
        $user->syncPermissions([
            'view employees',
            'view branches',
            'view departments',
            'view documents'
        ]);
    }
}