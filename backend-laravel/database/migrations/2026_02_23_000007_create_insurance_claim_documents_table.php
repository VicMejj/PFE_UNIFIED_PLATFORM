<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_claim_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('claim_id');
            $table->string('file_path')->nullable();
            $table->string('file_type')->nullable();
            $table->foreign('claim_id')->references('id')->on('insurance_claims')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_claim_documents');
    }
};
