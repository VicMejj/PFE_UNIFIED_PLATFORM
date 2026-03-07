<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_claims', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('enrollment_id');
            $table->string('claim_number')->unique();
            $table->string('status')->default('pending');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->date('date_filed')->nullable();
            $table->foreign('enrollment_id')->references('id')->on('insurance_enrollments')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_claims');
    }
};
