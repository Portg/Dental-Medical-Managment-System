<?php

namespace App\Services;

use App\InvoicePayment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InsuranceReportService
{
    /**
     * Get insurance payment report data for DataTables.
     */
    public function getInsurancePayments(array $filters): Collection
    {
        $query = DB::table('invoice_payments')
            ->leftJoin('invoices', 'invoices.id', 'invoice_payments.invoice_id')
            ->leftJoin('appointments', 'appointments.id', 'invoices.appointment_id')
            ->leftJoin('patients', 'patients.id', 'appointments.patient_id')
            ->leftJoin('insurance_companies', 'insurance_companies.id', 'invoice_payments.insurance_company_id')
            ->whereNull('invoice_payments.deleted_at')
            ->where('invoice_payments.payment_method', 'Insurance')
            ->select(
                'invoice_payments.*',
                'invoices.invoice_no',
                'patients.surname',
                'patients.othername',
                'insurance_companies.name as insurance_company'
            );

        if (!empty($filters['start_date']) && !empty($filters['end_date']) && !empty($filters['company'])) {
            $from_date = date('Y-m-d', strtotime($filters['start_date']));
            $to_date = date('Y-m-d', strtotime($filters['end_date']));
            $query->where('invoice_payments.insurance_company_id', $filters['company'])
                ->whereBetween(DB::raw('DATE(invoice_payments.created_at)'), [$from_date, $to_date]);
        }

        return $query->get();
    }

    /**
     * Mark an insurance payment as claimed.
     */
    public function claimPayment(int $invoicePaymentId): bool
    {
        $payment = InvoicePayment::find($invoicePaymentId);
        $payment->is_claimed = 1;

        return $payment->save();
    }
}
