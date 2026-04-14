<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration enhances the Insurance and Benefits modules with:
     * - Employee Score System (AI-powered eligibility scoring)
     * - Enhanced Insurance Plan Management
     * - Benefit Request Workflow
     * - Enhanced Notification System
     * - Audit Logging
     */
    public function up(): void
    {
        // ==========================================
        // EMPLOYEE SCORE SYSTEM (AI-Powered)
        // ==========================================
        Schema::create('employee_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->decimal('overall_score', 5, 2)->default(0.00)->comment('Score from 0-100');
            $table->decimal('attendance_score', 5, 2)->default(0.00);
            $table->decimal('performance_score', 5, 2)->default(0.00);
            $table->decimal('discipline_score', 5, 2)->default(0.00);
            $table->decimal('seniority_score', 5, 2)->default(0.00);
            $table->decimal('engagement_score', 5, 2)->default(0.00);
            $table->string('score_tier', 20)->default('medium')->comment('excellent, good, medium, risk');
            $table->json('score_factors')->nullable()->comment('Detailed breakdown of score calculation');
            $table->json('improvement_suggestions')->nullable()->comment('AI-generated suggestions to improve score');
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();
            
            $table->index(['employee_id', 'score_tier']);
            $table->index('overall_score');
        });

        // Score history for trend analysis
        Schema::create('employee_score_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('employee_score_id')->nullable()->constrained('employee_scores')->onDelete('set null');
            $table->decimal('overall_score', 5, 2);
            $table->string('score_tier', 20);
            $table->string('change_reason', 100)->nullable();
            $table->json('previous_scores')->nullable();
            $table->timestamps();
            
            $table->index(['employee_id', 'created_at']);
        });

        // ==========================================
        // ENHANCED INSURANCE PLAN MANAGEMENT
        // ==========================================
        Schema::create('insurance_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('plan_code', 50)->unique();
            $table->foreignId('provider_id')->nullable()->constrained('insurance_providers')->onDelete('set null');
            $table->string('coverage_type', 50)->default('individual')->comment('individual, family, group');
            $table->string('insurance_type', 50)->default('health')->comment('health, dental, vision, life, disability');
            $table->decimal('reimbursement_percentage', 5, 2)->default(80.00)->comment('Percentage reimbursed (0-100)');
            $table->decimal('maximum_yearly_amount', 12, 2)->default(0.00)->comment('Max yearly coverage amount');
            $table->decimal('deductible_amount', 12, 2)->default(0.00)->comment('Annual deductible');
            $table->integer('waiting_period_days')->default(0);
            $table->json('covered_services')->nullable()->comment('List of covered services/categories');
            $table->json('excluded_services')->nullable()->comment('List of excluded services');
            $table->json('required_documents')->nullable()->comment('Required documents for claims');
            $table->json('conditions')->nullable()->comment('Special conditions/rules');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('effective_date')->nullable();
            $table->date('expiration_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();
            
            $table->index(['is_active', 'coverage_type']);
            $table->index('insurance_type');
        });

        // Enhanced insurance assignments with bulk support
        Schema::create('insurance_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurance_plan_id')->constrained('insurance_plans')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('assignment_type', 30)->default('individual')->comment('individual, bulk, department');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->date('effective_date');
            $table->date('end_date')->nullable();
            $table->string('status', 30)->default('active')->comment('active, inactive, pending, terminated');
            $table->json('dependents_covered')->nullable();
            $table->decimal('employee_contribution', 10, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['insurance_plan_id', 'employee_id', 'effective_date']);
            $table->index(['employee_id', 'status']);
            $table->index(['department_id', 'status']);
        });

        // Assignment history for audit trail
        Schema::create('insurance_assignment_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurance_assignment_id')->constrained('insurance_assignments')->onDelete('cascade');
            $table->foreignId('changed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action', 50)->comment('assigned, modified, terminated, reactivated');
            $table->json('previous_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        // ==========================================
        // ENHANCED INSURANCE REQUEST/CLAIM WORKFLOW
        // ==========================================
        // Add new columns to existing insurance_claims table
        Schema::table('insurance_claims', function (Blueprint $table) {
            if (!Schema::hasColumn('insurance_claims', 'category')) {
                $table->string('category', 50)->nullable()->after('service_type');
            }
            if (!Schema::hasColumn('insurance_claims', 'hospital_provider')) {
                $table->string('hospital_provider', 255)->nullable()->after('description');
            }
            if (!Schema::hasColumn('insurance_claims', 'workflow_status')) {
                $table->string('workflow_status', 30)->default('draft')->after('status');
            }
            if (!Schema::hasColumn('insurance_claims', 'reimbursement_amount')) {
                $table->decimal('reimbursement_amount', 12, 2)->default(0.00)->after('approved_amount');
            }
            if (!Schema::hasColumn('insurance_claims', 'reimbursement_percentage')) {
                $table->decimal('reimbursement_percentage', 5, 2)->nullable()->after('reimbursement_amount');
            }
            if (!Schema::hasColumn('insurance_claims', 'missing_documents')) {
                $table->json('missing_documents')->nullable()->after('rejection_reason');
            }
            if (!Schema::hasColumn('insurance_claims', 'auto_calculated')) {
                $table->boolean('auto_calculated')->default(false)->after('missing_documents');
            }
            if (!Schema::hasColumn('insurance_claims', 'fraud_score')) {
                $table->decimal('fraud_score', 5, 3)->nullable()->after('auto_calculated');
            }
            if (!Schema::hasColumn('insurance_claims', 'sent_to_provider_at')) {
                $table->timestamp('sent_to_provider_at')->nullable()->after('payment_date');
            }
            if (!Schema::hasColumn('insurance_claims', 'provider_reference')) {
                $table->string('provider_reference', 100)->nullable()->after('sent_to_provider_at');
            }
        });

        // ==========================================
        // BENEFIT REQUEST WORKFLOW
        // ==========================================
        Schema::create('benefit_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('allowance_option_id')->constrained('allowance_options')->onDelete('cascade');
            $table->string('request_number', 50)->unique();
            $table->string('status', 30)->default('submitted')->comment('draft, submitted, under_review, approved, rejected, delivered, cancelled');
            $table->decimal('requested_amount', 10, 2)->default(0.00);
            $table->decimal('approved_amount', 10, 2)->default(0.00);
            $table->text('reason')->nullable();
            $table->json('supporting_documents')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('review_comments')->nullable();
            $table->text('approval_comments')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('workflow_history')->nullable();
            $table->boolean('is_auto_approved')->default(false);
            $table->string('auto_approval_reason', 100)->nullable();
            $table->timestamps();
            
            $table->index(['employee_id', 'status']);
            $table->index(['allowance_option_id', 'status']);
            $table->index('status');
        });

        // Benefit request attachments
        Schema::create('benefit_request_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('benefit_request_id')->constrained('benefit_requests')->onDelete('cascade');
            $table->string('document_type', 50);
            $table->string('document_name');
            $table->string('file_path');
            $table->string('file_mime_type', 100);
            $table->integer('file_size');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // ==========================================
        // ENHANCED BENEFIT ELIGIBILITY RULES
        // ==========================================
        Schema::table('benefit_eligibility_rules', function (Blueprint $table) {
            if (!Schema::hasColumn('benefit_eligibility_rules', 'department_id')) {
                $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('cascade');
            }
            if (!Schema::hasColumn('benefit_eligibility_rules', 'min_tenure_months')) {
                $table->integer('min_tenure_months')->default(0);
            }
            if (!Schema::hasColumn('benefit_eligibility_rules', 'max_requests_per_year')) {
                $table->integer('max_requests_per_year')->default(0);
            }
            if (!Schema::hasColumn('benefit_eligibility_rules', 'auto_approve_threshold')) {
                $table->decimal('auto_approve_threshold', 5, 3)->nullable();
            }
            if (!Schema::hasColumn('benefit_eligibility_rules', 'budget_limit')) {
                $table->decimal('budget_limit', 12, 2)->default(0.00);
            }
            if (!Schema::hasColumn('benefit_eligibility_rules', 'budget_used')) {
                $table->decimal('budget_used', 12, 2)->default(0.00);
            }
        });

        // ==========================================
        // ENHANCED NOTIFICATION SYSTEM
        // ==========================================
        Schema::table('notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('notifications', 'notifiable_type')) {
                $table->string('notifiable_type')->nullable()->after('type');
            }
            if (!Schema::hasColumn('notifications', 'notifiable_id')) {
                $table->unsignedBigInteger('notifiable_id')->nullable()->after('notifiable_type');
            }
            if (!Schema::hasColumn('notifications', 'priority')) {
                $table->string('priority', 20)->default('normal')->after('payload');
            }
            if (!Schema::hasColumn('notifications', 'category')) {
                $table->string('category', 50)->nullable()->after('priority');
            }
            if (!Schema::hasColumn('notifications', 'action_url')) {
                $table->string('action_url', 500)->nullable()->after('category');
            }
            if (!Schema::hasColumn('notifications', 'email_sent')) {
                $table->boolean('email_sent')->default(false)->after('read_at');
            }
            if (!Schema::hasColumn('notifications', 'email_sent_at')) {
                $table->timestamp('email_sent_at')->nullable()->after('email_sent');
            }
            
            $table->index(['notifiable_type', 'notifiable_id']);
            $table->index(['category', 'created_at']);
        });

        // Notification preferences
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('category', 50);
            $table->boolean('email_enabled')->default(true);
            $table->boolean('in_app_enabled')->default(true);
            $table->boolean('sms_enabled')->default(false);
            $table->timestamps();
            
            $table->unique(['user_id', 'category']);
        });

        // ==========================================
        // AUDIT LOG SYSTEM
        // ==========================================
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('event', 50)->comment('created, updated, deleted, approved, rejected, etc.');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
            
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['user_id', 'created_at']);
            $table->index('event');
        });

        // ==========================================
        // INSURANCE DASHBOARD CACHE
        // ==========================================
        Schema::create('insurance_dashboard_cache', function (Blueprint $table) {
            $table->id();
            $table->string('cache_key', 100)->unique();
            $table->json('data');
            $table->timestamp('expires_at');
            $table->timestamps();
            
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_dashboard_cache');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('notification_preferences');
        
        Schema::table('notifications', function (Blueprint $table) {
            if (Schema::hasColumn('notifications', 'notifiable_type')) {
                $table->dropColumn(['notifiable_type', 'notifiable_id', 'priority', 'category', 'action_url', 'email_sent', 'email_sent_at']);
            }
        });
        
        Schema::table('benefit_eligibility_rules', function (Blueprint $table) {
            if (Schema::hasColumn('benefit_eligibility_rules', 'department_id')) {
                $table->dropColumn(['department_id', 'min_tenure_months', 'max_requests_per_year', 'auto_approve_threshold', 'budget_limit', 'budget_used']);
            }
        });
        
        Schema::dropIfExists('benefit_request_documents');
        Schema::dropIfExists('benefit_requests');
        
        Schema::table('insurance_claims', function (Blueprint $table) {
            $columns = ['category', 'hospital_provider', 'workflow_status', 'reimbursement_amount', 'reimbursement_percentage', 'missing_documents', 'auto_calculated', 'fraud_score', 'sent_to_provider_at', 'provider_reference'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('insurance_claims', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
        
        Schema::dropIfExists('insurance_assignment_histories');
        Schema::dropIfExists('insurance_assignments');
        Schema::dropIfExists('insurance_plans');
        Schema::dropIfExists('employee_score_histories');
        Schema::dropIfExists('employee_scores');
    }
};