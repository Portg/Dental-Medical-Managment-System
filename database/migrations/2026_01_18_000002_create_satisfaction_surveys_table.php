<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSatisfactionSurveysTable extends Migration
{
    /**
     * Run the migrations.
     * 患者满意度调查表
     *
     * @return void
     */
    public function up()
    {
        Schema::create('satisfaction_surveys', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('patient_id')->nullable()->comment('患者ID');
            $table->unsignedBigInteger('appointment_id')->nullable()->comment('关联预约ID');
            $table->unsignedBigInteger('doctor_id')->nullable()->comment('接诊医生ID');
            $table->unsignedBigInteger('branch_id')->nullable()->comment('门店ID');

            // 评分 (1-5分)
            $table->tinyInteger('overall_rating')->nullable()->comment('总体评分');
            $table->tinyInteger('service_rating')->nullable()->comment('服务态度评分');
            $table->tinyInteger('environment_rating')->nullable()->comment('环境设施评分');
            $table->tinyInteger('wait_time_rating')->nullable()->comment('等待时间评分');
            $table->tinyInteger('doctor_rating')->nullable()->comment('医生评分');

            // NPS (0-10分)
            $table->tinyInteger('would_recommend')->nullable()->comment('推荐意愿 0-10');

            // 反馈
            $table->text('feedback')->nullable()->comment('评价反馈');
            $table->text('suggestions')->nullable()->comment('改进建议');

            // 调查信息
            $table->enum('survey_channel', ['sms', 'wechat', 'app', 'instore'])->default('sms')->comment('调查渠道');
            $table->date('survey_date')->nullable()->comment('填写日期');
            $table->boolean('is_anonymous')->default(false)->comment('是否匿名');
            $table->enum('status', ['pending', 'completed', 'expired'])->default('pending')->comment('状态');

            $table->timestamps();
            $table->softDeletes();

            // 索引
            $table->index('patient_id');
            $table->index('doctor_id');
            $table->index('branch_id');
            $table->index(['status', 'survey_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('satisfaction_surveys');
    }
}
