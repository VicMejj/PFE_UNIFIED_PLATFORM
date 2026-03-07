<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table){
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->date('dob')->nullable();
            $table->string('gender');
            $table->string('phone')->nullable();
            $table->string('address');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('employee_id')->unique();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('designation_id');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('designation_id')->references('id')->on('designations')->onDelete('cascade');
            $table->string('company_doj')->nullable();
            $table->string('documents')->nullable();
            $table->string('account_holder_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_identifier_code')->nullable();
            $table->string('branch_location')->nullable();
            $table->string('tax_payer_id')->nullable();
            $table->integer('salary_type')->nullable();
            $table->integer('account_type')->nullable();
            $table->float('salary', 20, 2)->default(0.00);
            $table->integer('is_active')->default('1');
            $table->unsignedBigInteger('created_by');
            $table->index(['branch_id','department_id','designation_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
