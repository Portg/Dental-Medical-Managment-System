<?php

namespace Tests\Unit;

use App\Exports\SafeValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

/**
 * 导出内容含大量用户可控文本（患者姓名、来源名称、备注、供应商名）。
 * 默认绑定器会把 '=...' '@...' 这类字符串写成公式，收件人打开报表即触发。
 */
class SafeValueBinderTest extends TestCase
{
    private function bind($value): \PhpOffice\PhpSpreadsheet\Cell\Cell
    {
        $cell = (new Spreadsheet())->getActiveSheet()->getCell('A1');
        (new SafeValueBinder())->bindValue($cell, $value);

        return $cell;
    }

    /**
     * @dataProvider formulaTriggers
     */
    public function test_formula_like_text_is_written_as_string($value): void
    {
        $cell = $this->bind($value);

        $this->assertSame(DataType::TYPE_STRING, $cell->getDataType(), "[$value] 不应被当成公式");
        $this->assertSame($value, $cell->getValue(), '内容必须原样保留，只是不再求值');
    }

    public static function formulaTriggers(): array
    {
        return [
            'HYPERLINK 外链回传' => ['=HYPERLINK("http://evil","click")'],
            '@ 起始'             => ['@SUM(1+1)'],
            '减号起始的命令注入' => ["-2+3+cmd|' /C calc'!A0"],
            '加号起始'           => ['+1+1'],
            '制表符起始'         => ["\t=1+1"],
        ];
    }

    /**
     * 数字不能被误伤——财务报表里的负数金额退化成文本就无法求和了。
     *
     * @dataProvider numericValues
     */
    public function test_numeric_values_stay_numeric($value): void
    {
        $this->assertSame(DataType::TYPE_NUMERIC, $this->bind($value)->getDataType());
    }

    public static function numericValues(): array
    {
        return [
            '负数金额字符串' => ['-500'],
            '小数字符串'     => ['3.14'],
            '整数'           => [42],
            '浮点'           => [-12.5],
        ];
    }

    public function test_plain_text_is_unaffected(): void
    {
        $cell = $this->bind('张三');

        $this->assertSame(DataType::TYPE_STRING, $cell->getDataType());
        $this->assertSame('张三', $cell->getValue());
    }

    public function test_config_wires_the_safe_binder_globally(): void
    {
        // 没有任何导出类实现 WithCustomValueBinder，全局配置即全量生效
        $this->assertSame(SafeValueBinder::class, config('excel.value_binder.default'));
    }
}
