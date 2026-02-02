<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExpenseExport implements FromArray, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
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
            $amount = $row->qty * $row->price;
            $rows[] = [
                $counter,
                date('d-M-Y', strtotime($row->created_at)),
                $row->item_name,
                $row->budget_line,
                $row->qty,
                $row->price,
                $amount,
            ];
            $counter++;
            $grand_total += $amount;
        }

        $rows[] = ['', '', '', '', '', '', 'Total= ' . number_format($grand_total)];
        $this->totalRowIndex = count($rows) + 1;

        return $rows;
    }

    public function headings(): array
    {
        return ['ID', 'Purchase Date', 'Item Name', 'Budget Line', 'Quantity', 'Unit Price', 'Total Amount'];
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }

    public function styles(Worksheet $sheet)
    {
        if ($this->totalRowIndex) {
            $sheet->getStyle('A' . $this->totalRowIndex . ':G' . $this->totalRowIndex)->getFont()->setBold(true);
        }
        return [];
    }
}
