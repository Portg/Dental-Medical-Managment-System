<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrdFieldsToAppointmentsTable extends Migration
{
    /**
     * Run the migrations.
     * PRD 2.0: 预约表补充字段
     *
     * @return void
     */
    public function up()
    {
        Schema::table('appointments', function (Blueprint $table) {
            // 预计时长(分钟)
            if (!Schema::hasColumn('appointments', 'duration_minutes')) {
                $table->integer('duration_minutes')->nullable()->after('end_date');
            }

            // 预约类型
            if (!Schema::hasColumn('appointments', 'appointment_type')) {
                $table->enum('appointment_type', ['first_visit', 'revisit', 'follow_up', 'emergency', 'consultation'])->default('first_visit')->after('duration_minutes');
            }

            // 预约来源
            if (!Schema::hasColumn('appointments', 'source')) {
                $table->enum('source', ['front_desk', 'phone', 'mini_program', 'meituan', 'dianping', 'walk_in', 'online'])->default('front_desk')->after('appointment_type');
            }

            // 取消原因
            if (!Schema::hasColumn('appointments', 'cancelled_reason')) {
                $table->string('cancelled_reason', 500)->nullable()->after('status');
            }

            // 取消人
            if (!Schema::hasColumn('appointments', 'cancelled_by')) {
                $table->unsignedBigInteger('cancelled_by')->nullable()->after('cancelled_reason');
                $table->foreign('cancelled_by')->references('id')->on('users')->onDelete('set null');
            }

            // 爽约次数 (累计)
            if (!Schema::hasColumn('appointments', 'no_show_count')) {
                $table->integer('no_show_count')->default(0)->after('cancelled_by');
            }

            // 提醒已发送
            if (!Schema::hasColumn('appointments', 'reminder_sent')) {
                $table->boolean('reminder_sent')->default(false)->after('no_show_count');
            }

            // 提醒发送时间
            if (!Schema::hasColumn('appointments', 'reminder_sent_at')) {
                $table->dateTime('reminder_sent_at')->nullable()->after('reminder_sent');
            }

            // 患者确认
            if (!Schema::hasColumn('appointments', 'confirmed_by_patient')) {
                $table->boolean('confirmed_by_patient')->default(false)->after('reminder_sent_at');
            }

            // 患者确认时间
            if (!Schema::hasColumn('appointments', 'confirmed_at')) {
                $table->dateTime('confirmed_at')->nullable()->after('confirmed_by_patient');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('appointments', function (Blueprint $table) {
            $columns = [
                'duration_minutes', 'appointment_type', 'source',
                'cancelled_reason', 'no_show_count',
                'reminder_sent', 'reminder_sent_at',
                'confirmed_by_patient', 'confirmed_at'
            ];

            if (Schema::hasColumn('appointments', 'cancelled_by')) {
                $table->dropForeign(['cancelled_by']);
                $table->dropColumn('cancelled_by');
            }

            foreach ($columns as $column) {
                if (Schema::hasColumn('appointments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
