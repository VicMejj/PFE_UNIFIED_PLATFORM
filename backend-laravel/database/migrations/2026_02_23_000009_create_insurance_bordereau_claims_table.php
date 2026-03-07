<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_bordereau_claims', function (Blueprint $table) {
            $table->unsignedBigInteger('bordereau_id');
            $table->unsignedBigInteger('claim_id');
            $table->foreign('bordereau_id')->references('id')->on('insurance_bordereaux')->onDelete('cascade');
            $table->foreign('claim_id')->references('id')->on('insurance_claims')->onDelete('cascade');
            $table->primary(['bordereau_id', 'claim_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_bordereau_claims');
    }
};
