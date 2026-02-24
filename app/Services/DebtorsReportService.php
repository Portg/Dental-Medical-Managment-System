<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DebtorsReportService
{
    /**
     * Get debtors data for the DataTables listing.
     */
    public function getDebtorsData(?string $startDate = null, ?string $endDate = null): array
    {
        return $this->buildDebtorsArray(false, $startDate, $endDate);
    }

    /**
     * Get debtors data for export (includes insurance company).
     */
    public function getDebtorsExportData(?string $startDate = null, ?string $endDate = null): array
    {
        return $this->buildDebtorsArray(true, $startDate, $endDate);
    }

    /**
     * Build the debtors array with outstanding balances using a single query.
     *
     * @param bool $includeInsurance Whether to include insurance company info (for export).
     * @param string|null $startDate Optional start date filter (Y-m-d).
     * @param string|null $endDate   Optional end date filter (Y-m-d).
     */
    private function buildDebtorsArray(bool $includeInsurance, ?string $startDate = null, ?string $endDate = null): array
    {
        $selectColumns = [
            'invoices.id as invoice_id',
            'invoices.invoice_no',
            DB::raw('DATE_FORMAT(invoices.created_at, "%d-%b-%Y") as invoice_date'),
            'patients.surname',
            'patients.othername',
            'patients.phone_no',
            DB::raw('SUM(invoice_items.amount * invoice_items.qty) as invoice_amount'),
            DB::raw('COALESCE(paid.amount_paid, 0) as amount_paid'),
            DB::raw('SUM(invoice_items.amount * invoice_items.qty) - COALESCE(paid.amount_paid, 0) as outstanding_balance'),
        ];

        if ($includeInsurance) {
            $selectColumns[] = 'insurance_companies.name as insurance_company';
        }

        $paymentSub = DB::table('invoice_payments')
            ->whereNull('deleted_at')
            ->select('invoice_id', DB::raw('SUM(amount) as amount_paid'))
            ->groupBy('invoice_id');

        $query = DB::table('invoices')
            ->join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->leftJoin('appointments', 'appointments.id', '=', 'invoices.appointment_id')
            ->leftJoin('patients', 'patients.id', '=', 'appointments.patient_id')
            ->leftJoinSub($paymentSub, 'paid', function ($join) {
                $join->on('invoices.id', '=', 'paid.invoice_id');
            })
            ->whereNull('invoices.deleted_at')
            ->whereNull('invoice_items.deleted_at');

        if ($includeInsurance) {
            $query->leftJoin('insurance_companies', 'insurance_companies.id', '=', 'patients.insurance_company_id');
        }

        if ($startDate) {
            $query->whereDate('invoices.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('invoices.created_at', '<=', $endDate);
        }

        $groupByColumns = [
            'invoices.id', 'invoices.invoice_no', 'invoices.created_at',
            'patients.surname', 'patients.othername', 'patients.phone_no',
            'paid.amount_paid',
        ];
        if ($includeInsurance) {
            $groupByColumns[] = 'insurance_companies.name';
        }

        $rows = $query->select($selectColumns)
            ->groupBy($groupByColumns)
            ->havingRaw('outstanding_balance > 0')
            ->orderByDesc('outstanding_balance')
            ->get();

        $output = [];
        foreach ($rows as $row) {
            $item = [
                'invoice_date'        => $row->invoice_date,
                'invoice_no'          => $row->invoice_no,
                'surname'             => $row->surname,
                'othername'           => $row->othername,
                'phone_no'            => $row->phone_no,
                'invoice_amount'      => $row->invoice_amount,
                'amount_paid'         => $row->amount_paid,
                'outstanding_balance' => $row->outstanding_balance,
            ];

            if ($includeInsurance) {
                $item['insurance_company'] = $row->insurance_company;
            }

            $output[] = $item;
        }

        return $output;
    }
}
