<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InvoicingPaymentMethodSheet implements FromArray, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    private $data;
    private $paymentMethod;
    private $totalRowIndex;

    public function __construct($allData, $paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
        $this->data = [];
        foreach ($allData as $row) {
            if ($row->payment_method == $paymentMethod) {
                $this->data[] = $row;
            }
        }
    }

    public function array(): array
    {
        $rows = [];
        $grand_total = 0;

        foreach ($this->data as $row) {
            $rows[] = [
                $row->invoice_no,
                date('d-M-Y', strtotime($row->payment_date)),
                \App\Http\Helper\NameHelper::join($row->surname, $row->othername),
                $row->amount,
                $row->payment_method . ' ' . ($row->insurance ?? ''),
            ];
            $grand_total += $row->amount;
        }

        $rows[] = ['', '', '', 'Total= ' . number_format($grand_total), ''];
        $this->totalRowIndex = count($rows) + 1;

        return $rows;
    }

    public function headings(): array
    {
        return ['Invoice No', 'Payment Date', 'Patient Name', 'Amount Paid', 'Payment Method'];
    }

    public function title(): string
    {
        return $this->paymentMethod;
    }

    public function styles(Worksheet $sheet)
    {
        if ($this->totalRowIndex) {
            $sheet->getStyle('A' . $this->totalRowIndex . ':E' . $this->totalRowIndex)->getFont()->setBold(true);
        }
        return [];
    }
}
