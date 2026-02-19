<?php

namespace App\Services;

use App\InvoiceItem;
use App\InvoicePayment;
use App\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DoctorPerformanceReportService
{
    /**
     * Get all doctors.
     */
    public function getDoctors(): Collection
    {
        return User::where('is_doctor', true)->orderBy('id', 'DESC')->get();
    }

    /**
     * Get doctor performance data filtered by date range and doctor.
     */
    public function getPerformanceData(int $doctorId, string $startDate, string $endDate): Collection
    {
        return DB::table('invoice_items')
            ->join('invoices', 'invoices.id', 'invoice_items.invoice_id')
            ->join('appointments', 'appointments.id', 'invoices.appointment_id')
            ->join('patients', 'patients.id', 'appointments.patient_id')
            ->whereNull('invoice_items.deleted_at')
            ->whereNull('invoices.deleted_at')
            ->where('invoice_items.doctor_id', $doctorId)
            ->whereBetween(DB::raw('DATE_FORMAT(invoices.created_at, \'%Y-%m-%d\')'), [$startDate, $endDate])
            ->select(
                'invoice_items.*',
                'patients.surname',
                'patients.othername',
                DB::raw('sum(price*qty) as amount'),
                DB::raw("(SELECT COALESCE(SUM(ii2.qty * ii2.price), 0) FROM invoice_items ii2 WHERE ii2.invoice_id = invoice_items.invoice_id AND ii2.deleted_at IS NULL) as invoice_total_amount"),
                DB::raw("(SELECT COALESCE(SUM(ip.amount), 0) FROM invoice_payments ip WHERE ip.invoice_id = invoice_items.invoice_id) as invoice_paid_amount")
            )
            ->groupBy('invoice_items.invoice_id')
            ->get();
    }

    /**
     * Get export data for the performance report.
     */
    public function getExportData(int $doctorId, string $from, string $to): Collection
    {
        return DB::table('invoice_items')
            ->leftJoin('invoices', 'invoices.id', 'invoice_items.invoice_id')
            ->leftJoin('appointments', 'appointments.id', 'invoices.appointment_id')
            ->leftJoin('patients', 'patients.id', 'appointments.patient_id')
            ->whereNull('invoice_items.deleted_at')
            ->whereNull('invoices.deleted_at')
            ->where('invoice_items.doctor_id', $doctorId)
            ->whereBetween(DB::raw('DATE_FORMAT(invoice_items.created_at, \'%Y-%m-%d\')'), [$from, $to])
            ->select(
                'invoice_no',
                'invoices.created_at',
                'invoice_items.invoice_id',
                'invoice_items.doctor_id',
                'patients.surname',
                'patients.othername',
                DB::raw('sum(qty*price) as total_amount')
            )
            ->groupBy('invoice_items.invoice_id')
            ->get();
    }

    /**
     * Get doctor by ID.
     */
    public function findDoctor(int $id): ?User
    {
        return User::where('id', $id)->first();
    }

    /**
     * Get total invoice amount.
     */
    public function totalInvoiceAmount(int $invoiceId): float
    {
        return (float) InvoiceItem::where('invoice_id', $invoiceId)->sum(DB::raw('qty*price'));
    }

    /**
     * Get total paid amount for an invoice.
     */
    public function totalInvoicePaidAmount(int $invoiceId): float
    {
        return (float) InvoicePayment::where('invoice_id', $invoiceId)->sum('amount');
    }

    /**
     * Get invoice balance (total - paid).
     */
    public function invoiceBalance(int $invoiceId): float
    {
        return $this->totalInvoiceAmount($invoiceId) - $this->totalInvoicePaidAmount($invoiceId);
    }
}
