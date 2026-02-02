<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class InvoicingReportExport implements WithMultipleSheets
{
    private $data;
    private $sheetTitle;
    private $paymentMethods;

    public function __construct($queryData, $sheetTitle)
    {
        $this->data = $queryData;
        $this->sheetTitle = $sheetTitle;

        // Collect unique payment methods
        $this->paymentMethods = [];
        foreach ($queryData as $row) {
            if ($row->payment_method && !in_array($row->payment_method, $this->paymentMethods)) {
                $this->paymentMethods[] = $row->payment_method;
            }
        }
    }

    public function sheets(): array
    {
        $sheets = [];

        // Summary sheet
        $sheets[] = new InvoicingReportSummarySheet($this->data, $this->sheetTitle);

        // Per-payment-method sheets
        foreach ($this->paymentMethods as $method) {
            $sheets[] = new InvoicingPaymentMethodSheet($this->data, $method);
        }

        return $sheets;
    }
}
