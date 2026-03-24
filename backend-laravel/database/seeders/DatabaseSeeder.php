<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Employee\AwardType;
use App\Models\Employee\TerminationType;
use App\Models\Finance\ExpenseType;
use App\Models\Finance\PaymentType;
use App\Models\Leave\LeaveType;
use App\Models\Performance\PerformanceType;
use App\Models\Performance\TrainingType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Call seeders in order
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            BranchSeeder::class,
            DepartmentSeeder::class,
            DesignationSeeder::class,
            EmployeeSeeder::class,
            CoreDataSeeder::class,
        ]);

        // Seed master data
        $this->seedLeaveTypes();
        $this->seedAwardTypes();
        $this->seedTerminationTypes();
        $this->seedPaymentTypes();
        $this->seedExpenseTypes();
        $this->seedTrainingTypes();
        $this->seedPerformanceTypes();
        $this->ensureAdminUser();

        // Seed demo data across modules
        $this->call(DemoDataSeeder::class);
    }

    private function ensureAdminUser(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'type' => 'admin',
                'avatar' => 'avatar.png',
                'lang' => 'en',
                'is_active' => 1,
                'created_by' => 1,
            ]
        );

        if (\App\Models\Role::where('name', 'admin')->where('guard_name', 'api')->exists()) {
            $admin->syncRoles(['admin']);
        }
    }

    private function seedLeaveTypes(): void
    {
        $types = [
            [
                'name' => 'Annual Leave',
                'description' => 'Paid annual leave for full-time employees.',
                'leave_code' => 'AL',
                'maximum_days' => 20,
                'is_active' => true,
            ],
            [
                'name' => 'Sick Leave',
                'description' => 'Medical leave for illness or recovery.',
                'leave_code' => 'SL',
                'maximum_days' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Casual Leave',
                'description' => 'Short notice personal leave.',
                'leave_code' => 'CL',
                'maximum_days' => 7,
                'is_active' => true,
            ],
            [
                'name' => 'Maternity Leave',
                'description' => 'Leave for maternity and recovery.',
                'leave_code' => 'ML',
                'maximum_days' => 90,
                'is_active' => true,
            ],
            [
                'name' => 'Paternity Leave',
                'description' => 'Leave for new fathers.',
                'leave_code' => 'PL',
                'maximum_days' => 14,
                'is_active' => true,
            ],
        ];

        foreach ($types as $type) {
            LeaveType::updateOrCreate(['name' => $type['name']], $type);
        }
    }

    private function seedAwardTypes(): void
    {
        $types = [
            ['name' => 'Excellence', 'description' => 'Exceptional performance', 'is_active' => true],
            ['name' => 'Achievement', 'description' => 'Major milestone delivery', 'is_active' => true],
            ['name' => 'Innovation', 'description' => 'Innovative ideas and execution', 'is_active' => true],
            ['name' => 'Team Work', 'description' => 'Strong collaboration', 'is_active' => true],
            ['name' => 'Customer Service', 'description' => 'Outstanding customer support', 'is_active' => true],
        ];

        foreach ($types as $type) {
            AwardType::updateOrCreate(['name' => $type['name']], $type);
        }
    }

    private function seedTerminationTypes(): void
    {
        $types = [
            ['name' => 'Resignation', 'description' => 'Employee resigned', 'is_active' => true],
            ['name' => 'Termination', 'description' => 'Employment terminated', 'is_active' => true],
            ['name' => 'Retirement', 'description' => 'Retirement from service', 'is_active' => true],
            ['name' => 'Voluntary Exit', 'description' => 'Voluntary separation', 'is_active' => true],
            ['name' => 'Contract End', 'description' => 'Contract completed', 'is_active' => true],
        ];

        foreach ($types as $type) {
            TerminationType::updateOrCreate(['name' => $type['name']], $type);
        }
    }

    private function seedPaymentTypes(): void
    {
        $types = [
            ['name' => 'Salary', 'description' => 'Regular salary payment', 'is_active' => true],
            ['name' => 'Bonus', 'description' => 'Bonus payment', 'is_active' => true],
            ['name' => 'Commission', 'description' => 'Commission payment', 'is_active' => true],
            ['name' => 'Overtime', 'description' => 'Overtime compensation', 'is_active' => true],
            ['name' => 'Allowance', 'description' => 'Fixed allowance payment', 'is_active' => true],
        ];

        foreach ($types as $type) {
            PaymentType::updateOrCreate(['name' => $type['name']], $type);
        }
    }

    private function seedExpenseTypes(): void
    {
        $types = [
            ['name' => 'Office Supplies', 'description' => 'Stationery and supplies', 'is_active' => true],
            ['name' => 'Travel', 'description' => 'Business travel expenses', 'is_active' => true],
            ['name' => 'Meals', 'description' => 'Meals and refreshments', 'is_active' => true],
            ['name' => 'Equipment', 'description' => 'Equipment purchases', 'is_active' => true],
            ['name' => 'Training', 'description' => 'Training and courses', 'is_active' => true],
            ['name' => 'Utilities', 'description' => 'Utilities and services', 'is_active' => true],
        ];

        foreach ($types as $type) {
            ExpenseType::updateOrCreate(['name' => $type['name']], $type);
        }
    }

    private function seedTrainingTypes(): void
    {
        $types = [
            ['name' => 'Technical', 'description' => 'Technical training', 'is_active' => true],
            ['name' => 'Soft Skills', 'description' => 'Soft skills training', 'is_active' => true],
            ['name' => 'Leadership', 'description' => 'Leadership training', 'is_active' => true],
            ['name' => 'Compliance', 'description' => 'Compliance training', 'is_active' => true],
            ['name' => 'Other', 'description' => 'Other training programs', 'is_active' => true],
        ];

        foreach ($types as $type) {
            TrainingType::updateOrCreate(['name' => $type['name']], $type);
        }
    }

    private function seedPerformanceTypes(): void
    {
        $types = [
            ['name' => 'Excellent', 'description' => 'Top performance', 'is_active' => true],
            ['name' => 'Good', 'description' => 'Strong performance', 'is_active' => true],
            ['name' => 'Average', 'description' => 'Meets expectations', 'is_active' => true],
            ['name' => 'Needs Improvement', 'description' => 'Below expectations', 'is_active' => true],
            ['name' => 'Poor', 'description' => 'Unsatisfactory performance', 'is_active' => true],
        ];

        foreach ($types as $type) {
            PerformanceType::updateOrCreate(['name' => $type['name']], $type);
        }
    }
}
