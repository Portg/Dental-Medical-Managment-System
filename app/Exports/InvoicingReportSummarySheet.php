<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InvoicingReportSummarySheet implements FromArray, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
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
                $row->invoice_no,
                date('d-M-Y', strtotime($row->payment_date)),
                \App\Http\Helper\NameHelper::join($row->surname, $row->othername),
                $row->amount,
                $row->payment_method,
            ];
            $grand_total += $row->amount;
        }

        $rows[] = ['', '', '', __('common.total') . '= ' . number_format($grand_total), ''];
        $this->totalRowIndex = count($rows) + 1;

        return $rows;
    }

    public function headings(): array
    {
        return [
            __('financial.invoice_number'),
            __('financial.payment_date'),
            __('financial.patient_name'),
            __('financial.amount_paid'),
            __('financial.payment_method'),
        ];
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }

    public function styles(Worksheet $sheet)
    {
        if ($this->totalRowIndex) {
            $sheet->getStyle('A' . $this->totalRowIndex . ':E' . $this->totalRowIndex)->getFont()->setBold(true);
        }
        return [];
    }
}
