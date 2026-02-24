<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProceduresExport implements FromArray, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
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

        foreach ($this->data as $row) {
            $rows[] = [
                $row->name,
                $row->procedure_income,
            ];
            $grand_total += $row->procedure_income;
        }

        $rows[] = ['', __('common.total') . '= ' . number_format($grand_total)];
        $this->totalRowIndex = count($rows) + 1;

        return $rows;
    }

    public function headings(): array
    {
        return [
            __('report.procedure_name'),
            __('report.procedure_income'),
        ];
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }

    public function styles(Worksheet $sheet)
    {
        if ($this->totalRowIndex) {
            $sheet->getStyle('A' . $this->totalRowIndex . ':B' . $this->totalRowIndex)->getFont()->setBold(true);
        }
        return [];
    }
}
