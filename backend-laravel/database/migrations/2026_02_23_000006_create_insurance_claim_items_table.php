<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_claim_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('claim_id');
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->foreign('claim_id')->references('id')->on('insurance_claims')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_claim_items');
    }
};
