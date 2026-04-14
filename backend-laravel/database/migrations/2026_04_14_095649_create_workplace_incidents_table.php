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
        Schema::create('workplace_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->date('incident_date');
            $table->string('type'); // accident, injury, complaint, safety_violation
            $table->string('severity')->default('low'); // low, medium, high, critical
            $table->text('description');
            $table->string('status')->default('reported'); // reported, investigating, resolved, closed
            $table->text('resolution')->nullable();
            $table->date('resolved_at')->nullable();
            $table->json('metadata')->nullable(); // insurance claim id, etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workplace_incidents');
    }
};
