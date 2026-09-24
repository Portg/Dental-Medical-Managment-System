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

    /**
     * 病历牙位标记投影出来的状态，牙位图编辑器必须都认识。
     *
     * 编辑器的 buildPayload 对 STATUS_MAP 里没有的状态是 `if (!meta) return;` ——
     * 直接跳过。于是漏登记一个状态的后果不是报错，而是：投影把残根写进去了，
     * 医生打开牙位图点一次保存，那颗牙就被静默抹回正常。没有任何痕迹。
     */
    public function test_projected_statuses_are_known_to_the_editor(): void
    {
        $path = dirname(__DIR__, 2) . '/public/include_js/dental_chart_editor.js';
        $this->assertFileExists($path);

        $js = file_get_contents($path);

        $this->assertSame(
            1,
            preg_match('/var\s+STATUS_MAP\s*=\s*\{(.*?)\n    \};/s', $js, $m),
            'dental_chart_editor.js 里找不到 STATUS_MAP，映射可能被改名或搬走了'
        );

        preg_match_all('/^\s*([a-z_]+)\s*:\s*\{/m', $m[1], $keys);
        $editorStatuses = $keys[1];

        $this->assertNotEmpty($editorStatuses, 'STATUS_MAP 解析出来是空的');

        foreach (DentalChartService::MARK_TO_STATUS as $mark => $status) {
            $this->assertContains(
                $status,
                $editorStatuses,
                "牙位标记 {$mark} 投影出的 {$status} 不在 dental_chart_editor.js 的 STATUS_MAP 里，"
                . '医生在牙位图上点保存会把它抹掉'
            );
            $this->assertContains(
                $status,
                DentalChartService::TOOTH_STATUSES,
                "牙位标记 {$mark} 投影出的 {$status} 不在 tooth_status 枚举白名单里"
            );
        }
    }

    /**
     * 工具栏上的每个状态按钮，编辑器和数据库都得认识。
     *
     * pontic 就是从这道缝里漏了很久：枚举里从 2026_01_17_800003 起就有这个值，
     * 但编辑器的 STATUS_MAP 和工具栏都没有它 —— 于是牙位图画不出「这里有座桥」，
     * 而且这种缺口不会报错，只会安静地少一个功能。
     */
    public function test_toolbar_statuses_are_known_to_editor_and_enum(): void
    {
        $blade = dirname(__DIR__, 2) . '/resources/views/dental_chart/partials/fdi_editor.blade.php';
        $js    = dirname(__DIR__, 2) . '/public/include_js/dental_chart_editor.js';

        $this->assertFileExists($blade);
        $this->assertFileExists($js);

        preg_match_all('/data-status="([a-z_]+)"/', file_get_contents($blade), $m);
        $toolbar = array_values(array_diff(array_unique($m[1]), ['clear']));

        $this->assertNotEmpty($toolbar, '工具栏解析出来是空的');

        $source = file_get_contents($js);
        $this->assertSame(
            1,
            preg_match('/var\s+STATUS_MAP\s*=\s*\{(.*?)\n    \};/s', $source, $sm),
            'dental_chart_editor.js 里找不到 STATUS_MAP'
        );
        preg_match_all('/^\s*([a-z_]+)\s*:\s*\{/m', $sm[1], $keys);

        foreach ($toolbar as $status) {
            $this->assertContains($status, $keys[1], "工具栏上的 {$status} 不在编辑器 STATUS_MAP 里，点了不会有反应");
            $this->assertContains($status, DentalChartService::TOOTH_STATUSES, "工具栏上的 {$status} 不在 tooth_status 枚举里，存不进去");
        }
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
