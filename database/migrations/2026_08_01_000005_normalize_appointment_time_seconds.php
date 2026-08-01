<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 把预约时间的秒归零。
 *
 * 预约的业务粒度是分钟：各创建入口均以 date('H:i:s', strtotime($input)) 写入，
 * 秒恒为 00。唯独「按患者直接打开牙科图表」的自动建约路径用了
 * now()->format('H:i:s')，把当前秒（如 14:49:08）一并写入 start_time 与 sort_by。
 *
 * 该写入点已修正为 now()->startOfMinute()，此处清理既有数据。
 */
return new class extends Migration
{
    public function up(): void
    {
        // start_time 为 time 列，sort_by 为 datetime 列
        DB::table('appointments')
            ->whereRaw('SECOND(start_time) <> 0')
            ->update(['start_time' => DB::raw("SEC_TO_TIME(TIME_TO_SEC(start_time) - SECOND(start_time))")]);

        DB::table('appointments')
            ->whereRaw('SECOND(sort_by) <> 0')
            ->update(['sort_by' => DB::raw("DATE_SUB(sort_by, INTERVAL SECOND(sort_by) SECOND)")]);
    }

    public function down(): void
    {
        // 秒一旦归零便无从还原，且原值本身就是误写入的噪声，不做逆向处理。
    }
};
