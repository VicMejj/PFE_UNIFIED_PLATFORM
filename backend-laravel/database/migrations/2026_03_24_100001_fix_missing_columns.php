<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('leave_types')) {
            Schema::table('leave_types', function (Blueprint $table) {
                if (! Schema::hasColumn('leave_types', 'name')) {
                    $table->string('name')->nullable();
                }
                if (! Schema::hasColumn('leave_types', 'description')) {
                    $table->text('description')->nullable();
                }
                if (! Schema::hasColumn('leave_types', 'leave_code')) {
                    $table->string('leave_code')->nullable();
                }
                if (! Schema::hasColumn('leave_types', 'maximum_days')) {
                    $table->integer('maximum_days')->nullable();
                }
                if (! Schema::hasColumn('leave_types', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
            });
        }

        if (Schema::hasTable('award_types')) {
            Schema::table('award_types', function (Blueprint $table) {
                if (! Schema::hasColumn('award_types', 'name')) {
                    $table->string('name')->nullable();
                }
                if (! Schema::hasColumn('award_types', 'description')) {
                    $table->text('description')->nullable();
                }
                if (! Schema::hasColumn('award_types', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
            });
        }

        if (Schema::hasTable('termination_types')) {
            Schema::table('termination_types', function (Blueprint $table) {
                if (! Schema::hasColumn('termination_types', 'name')) {
                    $table->string('name')->nullable();
                }
                if (! Schema::hasColumn('termination_types', 'description')) {
                    $table->text('description')->nullable();
                }
                if (! Schema::hasColumn('termination_types', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
            });
        }

        if (Schema::hasTable('payment_types')) {
            Schema::table('payment_types', function (Blueprint $table) {
                if (! Schema::hasColumn('payment_types', 'name')) {
                    $table->string('name')->nullable();
                }
                if (! Schema::hasColumn('payment_types', 'description')) {
                    $table->text('description')->nullable();
                }
                if (! Schema::hasColumn('payment_types', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
            });
        }

        if (Schema::hasTable('expense_types')) {
            Schema::table('expense_types', function (Blueprint $table) {
                if (! Schema::hasColumn('expense_types', 'name')) {
                    $table->string('name')->nullable();
                }
                if (! Schema::hasColumn('expense_types', 'description')) {
                    $table->text('description')->nullable();
                }
                if (! Schema::hasColumn('expense_types', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
            });
        }

        if (Schema::hasTable('training_types')) {
            Schema::table('training_types', function (Blueprint $table) {
                if (! Schema::hasColumn('training_types', 'name')) {
                    $table->string('name')->nullable();
                }
                if (! Schema::hasColumn('training_types', 'description')) {
                    $table->text('description')->nullable();
                }
                if (! Schema::hasColumn('training_types', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
            });
        }

        if (Schema::hasTable('performance_types')) {
            Schema::table('performance_types', function (Blueprint $table) {
                if (! Schema::hasColumn('performance_types', 'name')) {
                    $table->string('name')->nullable();
                }
                if (! Schema::hasColumn('performance_types', 'description')) {
                    $table->text('description')->nullable();
                }
                if (! Schema::hasColumn('performance_types', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
            });
        }

        if (Schema::hasTable('goal_types')) {
            Schema::table('goal_types', function (Blueprint $table) {
                if (! Schema::hasColumn('goal_types', 'name')) {
                    $table->string('name')->nullable();
                }
                if (! Schema::hasColumn('goal_types', 'description')) {
                    $table->text('description')->nullable();
                }
                if (! Schema::hasColumn('goal_types', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
            });
        }

        if (Schema::hasTable('contract_types')) {
            Schema::table('contract_types', function (Blueprint $table) {
                if (! Schema::hasColumn('contract_types', 'name')) {
                    $table->string('name')->nullable();
                }
                if (! Schema::hasColumn('contract_types', 'description')) {
                    $table->text('description')->nullable();
                }
                if (! Schema::hasColumn('contract_types', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
            });
        }

        if (Schema::hasTable('income_types')) {
            Schema::table('income_types', function (Blueprint $table) {
                if (! Schema::hasColumn('income_types', 'name')) {
                    $table->string('name')->nullable();
                }
                if (! Schema::hasColumn('income_types', 'description')) {
                    $table->text('description')->nullable();
                }
                if (! Schema::hasColumn('income_types', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
            });
        }

        if (Schema::hasTable('awards')) {
            Schema::table('awards', function (Blueprint $table) {
                if (! Schema::hasColumn('awards', 'award_type_id')) {
                    $table->unsignedBigInteger('award_type_id')->nullable();
                }
                if (! Schema::hasColumn('awards', 'gift')) {
                    $table->string('gift')->nullable();
                }
                if (! Schema::hasColumn('awards', 'cash_price')) {
                    $table->decimal('cash_price', 12, 2)->nullable();
                }
            });
        }

        if (Schema::hasTable('transfers')) {
            Schema::table('transfers', function (Blueprint $table) {
                if (! Schema::hasColumn('transfers', 'employee_id')) {
                    $table->unsignedBigInteger('employee_id')->nullable();
                }
                if (! Schema::hasColumn('transfers', 'from_branch_id')) {
                    $table->unsignedBigInteger('from_branch_id')->nullable();
                }
                if (! Schema::hasColumn('transfers', 'to_branch_id')) {
                    $table->unsignedBigInteger('to_branch_id')->nullable();
                }
                if (! Schema::hasColumn('transfers', 'from_department_id')) {
                    $table->unsignedBigInteger('from_department_id')->nullable();
                }
                if (! Schema::hasColumn('transfers', 'to_department_id')) {
                    $table->unsignedBigInteger('to_department_id')->nullable();
                }
                if (! Schema::hasColumn('transfers', 'transfer_date')) {
                    $table->date('transfer_date')->nullable();
                }
                if (! Schema::hasColumn('transfers', 'reason')) {
                    $table->text('reason')->nullable();
                }
                if (! Schema::hasColumn('transfers', 'remarks')) {
                    $table->text('remarks')->nullable();
                }
            });
        }

        if (Schema::hasTable('allowances')) {
            Schema::table('allowances', function (Blueprint $table) {
                if (! Schema::hasColumn('allowances', 'employee_id')) {
                    $table->unsignedBigInteger('employee_id')->nullable();
                }
                if (! Schema::hasColumn('allowances', 'allowance_option_id')) {
                    $table->unsignedBigInteger('allowance_option_id')->nullable();
                }
                if (! Schema::hasColumn('allowances', 'amount')) {
                    $table->decimal('amount', 12, 2)->nullable();
                }
                if (! Schema::hasColumn('allowances', 'start_date')) {
                    $table->date('start_date')->nullable();
                }
                if (! Schema::hasColumn('allowances', 'end_date')) {
                    $table->date('end_date')->nullable();
                }
                if (! Schema::hasColumn('allowances', 'status')) {
                    $table->string('status')->nullable();
                }
            });
        }

        if (Schema::hasTable('loans')) {
            Schema::table('loans', function (Blueprint $table) {
                if (! Schema::hasColumn('loans', 'employee_id')) {
                    $table->unsignedBigInteger('employee_id')->nullable();
                }
                if (! Schema::hasColumn('loans', 'loan_option_id')) {
                    $table->unsignedBigInteger('loan_option_id')->nullable();
                }
                if (! Schema::hasColumn('loans', 'principal_amount')) {
                    $table->decimal('principal_amount', 12, 2)->nullable();
                }
                if (! Schema::hasColumn('loans', 'rate_of_interest')) {
                    $table->decimal('rate_of_interest', 8, 2)->nullable();
                }
                if (! Schema::hasColumn('loans', 'approved_amount')) {
                    $table->decimal('approved_amount', 12, 2)->nullable();
                }
                if (! Schema::hasColumn('loans', 'loan_start_date')) {
                    $table->date('loan_start_date')->nullable();
                }
                if (! Schema::hasColumn('loans', 'loan_end_date')) {
                    $table->date('loan_end_date')->nullable();
                }
                if (! Schema::hasColumn('loans', 'emi_amount')) {
                    $table->decimal('emi_amount', 12, 2)->nullable();
                }
                if (! Schema::hasColumn('loans', 'remaining_balance')) {
                    $table->decimal('remaining_balance', 12, 2)->nullable();
                }
                if (! Schema::hasColumn('loans', 'status')) {
                    $table->string('status')->nullable();
                }
                if (! Schema::hasColumn('loans', 'notes')) {
                    $table->text('notes')->nullable();
                }
            });
        }

        if (Schema::hasTable('appraisals')) {
            Schema::table('appraisals', function (Blueprint $table) {
                if (! Schema::hasColumn('appraisals', 'employee_id')) {
                    $table->unsignedBigInteger('employee_id')->nullable();
                }
                if (! Schema::hasColumn('appraisals', 'appraisal_year')) {
                    $table->integer('appraisal_year')->nullable();
                }
                if (! Schema::hasColumn('appraisals', 'performance_type_id')) {
                    $table->unsignedBigInteger('performance_type_id')->nullable();
                }
                if (! Schema::hasColumn('appraisals', 'rating')) {
                    $table->decimal('rating', 5, 2)->nullable();
                }
                if (! Schema::hasColumn('appraisals', 'comments')) {
                    $table->text('comments')->nullable();
                }
                if (! Schema::hasColumn('appraisals', 'reviewed_by')) {
                    $table->unsignedBigInteger('reviewed_by')->nullable();
                }
                if (! Schema::hasColumn('appraisals', 'review_date')) {
                    $table->date('review_date')->nullable();
                }
                if (! Schema::hasColumn('appraisals', 'status')) {
                    $table->string('status')->nullable();
                }
            });
        }

        if (Schema::hasTable('jobs')) {
            Schema::table('jobs', function (Blueprint $table) {
                if (! Schema::hasColumn('jobs', 'job_title')) {
                    $table->string('job_title')->nullable();
                }
                if (! Schema::hasColumn('jobs', 'job_category_id')) {
                    $table->unsignedBigInteger('job_category_id')->nullable();
                }
                if (! Schema::hasColumn('jobs', 'description')) {
                    $table->text('description')->nullable();
                }
                if (! Schema::hasColumn('jobs', 'required_experience')) {
                    $table->string('required_experience')->nullable();
                }
                if (! Schema::hasColumn('jobs', 'required_qualifications')) {
                    $table->text('required_qualifications')->nullable();
                }
                if (! Schema::hasColumn('jobs', 'salary_from')) {
                    $table->decimal('salary_from', 12, 2)->nullable();
                }
                if (! Schema::hasColumn('jobs', 'salary_to')) {
                    $table->decimal('salary_to', 12, 2)->nullable();
                }
                if (! Schema::hasColumn('jobs', 'positions_available')) {
                    $table->integer('positions_available')->nullable();
                }
                if (! Schema::hasColumn('jobs', 'job_location')) {
                    $table->string('job_location')->nullable();
                }
                if (! Schema::hasColumn('jobs', 'posted_date')) {
                    $table->date('posted_date')->nullable();
                }
                if (! Schema::hasColumn('jobs', 'application_deadline')) {
                    $table->date('application_deadline')->nullable();
                }
                if (! Schema::hasColumn('jobs', 'status')) {
                    $table->string('status')->nullable();
                }
            });
        }

        if (Schema::hasTable('job_applications')) {
            Schema::table('job_applications', function (Blueprint $table) {
                if (! Schema::hasColumn('job_applications', 'job_id')) {
                    $table->unsignedBigInteger('job_id')->nullable();
                }
                if (! Schema::hasColumn('job_applications', 'job_stage_id')) {
                    $table->unsignedBigInteger('job_stage_id')->nullable();
                }
                if (! Schema::hasColumn('job_applications', 'applicant_name')) {
                    $table->string('applicant_name')->nullable();
                }
                if (! Schema::hasColumn('job_applications', 'applicant_email')) {
                    $table->string('applicant_email')->nullable();
                }
                if (! Schema::hasColumn('job_applications', 'applicant_phone')) {
                    $table->string('applicant_phone')->nullable();
                }
                if (! Schema::hasColumn('job_applications', 'resume_path')) {
                    $table->string('resume_path')->nullable();
                }
                if (! Schema::hasColumn('job_applications', 'application_date')) {
                    $table->date('application_date')->nullable();
                }
                if (! Schema::hasColumn('job_applications', 'status')) {
                    $table->string('status')->nullable();
                }
                if (! Schema::hasColumn('job_applications', 'score')) {
                    $table->decimal('score', 5, 2)->nullable();
                }
            });
        }

        if (Schema::hasTable('insurance_policies')) {
            Schema::table('insurance_policies', function (Blueprint $table) {
                if (! Schema::hasColumn('insurance_policies', 'policy_number')) {
                    $table->string('policy_number')->nullable();
                }
                if (! Schema::hasColumn('insurance_policies', 'policy_name')) {
                    $table->string('policy_name')->nullable();
                }
                if (! Schema::hasColumn('insurance_policies', 'policy_type')) {
                    $table->string('policy_type')->nullable();
                }
                if (! Schema::hasColumn('insurance_policies', 'start_date')) {
                    $table->date('start_date')->nullable();
                }
                if (! Schema::hasColumn('insurance_policies', 'end_date')) {
                    $table->date('end_date')->nullable();
                }
                if (! Schema::hasColumn('insurance_policies', 'premium_amount')) {
                    $table->decimal('premium_amount', 12, 2)->nullable();
                }
                if (! Schema::hasColumn('insurance_policies', 'coverage_amount')) {
                    $table->decimal('coverage_amount', 12, 2)->nullable();
                }
                if (! Schema::hasColumn('insurance_policies', 'eligibility_criteria')) {
                    $table->json('eligibility_criteria')->nullable();
                }
                if (! Schema::hasColumn('insurance_policies', 'waiting_period_days')) {
                    $table->integer('waiting_period_days')->nullable();
                }
                if (! Schema::hasColumn('insurance_policies', 'claim_settlement_days')) {
                    $table->integer('claim_settlement_days')->nullable();
                }
                if (! Schema::hasColumn('insurance_policies', 'is_family_coverage')) {
                    $table->boolean('is_family_coverage')->default(false);
                }
                if (! Schema::hasColumn('insurance_policies', 'remarks')) {
                    $table->text('remarks')->nullable();
                }
                if (! Schema::hasColumn('insurance_policies', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable();
                }
            });
        }

        if (Schema::hasTable('insurance_enrollments')) {
            Schema::table('insurance_enrollments', function (Blueprint $table) {
                if (! Schema::hasColumn('insurance_enrollments', 'enrollment_date')) {
                    $table->date('enrollment_date')->nullable();
                }
                if (! Schema::hasColumn('insurance_enrollments', 'effective_date')) {
                    $table->date('effective_date')->nullable();
                }
                if (! Schema::hasColumn('insurance_enrollments', 'employee_contribution')) {
                    $table->decimal('employee_contribution', 12, 2)->nullable();
                }
                if (! Schema::hasColumn('insurance_enrollments', 'employer_contribution')) {
                    $table->decimal('employer_contribution', 12, 2)->nullable();
                }
                if (! Schema::hasColumn('insurance_enrollments', 'premium_amount')) {
                    $table->decimal('premium_amount', 12, 2)->nullable();
                }
                if (! Schema::hasColumn('insurance_enrollments', 'enrollment_number')) {
                    $table->string('enrollment_number')->nullable();
                }
                if (! Schema::hasColumn('insurance_enrollments', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable();
                }
            });
        }

        if (Schema::hasTable('insurance_claims')) {
            Schema::table('insurance_claims', function (Blueprint $table) {
                if (! Schema::hasColumn('insurance_claims', 'employee_id')) {
                    $table->unsignedBigInteger('employee_id')->nullable();
                }
                if (! Schema::hasColumn('insurance_claims', 'dependent_id')) {
                    $table->unsignedBigInteger('dependent_id')->nullable();
                }
                if (! Schema::hasColumn('insurance_claims', 'provider_id')) {
                    $table->unsignedBigInteger('provider_id')->nullable();
                }
                if (! Schema::hasColumn('insurance_claims', 'claim_date')) {
                    $table->date('claim_date')->nullable();
                }
                if (! Schema::hasColumn('insurance_claims', 'service_date')) {
                    $table->date('service_date')->nullable();
                }
                if (! Schema::hasColumn('insurance_claims', 'description')) {
                    $table->text('description')->nullable();
                }
                if (! Schema::hasColumn('insurance_claims', 'claimed_amount')) {
                    $table->decimal('claimed_amount', 12, 2)->nullable();
                }
                if (! Schema::hasColumn('insurance_claims', 'approved_amount')) {
                    $table->decimal('approved_amount', 12, 2)->nullable();
                }
                if (! Schema::hasColumn('insurance_claims', 'patient_type')) {
                    $table->string('patient_type')->nullable();
                }
                if (! Schema::hasColumn('insurance_claims', 'service_type')) {
                    $table->string('service_type')->nullable();
                }
                if (! Schema::hasColumn('insurance_claims', 'treatment_details')) {
                    $table->text('treatment_details')->nullable();
                }
                if (! Schema::hasColumn('insurance_claims', 'reviewed_at')) {
                    $table->timestamp('reviewed_at')->nullable();
                }
                if (! Schema::hasColumn('insurance_claims', 'reviewed_by')) {
                    $table->unsignedBigInteger('reviewed_by')->nullable();
                }
                if (! Schema::hasColumn('insurance_claims', 'review_comments')) {
                    $table->text('review_comments')->nullable();
                }
                if (! Schema::hasColumn('insurance_claims', 'approved_at')) {
                    $table->timestamp('approved_at')->nullable();
                }
                if (! Schema::hasColumn('insurance_claims', 'approved_by')) {
                    $table->unsignedBigInteger('approved_by')->nullable();
                }
                if (! Schema::hasColumn('insurance_claims', 'approval_comments')) {
                    $table->text('approval_comments')->nullable();
                }
                if (! Schema::hasColumn('insurance_claims', 'payment_date')) {
                    $table->date('payment_date')->nullable();
                }
                if (! Schema::hasColumn('insurance_claims', 'payment_reference')) {
                    $table->string('payment_reference')->nullable();
                }
                if (! Schema::hasColumn('insurance_claims', 'rejection_reason')) {
                    $table->text('rejection_reason')->nullable();
                }
                if (! Schema::hasColumn('insurance_claims', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable();
                }
            });
        }

        if (Schema::hasTable('insurance_claim_items')) {
            Schema::table('insurance_claim_items', function (Blueprint $table) {
                if (! Schema::hasColumn('insurance_claim_items', 'item_type')) {
                    $table->string('item_type')->nullable();
                }
                if (! Schema::hasColumn('insurance_claim_items', 'quantity')) {
                    $table->decimal('quantity', 8, 2)->nullable();
                }
                if (! Schema::hasColumn('insurance_claim_items', 'unit_price')) {
                    $table->decimal('unit_price', 12, 2)->nullable();
                }
                if (! Schema::hasColumn('insurance_claim_items', 'status')) {
                    $table->string('status')->nullable();
                }
                if (! Schema::hasColumn('insurance_claim_items', 'remarks')) {
                    $table->text('remarks')->nullable();
                }
            });
        }

        if (Schema::hasTable('insurance_claim_documents')) {
            Schema::table('insurance_claim_documents', function (Blueprint $table) {
                if (! Schema::hasColumn('insurance_claim_documents', 'document_type')) {
                    $table->string('document_type')->nullable();
                }
                if (! Schema::hasColumn('insurance_claim_documents', 'document_name')) {
                    $table->string('document_name')->nullable();
                }
                if (! Schema::hasColumn('insurance_claim_documents', 'file_size')) {
                    $table->integer('file_size')->nullable();
                }
                if (! Schema::hasColumn('insurance_claim_documents', 'uploaded_by')) {
                    $table->unsignedBigInteger('uploaded_by')->nullable();
                }
                if (! Schema::hasColumn('insurance_claim_documents', 'uploaded_at')) {
                    $table->timestamp('uploaded_at')->nullable();
                }
                if (! Schema::hasColumn('insurance_claim_documents', 'remarks')) {
                    $table->text('remarks')->nullable();
                }
            });
        }

        if (Schema::hasTable('insurance_claim_history')) {
            Schema::table('insurance_claim_history', function (Blueprint $table) {
                if (! Schema::hasColumn('insurance_claim_history', 'changed_by')) {
                    $table->unsignedBigInteger('changed_by')->nullable();
                }
                if (! Schema::hasColumn('insurance_claim_history', 'changed_at')) {
                    $table->timestamp('changed_at')->nullable();
                }
                if (! Schema::hasColumn('insurance_claim_history', 'remarks')) {
                    $table->text('remarks')->nullable();
                }
            });
        }

        if (Schema::hasTable('insurance_bordereaux')) {
            Schema::table('insurance_bordereaux', function (Blueprint $table) {
                if (! Schema::hasColumn('insurance_bordereaux', 'policy_id')) {
                    $table->unsignedBigInteger('policy_id')->nullable();
                }
                if (! Schema::hasColumn('insurance_bordereaux', 'bordereau_number')) {
                    $table->string('bordereau_number')->nullable();
                }
                if (! Schema::hasColumn('insurance_bordereaux', 'bordereau_date')) {
                    $table->date('bordereau_date')->nullable();
                }
                if (! Schema::hasColumn('insurance_bordereaux', 'period_from')) {
                    $table->date('period_from')->nullable();
                }
                if (! Schema::hasColumn('insurance_bordereaux', 'period_to')) {
                    $table->date('period_to')->nullable();
                }
                if (! Schema::hasColumn('insurance_bordereaux', 'total_claims_count')) {
                    $table->integer('total_claims_count')->nullable();
                }
                if (! Schema::hasColumn('insurance_bordereaux', 'total_claimed_amount')) {
                    $table->decimal('total_claimed_amount', 12, 2)->nullable();
                }
                if (! Schema::hasColumn('insurance_bordereaux', 'total_approved_amount')) {
                    $table->decimal('total_approved_amount', 12, 2)->nullable();
                }
                if (! Schema::hasColumn('insurance_bordereaux', 'prepared_by')) {
                    $table->unsignedBigInteger('prepared_by')->nullable();
                }
                if (! Schema::hasColumn('insurance_bordereaux', 'prepared_at')) {
                    $table->timestamp('prepared_at')->nullable();
                }
                if (! Schema::hasColumn('insurance_bordereaux', 'validated_by')) {
                    $table->unsignedBigInteger('validated_by')->nullable();
                }
                if (! Schema::hasColumn('insurance_bordereaux', 'validated_at')) {
                    $table->timestamp('validated_at')->nullable();
                }
                if (! Schema::hasColumn('insurance_bordereaux', 'payment_date')) {
                    $table->date('payment_date')->nullable();
                }
                if (! Schema::hasColumn('insurance_bordereaux', 'payment_reference')) {
                    $table->string('payment_reference')->nullable();
                }
                if (! Schema::hasColumn('insurance_bordereaux', 'remarks')) {
                    $table->text('remarks')->nullable();
                }
            });
        }

        if (Schema::hasTable('insurance_bordereau_claims')) {
            Schema::table('insurance_bordereau_claims', function (Blueprint $table) {
                if (! Schema::hasColumn('insurance_bordereau_claims', 'amount')) {
                    $table->decimal('amount', 12, 2)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('leave_types')) {
            Schema::table('leave_types', function (Blueprint $table) {
                foreach (['name', 'description', 'leave_code', 'maximum_days', 'is_active'] as $column) {
                    if (Schema::hasColumn('leave_types', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('award_types')) {
            Schema::table('award_types', function (Blueprint $table) {
                foreach (['name', 'description', 'is_active'] as $column) {
                    if (Schema::hasColumn('award_types', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('termination_types')) {
            Schema::table('termination_types', function (Blueprint $table) {
                foreach (['name', 'description', 'is_active'] as $column) {
                    if (Schema::hasColumn('termination_types', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('payment_types')) {
            Schema::table('payment_types', function (Blueprint $table) {
                foreach (['name', 'description', 'is_active'] as $column) {
                    if (Schema::hasColumn('payment_types', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('expense_types')) {
            Schema::table('expense_types', function (Blueprint $table) {
                foreach (['name', 'description', 'is_active'] as $column) {
                    if (Schema::hasColumn('expense_types', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('training_types')) {
            Schema::table('training_types', function (Blueprint $table) {
                foreach (['name', 'description', 'is_active'] as $column) {
                    if (Schema::hasColumn('training_types', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('performance_types')) {
            Schema::table('performance_types', function (Blueprint $table) {
                foreach (['name', 'description', 'is_active'] as $column) {
                    if (Schema::hasColumn('performance_types', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('goal_types')) {
            Schema::table('goal_types', function (Blueprint $table) {
                foreach (['name', 'description', 'is_active'] as $column) {
                    if (Schema::hasColumn('goal_types', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('contract_types')) {
            Schema::table('contract_types', function (Blueprint $table) {
                foreach (['name', 'description', 'is_active'] as $column) {
                    if (Schema::hasColumn('contract_types', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('income_types')) {
            Schema::table('income_types', function (Blueprint $table) {
                foreach (['name', 'description', 'is_active'] as $column) {
                    if (Schema::hasColumn('income_types', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('awards')) {
            Schema::table('awards', function (Blueprint $table) {
                foreach (['award_type_id', 'gift', 'cash_price'] as $column) {
                    if (Schema::hasColumn('awards', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('transfers')) {
            Schema::table('transfers', function (Blueprint $table) {
                foreach ([
                    'employee_id',
                    'from_branch_id',
                    'to_branch_id',
                    'from_department_id',
                    'to_department_id',
                    'transfer_date',
                    'reason',
                    'remarks',
                ] as $column) {
                    if (Schema::hasColumn('transfers', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('allowances')) {
            Schema::table('allowances', function (Blueprint $table) {
                foreach (['employee_id', 'allowance_option_id', 'amount', 'start_date', 'end_date', 'status'] as $column) {
                    if (Schema::hasColumn('allowances', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('loans')) {
            Schema::table('loans', function (Blueprint $table) {
                foreach ([
                    'employee_id',
                    'loan_option_id',
                    'principal_amount',
                    'rate_of_interest',
                    'approved_amount',
                    'loan_start_date',
                    'loan_end_date',
                    'emi_amount',
                    'remaining_balance',
                    'status',
                    'notes',
                ] as $column) {
                    if (Schema::hasColumn('loans', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('appraisals')) {
            Schema::table('appraisals', function (Blueprint $table) {
                foreach ([
                    'employee_id',
                    'appraisal_year',
                    'performance_type_id',
                    'rating',
                    'comments',
                    'reviewed_by',
                    'review_date',
                    'status',
                ] as $column) {
                    if (Schema::hasColumn('appraisals', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('jobs')) {
            Schema::table('jobs', function (Blueprint $table) {
                foreach ([
                    'job_title',
                    'job_category_id',
                    'description',
                    'required_experience',
                    'required_qualifications',
                    'salary_from',
                    'salary_to',
                    'positions_available',
                    'job_location',
                    'posted_date',
                    'application_deadline',
                    'status',
                ] as $column) {
                    if (Schema::hasColumn('jobs', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('job_applications')) {
            Schema::table('job_applications', function (Blueprint $table) {
                foreach ([
                    'job_id',
                    'job_stage_id',
                    'applicant_name',
                    'applicant_email',
                    'applicant_phone',
                    'resume_path',
                    'application_date',
                    'status',
                    'score',
                ] as $column) {
                    if (Schema::hasColumn('job_applications', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('insurance_policies')) {
            Schema::table('insurance_policies', function (Blueprint $table) {
                foreach ([
                    'policy_number',
                    'policy_name',
                    'policy_type',
                    'start_date',
                    'end_date',
                    'premium_amount',
                    'coverage_amount',
                    'eligibility_criteria',
                    'waiting_period_days',
                    'claim_settlement_days',
                    'is_family_coverage',
                    'remarks',
                    'created_by',
                ] as $column) {
                    if (Schema::hasColumn('insurance_policies', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('insurance_enrollments')) {
            Schema::table('insurance_enrollments', function (Blueprint $table) {
                foreach ([
                    'enrollment_date',
                    'effective_date',
                    'employee_contribution',
                    'employer_contribution',
                    'premium_amount',
                    'enrollment_number',
                    'created_by',
                ] as $column) {
                    if (Schema::hasColumn('insurance_enrollments', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('insurance_claims')) {
            Schema::table('insurance_claims', function (Blueprint $table) {
                foreach ([
                    'employee_id',
                    'dependent_id',
                    'provider_id',
                    'claim_date',
                    'service_date',
                    'description',
                    'claimed_amount',
                    'approved_amount',
                    'patient_type',
                    'service_type',
                    'treatment_details',
                    'reviewed_at',
                    'reviewed_by',
                    'review_comments',
                    'approved_at',
                    'approved_by',
                    'approval_comments',
                    'payment_date',
                    'payment_reference',
                    'rejection_reason',
                    'created_by',
                ] as $column) {
                    if (Schema::hasColumn('insurance_claims', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('insurance_claim_items')) {
            Schema::table('insurance_claim_items', function (Blueprint $table) {
                foreach (['item_type', 'quantity', 'unit_price', 'status', 'remarks'] as $column) {
                    if (Schema::hasColumn('insurance_claim_items', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('insurance_claim_documents')) {
            Schema::table('insurance_claim_documents', function (Blueprint $table) {
                foreach (['document_type', 'document_name', 'file_size', 'uploaded_by', 'uploaded_at', 'remarks'] as $column) {
                    if (Schema::hasColumn('insurance_claim_documents', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('insurance_claim_history')) {
            Schema::table('insurance_claim_history', function (Blueprint $table) {
                foreach (['changed_by', 'changed_at', 'remarks'] as $column) {
                    if (Schema::hasColumn('insurance_claim_history', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('insurance_bordereaux')) {
            Schema::table('insurance_bordereaux', function (Blueprint $table) {
                foreach ([
                    'policy_id',
                    'bordereau_number',
                    'bordereau_date',
                    'period_from',
                    'period_to',
                    'total_claims_count',
                    'total_claimed_amount',
                    'total_approved_amount',
                    'prepared_by',
                    'prepared_at',
                    'validated_by',
                    'validated_at',
                    'payment_date',
                    'payment_reference',
                    'remarks',
                ] as $column) {
                    if (Schema::hasColumn('insurance_bordereaux', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('insurance_bordereau_claims')) {
            Schema::table('insurance_bordereau_claims', function (Blueprint $table) {
                if (Schema::hasColumn('insurance_bordereau_claims', 'amount')) {
                    $table->dropColumn('amount');
                }
            });
        }
    }
};
