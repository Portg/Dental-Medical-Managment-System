<?php

namespace App\Exports;

use App\InvoiceItem;
use App\InvoicePayment;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DoctorPerformanceExport implements FromArray, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
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
        $grand_total_paid = 0;
        $grand_outstanding = 0;

        foreach ($this->data as $row) {
            $invoiceAmount = InvoiceItem::where('invoice_id', $row->invoice_id)->sum(DB::raw('qty*price'));
            $paidAmount = InvoicePayment::where('invoice_id', $row->invoice_id)->sum('amount');
            $balance = $invoiceAmount - $paidAmount;

            $procedures = DB::table('invoice_items')
                ->leftJoin('medical_services', 'medical_services.id', 'invoice_items.medical_service_id')
                ->whereNull('invoice_items.deleted_at')
                ->where(['invoice_items.invoice_id' => $row->invoice_id, 'invoice_items.doctor_id' => $row->doctor_id])
                ->select('medical_services.name')
                ->get();
            $procedureStr = '';
            foreach ($procedures as $p) {
                $procedureStr .= $p->name;
            }

            $rows[] = [
                $row->invoice_no,
                date('d-M-Y', strtotime($row->created_at)),
                \App\Http\Helper\NameHelper::join($row->surname, $row->othername),
                $invoiceAmount,
                $procedureStr,
                $paidAmount,
                $balance,
            ];

            $grand_total += $invoiceAmount;
            $grand_total_paid += $paidAmount;
            $grand_outstanding += $balance;
        }

        $rows[] = ['', '', '', 'Total= ' . number_format($grand_total), '', 'Total Paid = ' . number_format($grand_total_paid), 'Total Outstanding = ' . number_format($grand_outstanding)];
        $this->totalRowIndex = count($rows) + 1;

        return $rows;
    }

    public function headings(): array
    {
        return ['Invoice No', 'Invoice Date', 'Patient Name', 'Total Amount', 'Invoice Procedures', 'Paid Amount', 'Outstanding Balance'];
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
