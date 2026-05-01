<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_score_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->string('author_type', 30)->comment('hr, manager, dept_manager');
            $table->string('note_type', 30)->default('adhoc')->comment('monthly, quarterly, annual, adhoc');
            
            $table->json('attendance_note')->nullable()->comment('Notes about attendance with ratings');
            $table->json('discipline_note')->nullable()->comment('Notes about discipline with ratings');
            $table->json('performance_note')->nullable()->comment('Notes about performance with ratings');
            $table->text('general_note')->nullable();
            
            $table->decimal('score_adjustment', 5, 2)->default(0)->comment('Manual adjustment to AI score (-20 to +20)');
            $table->boolean('is_ai_generated')->default(false)->comment('Whether AI suggested this note');
            $table->json('ai_analysis')->nullable()->comment('AI analysis and recommendations');
            
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            
            $table->timestamps();
            
            $table->index(['employee_id', 'author_type']);
            $table->index(['author_id', 'created_at']);
            $table->index(['period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_score_notes');
    }
};