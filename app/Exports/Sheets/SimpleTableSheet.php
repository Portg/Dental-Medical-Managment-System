<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * 通用「表头 + 二维数组」工作表。
 *
 * 复诊率/患者来源两张报表都是若干个结构简单的分区表，
 * 各建一个 Sheet 类只会产生大量近乎重复的样板，这里统一用一个。
 */
class SimpleTableSheet implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    private string $sheetTitle;
    private array $headings;
    private array $rows;
    private bool $boldLastRow;

    public function __construct(string $title, array $headings, array $rows, bool $boldLastRow = false)
    {
        $this->sheetTitle  = $title;
        $this->headings    = $headings;
        $this->rows        = $rows;
        $this->boldLastRow = $boldLastRow;
    }

    public function title(): string
    {
        // Excel 工作表名不允许 : \ / ? * [ ]，且上限 31 字符
        $clean = preg_replace('/[:\\\\\/\?\*\[\]]/u', '-', $this->sheetTitle);
        return mb_substr($clean, 0, 31);
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getFont()->setBold(true);

        if ($this->boldLastRow && count($this->rows) > 0) {
            $lastRow = count($this->rows) + 1; // +1 表头
            $sheet->getStyle('A' . $lastRow . ':' . $sheet->getHighestColumn() . $lastRow)
                ->getFont()->setBold(true);
        }

        return [];
    }
}
