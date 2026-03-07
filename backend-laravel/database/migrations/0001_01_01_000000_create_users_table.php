<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('type');
            $table->string('avatar')->default(config('chatify.user_avatar.default'));
            $table->string('lang');
            $table->integer('plan')->nullable();
            $table->date('plan_expire_date')->nullable();
            $table->integer('requested_plan')->default(0);
            $table->date('trial_expire_date')->nullable();
            $table->integer('trial_plan')->default(0);
            $table->integer('is_login_enable')->default(1);
            $table->float('storage_limit', 20, 2)->default(0.00);
            $table->timestamp('last_login')->nullable();
            $table->integer('is_active')->default('1');
            $table->integer('referral_code')->default(0);
            $table->integer('used_referral_code')->default(0);
            $table->integer('commission_amount')->default(0);
            $table->boolean('active_status')->default(0);
            $table->integer('is_disable')->default('1');
            $table->string('google2fa_secret')->nullable();
            $table->integer('google2fa_enable')->default(0);
            $table->boolean('dark_mode')->default(0);
            $table->string('messenger_color')->default('#2180f3');
            $table->string('created_by');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
