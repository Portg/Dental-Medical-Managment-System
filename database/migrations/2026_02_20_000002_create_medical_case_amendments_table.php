<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_case_amendments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('medical_case_id');
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->text('amendment_reason');
            $table->json('amendment_fields')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->dateTime('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->foreign('medical_case_id')->references('id')->on('medical_cases')->onDelete('cascade');
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['medical_case_id', 'status']);
            $table->index(['requested_by', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_case_amendments');
    }
};
