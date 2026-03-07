<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_bordereaux', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->string('status')->default('pending');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->date('date_generated')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_bordereaux');
    }
};
