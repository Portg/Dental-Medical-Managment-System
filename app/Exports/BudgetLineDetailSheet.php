<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BudgetLineDetailSheet implements FromArray, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    private $data;
    private $budgetLineName;
    private $totalRowIndex;

    public function __construct($queryData, $budgetLineName)
    {
        $this->data = $queryData;
        $this->budgetLineName = $budgetLineName;
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
                $row->product_name,
                $row->description,
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
        return ['ID', 'Purchase Date', 'Item', 'Description', 'Qty', 'Price', 'Total Amount'];
    }

    public function title(): string
    {
        return substr($this->budgetLineName, 0, 31); // Excel sheet name max 31 chars
    }

    public function styles(Worksheet $sheet)
    {
        if ($this->totalRowIndex) {
            $sheet->getStyle('A' . $this->totalRowIndex . ':G' . $this->totalRowIndex)->getFont()->setBold(true);
        }
        return [];
    }
}
