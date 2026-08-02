<?php

namespace App\Exports;

use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

/**
 * 防 CSV/Excel 公式注入的值绑定器。
 *
 * 导出内容里混着大量用户可控文本（患者姓名、来源名称、备注、供应商名等）。
 * 默认绑定器会把以 = + - @ 开头的字符串识别成公式（实测 '=HYPERLINK(...)'
 * 绑定后单元格类型是 f），收件人打开报表即触发，可用于外链回传数据或钓鱼。
 *
 * 这里把这类字符串强制按文本写入：内容原样保留，只是不再被求值。
 *
 * 数字不受影响 —— '-500'、'3.14' 这类 is_numeric 为真的值仍走默认绑定，
 * 财务报表里的负数金额不会退化成文本而无法参与求和。
 */
class SafeValueBinder extends DefaultValueBinder
{
    /** Excel / LibreOffice 会当作公式起始的字符 */
    private const FORMULA_TRIGGERS = ['=', '+', '-', '@', "\t", "\r"];

    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value) && $value !== ''
            && in_array($value[0], self::FORMULA_TRIGGERS, true)
            && !is_numeric($value)
        ) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
