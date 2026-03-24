<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            ['name' => 'Main Office', 'code' => 'HQ', 'description' => 'New York, NY'],
            ['name' => 'Sales Branch', 'code' => 'SALES', 'description' => 'Los Angeles, CA'],
            ['name' => 'Tech Hub', 'code' => 'TECH', 'description' => 'San Francisco, CA'],
            ['name' => 'Customer Service', 'code' => 'CS', 'description' => 'Chicago, IL'],
            ['name' => 'Operations', 'code' => 'OPS', 'description' => 'Houston, TX'],
        ];

        foreach ($branches as $branch) {
            Branch::updateOrCreate(['name' => $branch['name']], $branch);
        }
    }
}
