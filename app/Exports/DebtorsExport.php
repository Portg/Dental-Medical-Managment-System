<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DebtorsExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    private $data;
    private $totalRowIndex;

    public function __construct($outputArray)
    {
        $this->data = $outputArray;
    }

    public function array(): array
    {
        $rows = [];
        $grand_total = 0;
        $grand_total_paid = 0;
        $grand_outstanding = 0;

        foreach ($this->data as $row) {
            $rows[] = [
                $row['invoice_no'],
                $row['invoice_date'],
                \App\Http\Helper\NameHelper::join($row['surname'], $row['othername']),
                number_format($row['invoice_amount']),
                number_format($row['amount_paid']),
                number_format($row['outstanding_balance']),
            ];
            $grand_total += $row['invoice_amount'];
            $grand_total_paid += $row['amount_paid'];
            $grand_outstanding += $row['outstanding_balance'];
        }

        // Total row
        $rows[] = ['', '', '', 'Total= ' . number_format($grand_total), 'Total Paid = ' . number_format($grand_total_paid), 'Total Outstanding = ' . number_format($grand_outstanding)];
        $this->totalRowIndex = count($rows) + 1;

        return $rows;
    }

    public function headings(): array
    {
        return ['Invoice No', 'Invoice Date', 'Patient Name', 'Total Amount', 'Paid Amount', 'Outstanding Balance'];
    }

    public function styles(Worksheet $sheet)
    {
        if ($this->totalRowIndex) {
            $sheet->getStyle('A' . $this->totalRowIndex . ':F' . $this->totalRowIndex)->getFont()->setBold(true);
        }
        return [];
    }
}
