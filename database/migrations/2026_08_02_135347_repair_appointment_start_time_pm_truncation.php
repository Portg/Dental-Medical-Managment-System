<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 修复被 2026_08_01_000006 截断掉 AM/PM 的预约时间。
 *
 * appointments.start_time 是 varchar(191)，格式由写入侧决定。AppointmentService
 * 曾经对 walk-in 用 format('h:i A')、其余分支直接透传表单原值，库里因此混有
 * 「08:30 PM」这类 12 小时制字符串。2026_08_01_000006 用 LEFT(start_time, 5)
 * 归一时把 AM/PM 一并截掉——晚上 8 点半就此变成早上 8 点半，且 down() 只补 ':00'，
 * 反而把错误固化。
 *
 * 还原依据：同一行的 sort_by 是 datetime，存的是
 * date('H:i:s', strtotime($appointment_time))，即 24 小时制真值，且排班容量校验
 * （AppointmentService 的 max_patients 悲观锁）与日历排序都以它为准。
 *
 * 只修**能证明受损**的行：sort_by 的小时数恰好等于 start_time 小时数 + 12（模 24）。
 * 这是 PM 截断的精确特征，同时覆盖 12:30 AM → 00:30 的情形；
 * 而 '08:30 AM' 截断成 '08:30' 本就等于 24 小时制真值，两者一致，不会被误改。
 * walk-in 的 start_time（实际到店时间）与 sort_by（预约时段）本就可能不同，
 * 除非恰好相差整 12 小时，否则不受影响。
 */
return new class extends Migration
{
    public function up(): void
    {
        $condition = "sort_by IS NOT NULL
                      AND start_time IS NOT NULL
                      AND CHAR_LENGTH(start_time) = 5
                      AND HOUR(sort_by) = MOD(HOUR(STR_TO_DATE(start_time, '%H:%i')) + 12, 24)
                      AND MINUTE(sort_by) = MINUTE(STR_TO_DATE(start_time, '%H:%i'))";

        $affected = DB::table('appointments')
            ->whereRaw($condition)
            ->update(['start_time' => DB::raw("TIME_FORMAT(sort_by, '%H:%i')")]);

        if ($affected > 0) {
            Log::warning("[repair_appointment_start_time] 按 sort_by 还原了 {$affected} 条被截断 AM/PM 的预约时间。");
        }
    }

    public function down(): void
    {
        // 还原后的值才是真实预约时间，回滚等于重新把晚上的预约改回早上，不做逆向处理。
    }
};
