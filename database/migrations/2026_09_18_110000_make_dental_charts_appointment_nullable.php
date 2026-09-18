<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * dental_charts.appointment_id 放开为可空。
 *
 * 牙位状态现在由病历里的牙位标记投影出来（见 DentalChartService::projectFromMedicalCase），
 * 而**病历不一定有对应的就诊**：从患者页直接建的病历就没有预约。
 * 列是 NOT NULL 的话这类投影一条都写不进去。
 *
 * medical_case_id 本来就是可空的，两列现在都可空 —— 一条牙位状态记录至少要挂在
 * 其中一个上，这一条由写入方保证：投影总是带 medical_case_id，牙位图那条路
 * （replaceChartData）总是带 appointment_id。没有在库上加 CHECK 约束，
 * MySQL 5.7 不支持，而这个项目要跑在客户的老机器上。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dental_charts', function (Blueprint $table) {
            $table->unsignedBigInteger('appointment_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // 回滚前先清掉没有就诊的记录，否则改回 NOT NULL 会失败
        \Illuminate\Support\Facades\DB::table('dental_charts')
            ->whereNull('appointment_id')
            ->delete();

        Schema::table('dental_charts', function (Blueprint $table) {
            $table->unsignedBigInteger('appointment_id')->nullable(false)->change();
        });
    }
};
