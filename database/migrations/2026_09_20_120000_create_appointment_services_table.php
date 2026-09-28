<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 一次预约可以约多个项目。
 *
 * 参考视频的新建预约窗口右侧是一串可勾选的项目（补牙 / 拆线 / 戴牙 / 拔牙 /
 * 根管预备 / 换药…），而我们原先只能选一个 —— 前台要么只记一个、要么开两条
 * 预约，两种都不对。
 *
 * **appointments.service_id 保留不动**，存这次预约的「主项目」（勾选里的第一个）。
 * 这不是遗留包袱，是有意的反规范化：service_id 被 8 处读（工作台 6 个列表、
 * 日历、API 资源、挂号），全改成关联查询等于一次把工作台和预约两个模块的读路径
 * 都翻一遍，风险远大于收益。保留主项目后，那些地方不改也仍然是对的 —— 只是
 * 显示一个而不是全部；要显示全部的地方统一走 Appointment::serviceNamesSubquery()。
 *
 * 权威性约定：**本表是全集，service_id 是它的第一项**。两者的同步只在
 * AppointmentService::syncAppointmentServices() 一处发生，别处不要各写一遍。
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('appointment_services')) {
            return;
        }

        Schema::create('appointment_services', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('appointment_id');
            $table->unsignedBigInteger('medical_service_id');
            // 勾选顺序即显示顺序；sort_order = 0 的那条就是 service_id 里的主项目
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['appointment_id', 'medical_service_id'], 'apt_svc_unique');
            $table->index('appointment_id');

            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('cascade');
            $table->foreign('medical_service_id')->references('id')->on('medical_services');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_services');
    }
};
