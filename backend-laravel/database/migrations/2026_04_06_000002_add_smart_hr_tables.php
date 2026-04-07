<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            if (! Schema::hasColumn('leaves', 'total_days')) {
                $table->unsignedInteger('total_days')->default(1)->after('end_date');
            }
            if (! Schema::hasColumn('leaves', 'approval_probability')) {
                $table->decimal('approval_probability', 5, 3)->nullable()->after('total_days');
            }
            if (! Schema::hasColumn('leaves', 'ai_suggestion_score')) {
                $table->decimal('ai_suggestion_score', 5, 3)->nullable()->after('approval_probability');
            }
            if (! Schema::hasColumn('leaves', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('approved_by');
            }
            if (! Schema::hasColumn('leaves', 'policy_violations')) {
                $table->json('policy_violations')->nullable()->after('reason');
            }
        });

        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'status')) {
                $table->string('status')->default('draft')->after('contract_document_path');
            }
            if (! Schema::hasColumn('contracts', 'verification_token')) {
                $table->string('verification_token', 64)->nullable()->unique()->after('status');
            }
            if (! Schema::hasColumn('contracts', 'token_expires_at')) {
                $table->timestamp('token_expires_at')->nullable()->after('verification_token');
            }
            if (! Schema::hasColumn('contracts', 'signing_deadline')) {
                $table->timestamp('signing_deadline')->nullable()->after('token_expires_at');
            }
            if (! Schema::hasColumn('contracts', 'viewed_at')) {
                $table->timestamp('viewed_at')->nullable()->after('signing_deadline');
            }
            if (! Schema::hasColumn('contracts', 'signed_at')) {
                $table->timestamp('signed_at')->nullable()->after('viewed_at');
            }
            if (! Schema::hasColumn('contracts', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('signed_at');
            }
            if (! Schema::hasColumn('contracts', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('rejected_at');
            }
            if (! Schema::hasColumn('contracts', 'signed_ip')) {
                $table->string('signed_ip', 45)->nullable()->after('rejection_reason');
            }
        });

        Schema::create('contract_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->onDelete('cascade');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action', 50);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('benefit_eligibility_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('allowance_option_id')->constrained('allowance_options')->onDelete('cascade');
            $table->string('rule_type', 50);
            $table->decimal('threshold', 10, 2)->default(0);
            $table->decimal('weight', 5, 3)->default(1.0);
            $table->timestamps();
        });

        Schema::create('employee_benefit_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('allowance_option_id')->constrained('allowance_options')->onDelete('cascade');
            $table->decimal('score', 5, 3)->default(0.0);
            $table->string('status', 30)->default('not_eligible');
            $table->json('gap_actions')->nullable();
            $table->unsignedInteger('estimated_months_to_qualify')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type', 80);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->json('target_roles')->nullable();
            $table->json('payload')->nullable();
            $table->string('channel', 20)->default('in_app');
            $table->timestamp('read_at')->nullable();
            $table->string('dedup_key', 128)->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('ai_prediction_outcomes', function (Blueprint $table) {
            $table->id();
            $table->string('prediction_type', 50);
            $table->unsignedBigInteger('entity_id');
            $table->decimal('predicted_score', 5, 3)->nullable();
            $table->string('actual_outcome', 50)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_prediction_outcomes');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('employee_benefit_recommendations');
        Schema::dropIfExists('benefit_eligibility_rules');
        Schema::dropIfExists('contract_audit_logs');

        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'signed_ip')) {
                $table->dropColumn('signed_ip');
            }
            if (Schema::hasColumn('contracts', 'rejection_reason')) {
                $table->dropColumn('rejection_reason');
            }
            if (Schema::hasColumn('contracts', 'rejected_at')) {
                $table->dropColumn('rejected_at');
            }
            if (Schema::hasColumn('contracts', 'signed_at')) {
                $table->dropColumn('signed_at');
            }
            if (Schema::hasColumn('contracts', 'viewed_at')) {
                $table->dropColumn('viewed_at');
            }
            if (Schema::hasColumn('contracts', 'signing_deadline')) {
                $table->dropColumn('signing_deadline');
            }
            if (Schema::hasColumn('contracts', 'token_expires_at')) {
                $table->dropColumn('token_expires_at');
            }
            if (Schema::hasColumn('contracts', 'verification_token')) {
                $table->dropUnique(['verification_token']);
                $table->dropColumn('verification_token');
            }
            if (Schema::hasColumn('contracts', 'status')) {
                $table->dropColumn('status');
            }
        });

        Schema::table('leaves', function (Blueprint $table) {
            if (Schema::hasColumn('leaves', 'policy_violations')) {
                $table->dropColumn('policy_violations');
            }
            if (Schema::hasColumn('leaves', 'rejection_reason')) {
                $table->dropColumn('rejection_reason');
            }
            if (Schema::hasColumn('leaves', 'ai_suggestion_score')) {
                $table->dropColumn('ai_suggestion_score');
            }
            if (Schema::hasColumn('leaves', 'approval_probability')) {
                $table->dropColumn('approval_probability');
            }
            if (Schema::hasColumn('leaves', 'total_days')) {
                $table->dropColumn('total_days');
            }
        });
    }
};