<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 为满意度调查增加公开填写所需的字段。
 *
 * 原实现只能由已登录员工在后台提交，患者侧没有任何入口——
 * SatisfactionSurveyController::fill() 是 TODO 空壳，也没有对应路由。
 * 这里补上 token 让患者可以通过一次性链接自助填写：
 *   token      公开填写链接的凭证，全局唯一
 *   sent_at    链接生成/派发时间
 *   expires_at 有效期，过期后拒绝填写并置为 expired
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('satisfaction_surveys', function (Blueprint $table) {
            $table->char('token', 32)->nullable()->unique()->after('id')->comment('公开填写链接凭证');
            $table->timestamp('sent_at')->nullable()->after('survey_date')->comment('链接派发时间');
            $table->timestamp('expires_at')->nullable()->after('sent_at')->comment('链接过期时间');
        });
    }

    public function down(): void
    {
        Schema::table('satisfaction_surveys', function (Blueprint $table) {
            $table->dropUnique(['token']);
            $table->dropColumn(['token', 'sent_at', 'expires_at']);
        });
    }
};
