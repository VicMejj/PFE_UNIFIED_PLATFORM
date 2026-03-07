<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $employees = [
            [
                'name' => 'John CEO',
                'email' => 'ceo@example.com',
                'employee_id' => 'EMP001',
                'branch_id' => 1,
                'department_id' => 1,
                'designation_id' => 1,
                'phone' => '+1234567890',
                'dob' => '1985-01-15',
                'gender' => 'Male',
                'address' => '123 Main St, New York, NY',
                'company_doj' => '2020-01-01',
                'salary' => 150000,
                'is_active' => 1,
            ],
            [
                'name' => 'Jane Manager',
                'email' => 'manager@example.com',
                'employee_id' => 'EMP002',
                'branch_id' => 1,
                'department_id' => 2,
                'designation_id' => 2,
                'phone' => '+1234567891',
                'dob' => '1990-05-20',
                'gender' => 'Female',
                'address' => '456 Oak Ave, New York, NY',
                'company_doj' => '2021-03-15',
                'salary' => 95000,
                'is_active' => 1,
            ],
            [
                'name' => 'Bob Developer',
                'email' => 'developer@example.com',
                'employee_id' => 'EMP003',
                'branch_id' => 3,
                'department_id' => 2,
                'designation_id' => 3,
                'phone' => '+1234567892',
                'dob' => '1992-08-10',
                'gender' => 'Male',
                'address' => '789 Pine Rd, San Francisco, CA',
                'company_doj' => '2022-06-01',
                'salary' => 85000,
                'is_active' => 1,
            ],
            [
                'name' => 'Alice HR',
                'email' => 'hr@example.com',
                'employee_id' => 'EMP004',
                'branch_id' => 1,
                'department_id' => 1,
                'designation_id' => 3,
                'phone' => '+1234567893',
                'dob' => '1988-12-25',
                'gender' => 'Female',
                'address' => '321 Elm St, New York, NY',
                'company_doj' => '2021-09-01',
                'salary' => 75000,
                'is_active' => 1,
            ],
            [
                'name' => 'Charlie Accountant',
                'email' => 'accountant@example.com',
                'employee_id' => 'EMP005',
                'branch_id' => 1,
                'department_id' => 3,
                'designation_id' => 3,
                'phone' => '+1234567894',
                'dob' => '1991-03-30',
                'gender' => 'Male',
                'address' => '654 Maple Dr, New York, NY',
                'company_doj' => '2021-02-15',
                'salary' => 72000,
                'is_active' => 1,
            ],
        ];

        foreach ($employees as $empData) {
            $user = User::create([
                'name' => $empData['name'],
                'email' => $empData['email'],
                'password' => Hash::make('password123'),
                'type' => 'employee',
                'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($empData['name']),
                'lang' => 'en',
                'created_by' => 'admin',
                'email_verified_at' => now(),
            ]);

            $empData['user_id'] = $user->id;
            $empData['created_by'] = $user->id;
            $empData['password'] = 'password123';
            Employee::create($empData);
        }
    }
}
