<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DebtorsReportService
{
    /**
     * Get debtors data for the DataTables listing.
     */
    public function getDebtorsData(): array
    {
        return $this->buildDebtorsArray(false);
    }

    /**
     * Get debtors data for export (includes insurance company).
     */
    public function getDebtorsExportData(): array
    {
        return $this->buildDebtorsArray(true);
    }

    /**
     * Build the debtors array with outstanding balances.
     *
     * @param bool $includeInsurance Whether to include insurance company info (for export).
     */
    private function buildDebtorsArray(bool $includeInsurance): array
    {
        $output_array = [];

        $data = DB::table('invoice_items')
            ->whereNull('invoice_items.deleted_at')
            ->select('invoice_items.invoice_id', DB::raw('sum(invoice_items.amount*invoice_items.qty) as invoice_amount'))
            ->groupBy('invoice_items.invoice_id')
            ->get();

        foreach ($data as $item) {
            $payment_info = DB::table('invoice_payments')
                ->whereNull('deleted_at')
                ->where('invoice_id', $item->invoice_id)
                ->select(DB::raw('sum(amount) as amount_paid'))
                ->first();

            $invoiceQuery = DB::table('invoices')
                ->leftJoin('appointments', 'appointments.id', 'invoices.appointment_id')
                ->leftJoin('patients', 'patients.id', 'appointments.patient_id');

            if ($includeInsurance) {
                $invoiceQuery->leftJoin('insurance_companies', 'insurance_companies.id', 'patients.insurance_company_id');
            }

            $invoiceQuery->where('invoices.id', $item->invoice_id)
                ->whereNull('invoices.deleted_at');

            $selectColumns = ['patients.*', 'invoices.invoice_no', DB::raw('DATE_FORMAT(invoices.created_at, "%d-%b-%Y") as invoice_date')];
            if ($includeInsurance) {
                $selectColumns[] = 'insurance_companies.name as insurance_company';
            }

            $invoice_info = $invoiceQuery->select($selectColumns)->first();

            $outstanding_balance = $item->invoice_amount - $payment_info->amount_paid;
            if ($outstanding_balance > 0) {
                $row = [
                    'invoice_date' => $invoice_info->invoice_date,
                    'invoice_no' => $invoice_info->invoice_no,
                    'surname' => $invoice_info->surname,
                    'othername' => $invoice_info->othername,
                    'phone_no' => $invoice_info->phone_no,
                    'invoice_amount' => $item->invoice_amount,
                    'amount_paid' => $payment_info->amount_paid == null ? 0 : $payment_info->amount_paid,
                    'outstanding_balance' => $outstanding_balance,
                ];

                if ($includeInsurance) {
                    $row['insurance_company'] = $invoice_info->insurance_company;
                }

                $output_array[] = $row;
            }
        }

        return $output_array;
    }
}
