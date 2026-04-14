<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurance_claims', function (Blueprint $table) {
            if (!Schema::hasColumn('insurance_claims', 'sent_to_payroll_at')) {
                $table->timestamp('sent_to_payroll_at')->nullable()->after('sent_to_provider_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('insurance_claims', function (Blueprint $table) {
            $table->dropColumn(['sent_to_payroll_at']);
        });
    }
};
