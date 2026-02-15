<?php

namespace App\Services;

use App\Http\Helper\NameHelper;
use App\Invoice;
use App\InvoiceItem;
use App\InvoicePayment;
use App\MedicalService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InvoiceService
{
    // ─── Financial calculations ───────────────────────────────────

    public function totalInvoiceAmount(int $invoiceId): float
    {
        return (float) InvoiceItem::where('invoice_id', $invoiceId)->sum(DB::raw('price*qty'));
    }

    public function totalInvoicePaidAmount(int $invoiceId): float
    {
        return (float) InvoicePayment::where('invoice_id', $invoiceId)->sum('amount');
    }

    public function invoiceBalance(int $invoiceId): float
    {
        return $this->totalInvoiceAmount($invoiceId) - $this->totalInvoicePaidAmount($invoiceId);
    }

    public function cashAmountPaid(int $invoiceId): float
    {
        return (float) InvoicePayment::where(['invoice_id' => $invoiceId, 'payment_method' => 'Cash'])->sum('amount');
    }

    public function selfAccountAmountPaid(int $invoiceId): float
    {
        return (float) InvoicePayment::where(['invoice_id' => $invoiceId, 'payment_method' => 'Self Account'])->sum('amount');
    }

    public function insuranceAmountPaid(int $invoiceId): float
    {
        return (float) InvoicePayment::where(['invoice_id' => $invoiceId, 'payment_method' => 'Insurance'])->sum('amount');
    }

    // ─── Query helpers ────────────────────────────────────────────

    public function patientInfoByAppointment(int $appointmentId)
    {
        return DB::table('appointments')
            ->join('patients', 'patients.id', 'appointments.patient_id')
            ->join('insurance_companies', 'insurance_companies.id', 'patients.insurance_company_id')
            ->where('appointments.id', $appointmentId)
            ->select('patients.*', 'insurance_companies.name')
            ->first();
    }

    // ─── List / filter ────────────────────────────────────────────

    /**
     * Base query for invoice list (joins patients + users).
     */
    private function invoiceListQuery()
    {
        return DB::table('invoices')
            ->join('appointments', 'appointments.id', 'invoices.appointment_id')
            ->join('patients', 'patients.id', 'appointments.patient_id')
            ->join('users', 'users.id', 'invoices._who_added')
            ->select('invoices.*', 'patients.surname', 'patients.othername', 'patients.email', 'users.othername as addedBy');
    }

    /**
     * Get filtered invoice list for DataTables.
     */
    public function getInvoiceList(array $filters): Collection
    {
        $query = $this->invoiceListQuery();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                NameHelper::addNameSearch($q, $search, 'patients');
            });
        } elseif (!empty($filters['invoice_no'])) {
            $query->where('invoices.invoice_no', '=', $filters['invoice_no']);
        } elseif (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween(
                DB::raw("DATE_FORMAT(invoices.created_at, '%Y-%m-%d')"),
                [$filters['start_date'], $filters['end_date']]
            );
        }

        return $query->orderBy('invoices.id', 'desc')->get();
    }

    /**
     * Get patient-specific invoices for patient detail page.
     */
    public function getPatientInvoices(int $patientId): Collection
    {
        return DB::table('invoices')
            ->join('appointments', 'appointments.id', 'invoices.appointment_id')
            ->join('patients', 'patients.id', 'appointments.patient_id')
            ->whereNull('invoices.deleted_at')
            ->where('patients.id', $patientId)
            ->select('invoices.*', 'appointments.status as appointment_status')
            ->orderBy('invoices.created_at', 'desc')
            ->get();
    }

    // ─── Single invoice ───────────────────────────────────────────

    /**
     * Get invoice share details (patient + addedBy).
     */
    public function getInvoiceShareDetails(int $invoiceId)
    {
        return $this->invoiceListQuery()
            ->where('invoices.id', $invoiceId)
            ->first();
    }

    /**
     * Get invoice balance info for payment clearance view.
     */
    public function getInvoiceAmountData(int $invoiceId): array
    {
        $invoice = Invoice::findOrFail($invoiceId);

        return [
            'amount' => $this->invoiceBalance($invoiceId),
            'today_date' => date('Y-m-d'),
            'patient' => $this->patientInfoByAppointment($invoice->appointment_id),
        ];
    }

    /**
     * Get invoice detail for show page.
     */
    public function getInvoiceDetail(int $invoiceId): array
    {
        $patient = DB::table('invoices')
            ->join('appointments', 'appointments.id', 'invoices.appointment_id')
            ->join('patients', 'patients.id', 'appointments.patient_id')
            ->where('invoices.id', $invoiceId)
            ->select('patients.*')
            ->first();

        $invoice = Invoice::where('id', $invoiceId)->first();

        return [
            'patient' => $patient,
            'invoice_id' => $invoiceId,
            'invoice' => $invoice,
        ];
    }

    /**
     * Get invoice preview data.
     */
    public function getPreviewData(int $invoiceId): array
    {
        return [
            'invoice' => Invoice::where('id', $invoiceId)->first(),
            'invoice_items' => InvoiceItem::where('invoice_id', $invoiceId)->get(),
        ];
    }

    /**
     * Get data needed for printing a receipt PDF.
     */
    public function getReceiptData(int $invoiceId): array
    {
        $patient = DB::table('invoices')
            ->leftJoin('appointments', 'appointments.id', 'invoices.appointment_id')
            ->leftJoin('patients', 'patients.id', 'appointments.patient_id')
            ->where('invoices.id', $invoiceId)
            ->select('patients.*', DB::raw(
                app()->getLocale() === 'zh-CN'
                    ? "CONCAT(patients.surname, patients.othername) as full_name"
                    : "CONCAT(patients.surname, ' ', patients.othername) as full_name"
            ))
            ->first();

        return [
            'patient' => $patient,
            'invoice' => Invoice::where('id', $invoiceId)->first(),
            'invoice_items' => InvoiceItem::where('invoice_id', $invoiceId)->get(),
            'payments' => InvoicePayment::where('invoice_id', $invoiceId)->get(),
        ];
    }

    /**
     * Get invoice procedures for JSON display.
     */
    public function getInvoiceProcedures(int $invoiceId): Collection
    {
        return DB::table('invoice_items')
            ->leftJoin('medical_services', 'medical_services.id', 'invoice_items.medical_service_id')
            ->whereNull('invoice_items.deleted_at')
            ->where('invoice_items.invoice_id', $invoiceId)
            ->select(
                'medical_services.name',
                'invoice_items.qty',
                'invoice_items.price',
                DB::raw('invoice_items.qty*invoice_items.price as total')
            )
            ->get();
    }

    /**
     * Get concatenated procedure names for an invoice.
     */
    public function getInvoiceProcedureNames(int $invoiceId): string
    {
        $procedures = DB::table('invoice_items')
            ->leftJoin('medical_services', 'medical_services.id', 'invoice_items.medical_service_id')
            ->whereNull('invoice_items.deleted_at')
            ->where('invoice_items.invoice_id', $invoiceId)
            ->select('medical_services.name', 'invoice_items.*')
            ->get();

        return $procedures->pluck('name')->implode('');
    }

    // ─── Export ───────────────────────────────────────────────────

    /**
     * Get invoice data for Excel export.
     */
    public function getExportData(?string $from, ?string $to): Collection
    {
        $query = DB::table('invoices')
            ->join('appointments', 'appointments.id', 'invoices.appointment_id')
            ->join('patients', 'patients.id', 'appointments.patient_id')
            ->join('users', 'users.id', 'invoices._who_added')
            ->select('invoices.*', 'patients.surname', 'patients.othername', 'users.othername as addedBy');

        if ($from && $to) {
            $query->whereBetween(DB::raw('DATE(invoices.created_at)'), [$from, $to]);
        }

        return $query->orderBy('invoices.id', 'ASC')->get();
    }

    // ─── CUD operations ──────────────────────────────────────────

    /**
     * Create or append invoice items for an appointment.
     *
     * @return array{message: string, status: bool}
     */
    public function createInvoice(int $appointmentId, array $items): array
    {
        $invoice = Invoice::where('appointment_id', $appointmentId)->first();

        if (!$invoice) {
            $invoice = Invoice::create([
                'invoice_no' => Invoice::InvoiceNo(),
                'appointment_id' => $appointmentId,
                '_who_added' => Auth::user()->id,
            ]);

            if (!$invoice) {
                return ['message' => __('messages.error_occurred_later'), 'status' => false];
            }
        }

        foreach ($items as $item) {
            InvoiceItem::create([
                'qty' => $item['qty'],
                'price' => $item['price'],
                'invoice_id' => $invoice->id,
                'tooth_no' => $item['tooth_no'] ?? null,
                'medical_service_id' => $item['medical_service_id'],
                'doctor_id' => $item['doctor_id'],
                '_who_added' => Auth::user()->id,
            ]);
        }

        return ['message' => __('invoices.invoice_created_successfully'), 'status' => true];
    }

    /**
     * Delete (soft-delete) an invoice.
     *
     * @return array{message: string, status: bool}
     */
    public function deleteInvoice(int $id): array
    {
        $invoice = Invoice::find($id);

        if (!$invoice) {
            return ['message' => __('invoices.no_invoices_found'), 'status' => false];
        }

        if ($invoice->delete()) {
            return ['message' => __('invoices.invoice_deleted_successfully'), 'status' => true];
        }

        return ['message' => __('messages.error_occurred'), 'status' => false];
    }

    // ─── Email ────────────────────────────────────────────────────

    /**
     * Get data for sending an invoice email and dispatch the job.
     */
    public function sendInvoiceEmail(int $invoiceId, string $email, ?string $message = null): void
    {
        $data = [];

        $data['patient'] = DB::table('invoices')
            ->leftJoin('appointments', 'appointments.id', 'invoices.appointment_id')
            ->leftJoin('patients', 'patients.id', 'appointments.patient_id')
            ->where('invoices.id', $invoiceId)
            ->select('surname', 'othername', 'email', 'phone_no')
            ->first();

        $data['invoice'] = Invoice::where('id', $invoiceId)->first();
        $data['invoice_items'] = InvoiceItem::where('invoice_id', $invoiceId)->get();
        $data['payments'] = InvoicePayment::where('invoice_id', $invoiceId)->get();

        dispatch(new \App\Jobs\ShareEmailInvoice($data, $email, $message));
    }

    // ─── Discount approval ───────────────────────────────────────

    /**
     * Get pending discount approval invoices.
     */
    public function getPendingDiscountApprovals(): Collection
    {
        return Invoice::pendingDiscountApproval()
            ->with(['patient', 'addedBy'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Approve a pending discount.
     *
     * @return array{message: string, status: bool}
     */
    public function approveDiscount(int $id, int $approverId, ?string $reason = null): array
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->discount_approval_status !== 'pending') {
            return ['message' => __('invoices.discount_not_pending'), 'status' => false];
        }

        $invoice->approveDiscount($approverId, $reason);

        return ['message' => __('invoices.discount_approved'), 'status' => true];
    }

    /**
     * Reject a pending discount.
     *
     * @return array{message: string, status: bool}
     */
    public function rejectDiscount(int $id, int $approverId, string $reason): array
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->discount_approval_status !== 'pending') {
            return ['message' => __('invoices.discount_not_pending'), 'status' => false];
        }

        $invoice->rejectDiscount($approverId, $reason);

        return ['message' => __('invoices.discount_rejected'), 'status' => true];
    }

    /**
     * Set an invoice as credit (挂账).
     *
     * @return array{message: string, status: bool}
     */
    public function setCredit(int $id, int $approverId): array
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->payment_status === 'paid') {
            return ['message' => __('invoices.invoice_already_paid'), 'status' => false];
        }

        $invoice->setAsCredit($approverId);

        return ['message' => __('invoices.credit_approved'), 'status' => true];
    }

    // ─── Search ──────────────────────────────────────────────────

    /**
     * Search invoices by invoice_no or patient name.
     */
    public function searchInvoices(string $search): Collection
    {
        return DB::table('invoices')
            ->leftJoin('appointments', 'appointments.id', 'invoices.appointment_id')
            ->leftJoin('patients', 'patients.id', DB::raw('COALESCE(invoices.patient_id, appointments.patient_id)'))
            ->where(function ($query) use ($search) {
                $query->where('invoices.invoice_no', 'like', "%{$search}%");
                NameHelper::addNameSearch($query, $search, 'patients');
            })
            ->whereNull('invoices.deleted_at')
            ->select(
                'invoices.id',
                'invoices.invoice_no',
                DB::raw(app()->getLocale() === 'zh-CN'
                    ? "CONCAT(patients.surname, patients.othername) as patient_name"
                    : "CONCAT(patients.surname, ' ', patients.othername) as patient_name")
            )
            ->limit(20)
            ->get();
    }
}
