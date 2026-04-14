<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_sheets', function (Blueprint $table) {
            if (! Schema::hasColumn('time_sheets', 'status')) {
                $table->string('status', 32)->default('present')->after('overtime_hours');
            }
        });
    }

    public function down(): void
    {
        Schema::table('time_sheets', function (Blueprint $table) {
            if (Schema::hasColumn('time_sheets', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
