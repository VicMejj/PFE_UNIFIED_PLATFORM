<?php

namespace Database\Seeders;

use App\Models\Misc\Asset;
use App\Models\Misc\Language;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class CoreDataSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            ['code' => 'en', 'name' => 'English', 'is_active' => true],
            ['code' => 'fr', 'name' => 'French', 'is_active' => true],
            ['code' => 'es', 'name' => 'Spanish', 'is_active' => false],
        ];

        foreach ($languages as $language) {
            Language::updateOrCreate(
                ['code' => $language['code']],
                $language
            );
        }

        $settings = [
            ['key' => 'company.name', 'value' => 'Unified Platform', 'type' => 'string', 'category' => 'company'],
            ['key' => 'company.email', 'value' => 'admin@example.com', 'type' => 'string', 'category' => 'company'],
            ['key' => 'company.phone', 'value' => '+1-555-0100', 'type' => 'string', 'category' => 'company'],
            ['key' => 'company.address', 'value' => '123 Main St, New York, NY', 'type' => 'string', 'category' => 'company'],
            ['key' => 'system.timezone', 'value' => 'UTC', 'type' => 'string', 'category' => 'system'],
            ['key' => 'system.locale', 'value' => 'en', 'type' => 'string', 'category' => 'system'],
            ['key' => 'system.currency', 'value' => 'USD', 'type' => 'string', 'category' => 'system'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }

        Asset::firstOrCreate(
            ['asset_name' => 'Default Office Laptop'],
            [
                'asset_type' => 'equipment',
                'purchase_date' => now()->toDateString(),
                'purchase_price' => 1200,
                'depreciation_rate' => 10,
                'current_value' => 1200,
                'status' => 'active',
                'notes' => 'Seeded default asset',
            ]
        );
    }
}
