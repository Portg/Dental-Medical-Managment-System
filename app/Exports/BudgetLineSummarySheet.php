<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BudgetLineSummarySheet implements FromArray, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    private $data;
    private $sheetTitle;
    private $totalRowIndex;

    public function __construct($queryData, $sheetTitle)
    {
        $this->data = $queryData;
        $this->sheetTitle = $sheetTitle;
    }

    public function array(): array
    {
        $rows = [];
        $grand_total = 0;
        $counter = 1;

        foreach ($this->data as $row) {
            $rows[] = [
                $counter,
                $row->budget_line,
                $row->total_qty,
                $row->product_price,
            ];
            $counter++;
            $grand_total += $row->product_price;
        }

        $rows[] = ['', '', '', 'Total= ' . number_format($grand_total)];
        $this->totalRowIndex = count($rows) + 1;

        return $rows;
    }

    public function headings(): array
    {
        return ['ID', 'Budget Line', 'Total Items', 'Amount'];
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }

    public function styles(Worksheet $sheet)
    {
        if ($this->totalRowIndex) {
            $sheet->getStyle('A' . $this->totalRowIndex . ':D' . $this->totalRowIndex)->getFont()->setBold(true);
        }
        return [];
    }
}
