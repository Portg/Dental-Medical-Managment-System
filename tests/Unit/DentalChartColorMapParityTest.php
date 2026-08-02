<?php

namespace Tests\Unit;

use App\Services\DentalChartService;
use PHPUnit\Framework\TestCase;

/**
 * 牙位颜色编号 → 状态的映射在三处各存了一份：
 *
 *   1. DentalChartService::COLOR_TO_STATUS —— 写入与摘要读取；
 *   2. public/include_js/dental_chart_editor.js 的 COLOR_TO_STATUS —— 前端回显；
 *   3. 2026_08_02_120136 回填迁移里的同名常量 —— 故意冻结执行当时的语义，不参与本断言。
 *
 * 1 和 2 必须一致：漂移会让同一颗牙在前端显示龋齿、在患者摘要里显示别的状态，
 * 而这种不一致没有任何运行时报错，只能靠这条测试拦。
 */
class DentalChartColorMapParityTest extends TestCase
{
    public function test_js_color_map_matches_php_constant(): void
    {
        // 纯单测，不启动容器，所以不用 public_path()
        $path = dirname(__DIR__, 2) . '/public/include_js/dental_chart_editor.js';

        $this->assertFileExists($path);

        $js = file_get_contents($path);

        $this->assertSame(
            1,
            preg_match('/var\s+COLOR_TO_STATUS\s*=\s*\{(.*?)\}/s', $js, $m),
            'dental_chart_editor.js 里找不到 COLOR_TO_STATUS，映射可能被改名或搬走了'
        );

        preg_match_all("/'(\d+)'\s*:\s*'([a-z_]+)'/", $m[1], $pairs, PREG_SET_ORDER);

        $fromJs = [];
        foreach ($pairs as $pair) {
            $fromJs[$pair[1]] = $pair[2];
        }

        $fromPhp = DentalChartService::COLOR_TO_STATUS;

        ksort($fromJs);
        ksort($fromPhp);

        $this->assertSame(
            $fromPhp,
            $fromJs,
            'dental_chart_editor.js 与 DentalChartService::COLOR_TO_STATUS 已漂移，两边必须同步改'
        );
    }

    public function test_mapped_statuses_are_all_valid_enum_values(): void
    {
        foreach (DentalChartService::COLOR_TO_STATUS as $color => $status) {
            $this->assertContains(
                $status,
                DentalChartService::TOOTH_STATUSES,
                "color={$color} 折算出的 {$status} 不在 dental_charts.tooth_status 的枚举里"
            );
        }
    }
}
