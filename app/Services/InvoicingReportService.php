<?php

namespace App\Services;

use App\InsuranceCompany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InvoicingReportService
{
    /**
     * Get invoice payment report data for DataTables.
     */
    public function getInvoicePayments(array $filters): Collection
    {
        $query = DB::table('invoice_payments')
            ->leftJoin('invoices', 'invoices.id', 'invoice_payments.invoice_id')
            ->leftJoin('appointments', 'appointments.id', 'invoices.appointment_id')
            ->leftJoin('patients', 'patients.id', 'appointments.patient_id')
            ->whereNull('invoice_payments.deleted_at')
            ->select(
                'invoice_payments.*',
                DB::raw('DATE_FORMAT(invoice_payments.payment_date, "%d-%b-%Y") as payment_date'),
                'patients.surname',
                'patients.othername'
            );

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween(
                DB::raw('DATE_FORMAT(invoice_payments.payment_date, \'%Y-%m-%d\')'),
                [$filters['start_date'], $filters['end_date']]
            );
        }

        return $query->get();
    }

    /**
     * Get invoice payment data for export.
     */
    public function getExportData(?string $from, ?string $to): Collection
    {
        return DB::table('invoice_payments')
            ->leftJoin('insurance_companies', 'insurance_companies.id', 'invoice_payments.insurance_company_id')
            ->leftJoin('invoices', 'invoices.id', 'invoice_payments.invoice_id')
            ->leftJoin('appointments', 'appointments.id', 'invoices.appointment_id')
            ->leftJoin('patients', 'patients.id', 'appointments.patient_id')
            ->whereNull('invoice_payments.deleted_at')
            ->whereBetween(
                DB::raw('DATE_FORMAT(invoice_payments.payment_date, \'%Y-%m-%d\')'),
                [$from, $to]
            )
            ->select(
                'invoice_payments.*',
                'invoices.invoice_no',
                DB::raw('DATE_FORMAT(invoice_payments.payment_date, "%d-%b-%Y") as payment_date'),
                'patients.surname',
                'patients.othername',
                'insurance_companies.name as insurance'
            )
            ->get();
    }

    /**
     * Get today's cash payments for DataTables.
     */
    public function getTodaysCash(): Collection
    {
        return DB::table('invoice_payments')
            ->leftJoin('invoices', 'invoices.id', 'invoice_payments.invoice_id')
            ->leftJoin('appointments', 'appointments.id', 'invoices.appointment_id')
            ->leftJoin('patients', 'patients.id', 'appointments.patient_id')
            ->leftJoin('users', 'users.id', 'invoice_payments._who_added')
            ->whereNull('invoice_payments.deleted_at')
            ->where('payment_method', 'Cash')
            ->whereDate('payment_date', date('Y-m-d'))
            ->select(
                'invoice_payments.*',
                'patients.surname',
                'patients.othername',
                DB::raw('TIME(invoice_payments.updated_at) AS created_date'),
                'users.othername as added_by'
            )
            ->get();
    }

    /**
     * Get today's expenses for DataTables.
     */
    public function getTodaysExpenses(): Collection
    {
        return DB::table('expense_items')
            ->leftJoin('users', 'users.id', 'expense_items._who_added')
            ->whereNull('expense_items.deleted_at')
            ->whereDate('expense_items.updated_at', date('Y-m-d'))
            ->select(
                'expense_items.*',
                DB::raw('TIME(expense_items.updated_at) AS created_date'),
                'users.othername as added_by'
            )
            ->get();
    }

    /**
     * Get insurance providers list.
     */
    public function getInsuranceProviders(): Collection
    {
        return InsuranceCompany::orderBy('id', 'DESC')->get();
    }
}
