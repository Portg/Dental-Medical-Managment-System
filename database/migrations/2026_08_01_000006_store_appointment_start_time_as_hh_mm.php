<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 把 appointments.start_time 统一为 HH:MM。
 *
 * 该列是 varchar(191) 而非 MySQL time —— 存什么就返回什么，格式完全由写入侧决定。
 * 预约的业务粒度是分钟，因此应直接存 HH:MM，而不是存 HH:MM:SS 再在展示层截断。
 *
 * 上一个迁移（2026_08_01_000005）只把秒归零，得到 14:49:00，仍带无意义的 :00，
 * 此处进一步截至分。写入侧已在 DentalChartService 中改为 now()->format('H:i')。
 *
 * 注意 shifts.start_time 与 doctor_schedules.start_time 是真正的 time 列，
 * MySQL 恒返回 HH:MM:SS，只能在展示层截断（见 Shift::timeRange()），不适用本迁移。
 */
return new class extends Migration
{
    public function up(): void
    {
        // 先把 12 小时制（如 '08:30 PM'，历史上 walk-in 与部分表单透传会写成这样）
        // 转成 24 小时制，再截断。直接 LEFT(...,5) 会把 PM 连同真实时段一起丢掉，
        // 让晚上 8 点半变成早上 8 点半。
        //
        // 注：本迁移早期版本正是直接 LEFT()。已经跑过旧版本的库由
        // 2026_08_02_135347_repair_appointment_start_time_pm_truncation 按 sort_by 还原。
        DB::table('appointments')
            ->whereNotNull('start_time')
            ->whereRaw("start_time REGEXP '[AaPp][Mm][[:space:]]*$'")
            ->update(['start_time' => DB::raw("TIME_FORMAT(STR_TO_DATE(TRIM(start_time), '%h:%i %p'), '%H:%i')")]);

        DB::table('appointments')
            ->whereNotNull('start_time')
            ->whereRaw('CHAR_LENGTH(start_time) > 5')
            ->update(['start_time' => DB::raw('LEFT(start_time, 5)')]);
    }

    public function down(): void
    {
        // 补回 ':00' 以还原 HH:MM:SS 形态
        DB::table('appointments')
            ->whereNotNull('start_time')
            ->whereRaw('CHAR_LENGTH(start_time) = 5')
            ->update(['start_time' => DB::raw("CONCAT(start_time, ':00')")]);
    }
};
