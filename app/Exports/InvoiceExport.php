<?php

namespace App\Exports;

use App\InvoiceItem;
use App\InvoicePayment;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InvoiceExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    private $data;
    private $totalRowIndex;

    public function __construct($queryData)
    {
        $this->data = $queryData;
    }

    public function array(): array
    {
        $rows = [];
        $grand_total = 0;
        $grand_total_paid = 0;
        $grand_outstanding = 0;

        foreach ($this->data as $row) {
            $invoiceAmount = InvoiceItem::where('invoice_id', $row->id)->sum(DB::raw('price*qty'));
            $paidAmount = InvoicePayment::where('invoice_id', $row->id)->sum('amount');
            $balance = $invoiceAmount - $paidAmount;

            $procedures = DB::table('invoice_items')
                ->leftJoin('medical_services', 'medical_services.id', 'invoice_items.medical_service_id')
                ->whereNull('invoice_items.deleted_at')
                ->where('invoice_items.invoice_id', $row->id)
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

        // Total row
        $rows[] = ['', '', '', __('common.total') . '= ' . number_format($grand_total), '', __('financial.amount_paid') . ' = ' . number_format($grand_total_paid), __('invoices.outstanding_balance') . ' = ' . number_format($grand_outstanding)];
        $this->totalRowIndex = count($rows) + 1; // +1 for heading row

        return $rows;
    }

    public function headings(): array
    {
        return [
            __('financial.invoice_number'),
            __('financial.invoice_date'),
            __('financial.patient_name'),
            __('financial.total_amount'),
            __('financial.procedure'),
            __('financial.amount_paid'),
            __('invoices.outstanding_balance'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        if ($this->totalRowIndex) {
            $sheet->getStyle('A' . $this->totalRowIndex . ':G' . $this->totalRowIndex)->getFont()->setBold(true);
        }
        return [];
    }
}
