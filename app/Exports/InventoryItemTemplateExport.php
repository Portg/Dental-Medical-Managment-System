<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * 物品批量导入模板（仅表头 + 示例行）。
 */
class InventoryItemTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    /**
     * 提供一行示例数据，帮助用户理解格式。
     */
    public function array(): array
    {
        return [
            [
                'ITEM-001',   // 物品编码
                '一次性手套',  // 物品名称
                'CONS',       // 分类代码（需与系统已有分类代码对应）
                '盒',         // 单位
                'L码',        // 规格型号
                '某品牌',      // 品牌/厂家
                '25.00',      // 参考进价
                '35.00',      // 销售价格
                '否',          // 有效期管理（是/否）
                '50',         // 安全库存
                'A区货架1',    // 存放位置
            ],
        ];
    }

    public function headings(): array
    {
        return [
            '物品编码*',
            '物品名称*',
            '分类代码*',
            '单位*',
            '规格型号',
            '品牌/厂家',
            '参考进价',
            '销售价格',
            '有效期管理(是/否)',
            '安全库存',
            '存放位置',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // 表头行加粗 + 蓝色背景
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE6F3FF'],
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 20,
            'C' => 15,
            'D' => 8,
            'E' => 15,
            'F' => 15,
            'G' => 12,
            'H' => 12,
            'I' => 18,
            'J' => 10,
            'K' => 20,
        ];
    }
}
