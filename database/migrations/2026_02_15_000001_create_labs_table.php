<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->text('specialties')->nullable()->comment('擅长类型，逗号分隔');
            $table->unsignedSmallInteger('avg_turnaround_days')->nullable()->comment('平均交付天数');
            $table->string('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('_who_added');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labs');
    }
};
