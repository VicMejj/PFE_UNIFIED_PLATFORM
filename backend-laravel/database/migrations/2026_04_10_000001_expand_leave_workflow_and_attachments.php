<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            if (! Schema::hasColumn('leaves', 'duration_type')) {
                $table->string('duration_type', 32)->default('full_day')->after('end_date');
            }

            if (! Schema::hasColumn('leaves', 'approval_stage')) {
                $table->string('approval_stage', 32)->nullable()->after('status');
            }
        });

        Schema::create('leave_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_id')->constrained('leaves')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type', 120)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_attachments');

        Schema::table('leaves', function (Blueprint $table) {
            if (Schema::hasColumn('leaves', 'approval_stage')) {
                $table->dropColumn('approval_stage');
            }

            if (Schema::hasColumn('leaves', 'duration_type')) {
                $table->dropColumn('duration_type');
            }
        });
    }
};
