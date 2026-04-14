<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['branches', 'departments', 'designations'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (! Schema::hasColumn($table, 'is_active')) {
                    $blueprint->boolean('is_active')->default(true)->after('description');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['branches', 'departments', 'designations'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (Schema::hasColumn($table, 'is_active')) {
                    $blueprint->dropColumn('is_active');
                }
            });
        }
    }
};
