<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWaitingQueuesTable extends Migration
{
    /**
     * Run the migrations.
     * 候诊队列表 - 管理患者到院后的等待和叫号
     *
     * @return void
     */
    public function up()
    {
        Schema::create('waiting_queues', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('branch_id')->comment('门店ID');
            $table->unsignedBigInteger('appointment_id')->comment('关联预约ID');
            $table->unsignedBigInteger('patient_id')->comment('患者ID');
            $table->unsignedBigInteger('doctor_id')->nullable()->comment('接诊医生ID');
            $table->unsignedBigInteger('chair_id')->nullable()->comment('椅位ID');

            $table->integer('queue_number')->comment('排队号码');
            $table->enum('status', [
                'waiting',      // 等待中
                'called',       // 已叫号
                'in_treatment', // 就诊中
                'completed',    // 已完成
                'cancelled',    // 已取消
                'no_show'       // 爽约
            ])->default('waiting')->comment('状态');

            $table->timestamp('check_in_time')->comment('签到时间');
            $table->timestamp('called_time')->nullable()->comment('叫号时间');
            $table->timestamp('treatment_start_time')->nullable()->comment('就诊开始时间');
            $table->timestamp('treatment_end_time')->nullable()->comment('就诊结束时间');

            $table->integer('estimated_wait_minutes')->default(0)->comment('预计等待分钟');
            $table->string('visit_type', 50)->nullable()->comment('就诊类型');
            $table->text('notes')->nullable()->comment('备注');

            $table->unsignedBigInteger('called_by')->nullable()->comment('叫号人员ID');
            $table->unsignedBigInteger('created_by')->nullable()->comment('创建人ID');

            $table->timestamps();
            $table->softDeletes();

            // 索引
            $table->index(['branch_id', 'status']);
            $table->index(['branch_id', 'check_in_time']);
            $table->index('appointment_id');
            $table->index('patient_id');
            $table->index('doctor_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('waiting_queues');
    }
}
