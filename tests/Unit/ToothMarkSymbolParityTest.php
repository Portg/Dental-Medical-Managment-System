<?php

namespace Tests\Unit;

use App\MedicalCaseItem;
use PHPUnit\Framework\TestCase;

/**
 * 牙位标记的符号表在两处各存了一份：
 *
 *   1. MedicalCaseItem::MARK_SYMBOLS —— 派生文本（打印、详情、API、OCR、工作日志都读它）；
 *   2. public/include_js/medical_case_items.js 的 MARK_SYMBOLS —— 编辑器里的十字图与派生预览。
 *
 * 两边必须一致：漂移会让同一行病历在编辑器里显示 △、打印出来却是别的符号或干脆
 * 没有，而这种不一致没有任何运行时报错 —— 与 DentalChartColorMapParityTest 守的
 * 是同一类坑。
 */
class ToothMarkSymbolParityTest extends TestCase
{
    public function test_js_symbol_table_matches_php_constant(): void
    {
        $path = dirname(__DIR__, 2) . '/public/include_js/medical_case_items.js';

        $this->assertFileExists($path);

        $js = file_get_contents($path);

        $this->assertSame(
            1,
            preg_match('/var\s+MARK_SYMBOLS\s*=\s*\{(.*?)\};/s', $js, $m),
            'medical_case_items.js 里找不到 MARK_SYMBOLS，映射可能被改名或搬走了'
        );

        // residual_root: '△',   // 注释在后面，只取引号里的符号
        preg_match_all("/([a-z_]+)\s*:\s*'([^']+)'/u", $m[1], $pairs, PREG_SET_ORDER);

        $fromJs = [];
        foreach ($pairs as $pair) {
            $fromJs[$pair[1]] = $pair[2];
        }

        $fromPhp = MedicalCaseItem::MARK_SYMBOLS;

        ksort($fromJs);
        ksort($fromPhp);

        $this->assertSame(
            $fromPhp,
            $fromJs,
            'medical_case_items.js 与 MedicalCaseItem::MARK_SYMBOLS 已漂移，两边必须同步改'
        );
    }

    /**
     * 符号表的键必须都是认得的标记 slug —— 多一个键意味着前端能画出一个
     * 服务端会当成「认不出的标记」丢掉的符号。
     */
    public function test_symbol_keys_are_all_known_marks(): void
    {
        foreach (MedicalCaseItem::MARK_SYMBOLS as $mark => $symbol) {
            $this->assertContains(
                $mark,
                MedicalCaseItem::MARKS,
                "{$mark} 有符号但不在 MARKS 白名单里，normalizeCaseItems 会把它当没填"
            );
        }
    }
}
