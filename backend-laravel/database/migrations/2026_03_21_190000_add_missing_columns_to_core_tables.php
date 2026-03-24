<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('languages', function (Blueprint $table) {
            if (!Schema::hasColumn('languages', 'code')) {
                $table->string('code', 10)->nullable()->after('id');
            }
            if (!Schema::hasColumn('languages', 'name')) {
                $table->string('name')->nullable()->after('code');
            }
            if (!Schema::hasColumn('languages', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('name');
            }
        });

        Schema::table('assets', function (Blueprint $table) {
            if (!Schema::hasColumn('assets', 'asset_name')) {
                $table->string('asset_name')->nullable()->after('id');
            }
            if (!Schema::hasColumn('assets', 'asset_type')) {
                $table->string('asset_type')->nullable()->after('asset_name');
            }
            if (!Schema::hasColumn('assets', 'purchase_date')) {
                $table->date('purchase_date')->nullable()->after('asset_type');
            }
            if (!Schema::hasColumn('assets', 'purchase_price')) {
                $table->decimal('purchase_price', 12, 2)->nullable()->after('purchase_date');
            }
            if (!Schema::hasColumn('assets', 'depreciation_rate')) {
                $table->decimal('depreciation_rate', 5, 2)->nullable()->after('purchase_price');
            }
            if (!Schema::hasColumn('assets', 'current_value')) {
                $table->decimal('current_value', 12, 2)->nullable()->after('depreciation_rate');
            }
            if (!Schema::hasColumn('assets', 'status')) {
                $table->string('status')->nullable()->after('current_value');
            }
            if (!Schema::hasColumn('assets', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('languages', function (Blueprint $table) {
            $drop = [];
            foreach (['code', 'name', 'is_active'] as $column) {
                if (Schema::hasColumn('languages', $column)) {
                    $drop[] = $column;
                }
            }
            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });

        Schema::table('assets', function (Blueprint $table) {
            $drop = [];
            foreach (['asset_name', 'asset_type', 'purchase_date', 'purchase_price', 'depreciation_rate', 'current_value', 'status', 'notes'] as $column) {
                if (Schema::hasColumn('assets', $column)) {
                    $drop[] = $column;
                }
            }
            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
