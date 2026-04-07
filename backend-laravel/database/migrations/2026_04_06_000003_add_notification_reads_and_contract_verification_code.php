<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'verification_code')) {
                $table->string('verification_code', 20)->nullable()->after('verification_token');
                $table->index('verification_code');
            }
        });

        Schema::table('notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('notifications', 'target_user_ids')) {
                $table->json('target_user_ids')->nullable()->after('target_roles');
            }
        });

        if (! Schema::hasTable('notification_reads')) {
            Schema::create('notification_reads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('notification_id')->constrained('notifications')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                $table->unique(['notification_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_reads');

        Schema::table('notifications', function (Blueprint $table) {
            if (Schema::hasColumn('notifications', 'target_user_ids')) {
                $table->dropColumn('target_user_ids');
            }
        });

        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'verification_code')) {
                $table->dropIndex(['verification_code']);
                $table->dropColumn('verification_code');
            }
        });
    }
};
