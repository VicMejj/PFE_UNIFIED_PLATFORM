<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

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
        ]);

        // Seed master data
        $this->seedLeaveTypes();
        $this->seedAwardTypes();
        $this->seedTerminationTypes();
        $this->seedPaymentTypes();
        $this->seedExpenseTypes();
        $this->seedTrainingTypes();
        $this->seedPerformanceTypes();
    }

    private function seedLeaveTypes(): void
    {
        $types = ['Annual Leave', 'Sick Leave', 'Casual Leave', 'Maternity Leave', 'Paternity Leave'];
        foreach ($types as $type) {
            \App\Models\LeaveType::firstOrCreate(['name' => $type]);
        }
    }

    private function seedAwardTypes(): void
    {
        $types = ['Excellence', 'Achievement', 'Innovation', 'Team Work', 'Customer Service'];
        foreach ($types as $type) {
            \App\Models\AwardType::firstOrCreate(['name' => $type]);
        }
    }

    private function seedTerminationTypes(): void
    {
        $types = ['Resignation', 'Termination', 'Retirement', 'Voluntary Exit', 'Contract End'];
        foreach ($types as $type) {
            \App\Models\TerminationType::firstOrCreate(['name' => $type]);
        }
    }

    private function seedPaymentTypes(): void
    {
        $types = ['Salary', 'Bonus', 'Commission', 'Overtime', 'Allowance'];
        foreach ($types as $type) {
            \App\Models\PaymentType::firstOrCreate(['name' => $type]);
        }
    }

    private function seedExpenseTypes(): void
    {
        $types = ['Office Supplies', 'Travel', 'Meals', 'Equipment', 'Training', 'Utilities'];
        foreach ($types as $type) {
            \App\Models\ExpenseType::firstOrCreate(['name' => $type]);
        }
    }

    private function seedTrainingTypes(): void
    {
        $types = ['Technical', 'Soft Skills', 'Leadership', 'Compliance', 'Other'];
        foreach ($types as $type) {
            \App\Models\TrainingType::firstOrCreate(['name' => $type]);
        }
    }

    private function seedPerformanceTypes(): void
    {
        $types = ['Excellent', 'Good', 'Average', 'Needs Improvement', 'Poor'];
        foreach ($types as $type) {
            \App\Models\PerformanceType::firstOrCreate(['name' => $type]);
        }
    }
}