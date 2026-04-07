<?php

namespace Database\Seeders;

use App\Models\Payroll\AllowanceOption;
use Illuminate\Database\Seeder;

class AllowanceOptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $allowanceOptions = [
            [
                'name' => 'Health Insurance',
                'description' => 'Comprehensive health insurance coverage for employees and their families.',
                'is_active' => true,
            ],
            [
                'name' => 'Dental Coverage',
                'description' => 'Dental care and orthodontic coverage.',
                'is_active' => true,
            ],
            [
                'name' => 'Vision Insurance',
                'description' => 'Eye care and vision correction coverage.',
                'is_active' => true,
            ],
            [
                'name' => 'Retirement Plan (401k)',
                'description' => 'Tax-deferred retirement savings plan with employer matching.',
                'is_active' => true,
            ],
            [
                'name' => 'Paid Time Off (PTO)',
                'description' => 'Flexible PTO policy including vacation, sick days, and personal time.',
                'is_active' => true,
            ],
            [
                'name' => 'Life Insurance',
                'description' => 'Life insurance coverage for employee and beneficiaries.',
                'is_active' => true,
            ],
            [
                'name' => 'Gym Membership',
                'description' => 'Subsidized fitness center and wellness program membership.',
                'is_active' => true,
            ],
            [
                'name' => 'Professional Development',
                'description' => 'Annual budget for training, certifications, and professional courses.',
                'is_active' => true,
            ],
            [
                'name' => 'Transportation Allowance',
                'description' => 'Monthly public transportation or parking allowance.',
                'is_active' => true,
            ],
            [
                'name' => 'Meal Vouchers',
                'description' => 'Daily meal vouchers or subsidized cafeteria access.',
                'is_active' => true,
            ],
            [
                'name' => 'Remote Work Stipend',
                'description' => 'Monthly allowance for home office equipment and internet.',
                'is_active' => true,
            ],
            [
                'name' => 'Child Care Assistance',
                'description' => 'Subsidized or on-site child care services.',
                'is_active' => true,
            ],
        ];

        foreach ($allowanceOptions as $option) {
            AllowanceOption::updateOrCreate(
                ['name' => $option['name']],
                $option
            );
        }
    }
}
