<?php

namespace Database\Seeders;

use App\Models\Designation;
use Illuminate\Database\Seeder;

class DesignationSeeder extends Seeder
{
    public function run(): void
    {
        $designations = [
            ['title' => 'CEO'],
            ['title' => 'Manager'],
            ['title' => 'Senior Staff'],
            ['title' => 'Staff'],
            ['title' => 'Junior Staff'],
            ['title' => 'Intern'],
        ];

        foreach ($designations as $designation) {
            Designation::create($designation);
        }
    }
}
