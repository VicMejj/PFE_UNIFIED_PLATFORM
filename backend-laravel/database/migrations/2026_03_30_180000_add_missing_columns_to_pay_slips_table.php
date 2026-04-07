<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pay_slips', function (Blueprint $table) {
            if (! Schema::hasColumn('pay_slips', 'employee_id')) {
                $table->unsignedBigInteger('employee_id')->nullable()->after('id');
            }

            if (! Schema::hasColumn('pay_slips', 'payslip_type_id')) {
                $table->unsignedBigInteger('payslip_type_id')->nullable()->after('employee_id');
            }

            if (! Schema::hasColumn('pay_slips', 'payroll_month')) {
                $table->unsignedTinyInteger('payroll_month')->nullable()->after('payslip_type_id');
            }

            if (! Schema::hasColumn('pay_slips', 'payroll_year')) {
                $table->unsignedSmallInteger('payroll_year')->nullable()->after('payroll_month');
            }

            if (! Schema::hasColumn('pay_slips', 'basic_salary')) {
                $table->decimal('basic_salary', 12, 2)->nullable()->after('payroll_year');
            }

            if (! Schema::hasColumn('pay_slips', 'allowance')) {
                $table->json('allowance')->nullable()->after('basic_salary');
            }

            if (! Schema::hasColumn('pay_slips', 'commission')) {
                $table->json('commission')->nullable()->after('allowance');
            }

            if (! Schema::hasColumn('pay_slips', 'loan')) {
                $table->json('loan')->nullable()->after('commission');
            }

            if (! Schema::hasColumn('pay_slips', 'saturation_deduction')) {
                $table->json('saturation_deduction')->nullable()->after('loan');
            }

            if (! Schema::hasColumn('pay_slips', 'other_payment')) {
                $table->json('other_payment')->nullable()->after('saturation_deduction');
            }

            if (! Schema::hasColumn('pay_slips', 'overtime')) {
                $table->json('overtime')->nullable()->after('other_payment');
            }

            if (! Schema::hasColumn('pay_slips', 'gross_salary')) {
                $table->decimal('gross_salary', 12, 2)->nullable()->after('overtime');
            }

            if (! Schema::hasColumn('pay_slips', 'deductions')) {
                $table->decimal('deductions', 12, 2)->nullable()->default(0)->after('gross_salary');
            }

            if (! Schema::hasColumn('pay_slips', 'net_payable')) {
                $table->decimal('net_payable', 12, 2)->nullable()->after('deductions');
            }

            if (! Schema::hasColumn('pay_slips', 'payment_date')) {
                $table->date('payment_date')->nullable()->after('net_payable');
            }

            if (! Schema::hasColumn('pay_slips', 'status')) {
                $table->string('status', 50)->nullable()->after('payment_date');
            }

            if (! Schema::hasColumn('pay_slips', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pay_slips', function (Blueprint $table) {
            $columns = [
                'employee_id',
                'payslip_type_id',
                'payroll_month',
                'payroll_year',
                'basic_salary',
                'allowance',
                'commission',
                'loan',
                'saturation_deduction',
                'other_payment',
                'overtime',
                'gross_salary',
                'deductions',
                'net_payable',
                'payment_date',
                'status',
                'notes',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('pay_slips', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
