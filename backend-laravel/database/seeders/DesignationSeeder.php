<?php

namespace Database\Seeders;

use App\Models\Designation;
use Illuminate\Database\Seeder;

class DesignationSeeder extends Seeder
{
    public function run(): void
    {
        $designations = [
            ['title' => 'CEO', 'code' => 'CEO', 'description' => 'Chief Executive Officer'],
            ['title' => 'Manager', 'code' => 'MGR', 'description' => 'Team or department manager'],
            ['title' => 'Senior Staff', 'code' => 'SR', 'description' => 'Senior individual contributor'],
            ['title' => 'Staff', 'code' => 'STF', 'description' => 'Staff individual contributor'],
            ['title' => 'Junior Staff', 'code' => 'JR', 'description' => 'Junior individual contributor'],
            ['title' => 'Intern', 'code' => 'INT', 'description' => 'Internship role'],
        ];

        foreach ($designations as $designation) {
            Designation::updateOrCreate(['title' => $designation['title']], $designation);
        }
    }
}
