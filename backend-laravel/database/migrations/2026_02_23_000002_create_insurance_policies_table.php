<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_policies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('provider_id');
            $table->string('name');
            $table->text('coverage_details')->nullable();
            $table->decimal('premium', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreign('provider_id')->references('id')->on('insurance_providers')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_policies');
    }
};
