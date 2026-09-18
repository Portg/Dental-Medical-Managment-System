<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 牙位状态补一个「残根」。
 *
 * 病历里的牙位标记 △ 要投影到 dental_charts.tooth_status，而枚举里没有残根。
 * 不能映射成 missing —— 残根是牙冠没了、**牙根还在**，和缺失是两回事：
 * 残根可能要拔、可能能做桩核冠保留，缺失则是要考虑修复。两者在治疗决策上分岔，
 * 合并成一个状态等于把这个分岔抹掉。
 *
 * 用 DB::statement 改 enum：Laravel 的 Schema builder 改不了 MySQL 的 enum
 * （doctrine/dbal 对 enum 的支持一直不完整），这是这个项目里改 enum 的既有做法。
 */
return new class extends Migration
{
    private const WITH_RESIDUAL = "'normal','caries','filled','crown','rct','missing','implant','pontic','extraction_planned','impacted','residual_root'";
    private const WITHOUT       = "'normal','caries','filled','crown','rct','missing','implant','pontic','extraction_planned','impacted'";

    public function up(): void
    {
        DB::statement(
            "ALTER TABLE dental_charts MODIFY tooth_status ENUM(" . self::WITH_RESIDUAL . ") NOT NULL DEFAULT 'normal'"
        );
    }

    public function down(): void
    {
        // 回滚前先把已经落成 residual_root 的记录收回到 normal，
        // 否则 MODIFY 会把它们静默截成空串（MySQL 非严格模式）。
        DB::table('dental_charts')->where('tooth_status', 'residual_root')->update(['tooth_status' => 'normal']);

        DB::statement(
            "ALTER TABLE dental_charts MODIFY tooth_status ENUM(" . self::WITHOUT . ") NOT NULL DEFAULT 'normal'"
        );
    }
};
