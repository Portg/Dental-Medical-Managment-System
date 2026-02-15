<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_cases', function (Blueprint $table) {
            $table->id();
            $table->string('lab_case_no')->unique()->comment('技工单编号');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->unsignedBigInteger('medical_case_id')->nullable();
            $table->unsignedBigInteger('lab_id')->comment('关联技工厂');
            $table->string('prosthesis_type')->comment('义齿类型：冠/桥/活动义齿/种植体等');
            $table->string('material')->nullable()->comment('材料：氧化锆/金属烤瓷/全瓷等');
            $table->string('color_shade')->nullable()->comment('比色信息');
            $table->json('teeth_positions')->nullable()->comment('牙位 JSON');
            $table->text('special_requirements')->nullable()->comment('特殊工艺要求');
            $table->enum('status', [
                'pending',        // 待送出
                'sent',           // 已送出
                'in_production',  // 制作中
                'returned',       // 已返回
                'try_in',         // 试戴
                'completed',      // 完成
                'rework',         // 返工
            ])->default('pending');
            $table->date('sent_date')->nullable();
            $table->date('expected_return_date')->nullable();
            $table->date('actual_return_date')->nullable();
            $table->decimal('lab_fee', 10, 2)->default(0)->comment('加工费');
            $table->decimal('patient_charge', 10, 2)->default(0)->comment('患者收费');
            $table->unsignedTinyInteger('quality_rating')->nullable()->comment('质量评分 1-5');
            $table->unsignedSmallInteger('rework_count')->default(0);
            $table->text('rework_reason')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('_who_added');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('patient_id')->references('id')->on('patients');
            $table->foreign('doctor_id')->references('id')->on('users');
            $table->foreign('lab_id')->references('id')->on('labs');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_cases');
    }
};
