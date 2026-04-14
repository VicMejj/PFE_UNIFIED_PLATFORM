<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('allowances', function (Blueprint $table) {
            if (!Schema::hasColumn('allowances', 'claimed')) {
                $table->boolean('claimed')->default(false)->after('status');
            }
            if (!Schema::hasColumn('allowances', 'claimed_at')) {
                $table->timestamp('claimed_at')->nullable()->after('claimed');
            }
            if (!Schema::hasColumn('allowances', 'claimed_by')) {
                $table->foreignId('claimed_by')->nullable()->constrained('users')->onDelete('set null')->after('claimed_at');
            }
            if (!Schema::hasColumn('allowances', 'status_changed_at')) {
                $table->timestamp('status_changed_at')->nullable()->after('end_date');
            }
            if (!Schema::hasColumn('allowances', 'status_changed_by')) {
                $table->foreignId('status_changed_by')->nullable()->constrained('users')->onDelete('set null')->after('status_changed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('allowances', function (Blueprint $table) {
            $columns = ['claimed', 'claimed_at', 'claimed_by', 'status_changed_at', 'status_changed_by'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('allowances', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
