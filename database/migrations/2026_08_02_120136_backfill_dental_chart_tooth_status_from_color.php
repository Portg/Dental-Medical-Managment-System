<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 用 color 编号回填 dental_charts.tooth_status。
 *
 * 背景：旧版 Angular 牙位图只存 color 编号，没有状态字段。
 * 2026_01_17_800003 加 tooth_status 列时带了 NOT NULL DEFAULT 'normal'，
 * 于是**所有历史行都被填成了 normal**，真实状态仍只存在于 color 里。
 *
 * 后果：患者牙位图摘要（DentalChartService::getChartSummaryForPatient）里
 * 读取端本来写了 `$row->tooth_status ?: COLOR_TO_STATUS[$row->color]` 想做兜底，
 * 但 'normal' 是真值，`?:` 永远短路——兜底从未生效，这批牙位要么被标成
 * 「正常」要么干脆不出现，龋齿/缺失/种植等标记全部丢失。
 *
 * 写入端已在 DentalChartService::replaceChartData() 修正（没给状态时先按 color
 * 折算再落回 normal），这里清理既有数据。
 *
 * 幂等：只动 tooth_status='normal' 且 color 能折算的行；重复执行无副作用。
 */
return new class extends Migration
{
    /**
     * 与 DentalChartService::COLOR_TO_STATUS 保持一致。
     * 迁移不引用应用层常量——常量将来可能改，迁移必须锁定执行当时的语义。
     */
    private const COLOR_TO_STATUS = [
        '1'  => 'filled',
        '2'  => 'caries',
        '3'  => 'rct',
        '4'  => 'missing',
        '6'  => 'implant',
        '8'  => 'crown',
        '11' => 'impacted',
    ];

    public function up(): void
    {
        foreach (self::COLOR_TO_STATUS as $color => $status) {
            DB::table('dental_charts')
                ->where('tooth_status', 'normal')
                ->where('color', (string) $color)
                ->update(['tooth_status' => $status]);
        }
    }

    public function down(): void
    {
        // 回滚会把这批牙位重新抹成 normal，等于再次丢失龋齿/缺失/种植等标记，
        // 而 color 仍在、随时可再跑一次 up()，故不做逆向处理。
    }
};
