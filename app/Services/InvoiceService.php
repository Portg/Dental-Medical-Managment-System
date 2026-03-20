<?php

namespace App\Services;

use App\Http\Helper\NameHelper;
use App\Invoice;
use App\InvoiceItem;
use App\InvoicePayment;
use App\MedicalService;
use App\Services\StockOutService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\DataTables;

class InvoiceService
{
    private StockOutService $stockOutService;

    public function __construct(StockOutService $stockOutService)
    {
        $this->stockOutService = $stockOutService;
    }

    // ─── Financial calculations ───────────────────────────────────

    public function totalInvoiceAmount(int $invoiceId): string
    {
        return (string) InvoiceItem::where('invoice_id', $invoiceId)->sum(DB::raw('price*qty'));
    }

    public function totalInvoicePaidAmount(int $invoiceId): string
    {
        return (string) InvoicePayment::where('invoice_id', $invoiceId)->sum('amount');
    }

    public function invoiceBalance(int $invoiceId): string
    {
        $total   = $this->totalInvoiceAmount($invoiceId);
        $paid    = $this->totalInvoicePaidAmount($invoiceId);
        $balance = bcsub($total, $paid, 2);
        return bccomp($balance, '0', 2) >= 0 ? $balance : '0';
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
            ->leftJoin('appointments', 'appointments.id', 'invoices.appointment_id')
            ->leftJoin('patients', 'patients.id', DB::raw('COALESCE(invoices.patient_id, appointments.patient_id)'))
            ->join('users', 'users.id', 'invoices._who_added')
            ->whereNull('invoices.deleted_at')
            // Exclude soft-deleted patients, but keep invoices with no patient (patients.id IS NULL)
            ->where(function ($q) {
                $q->whereNull('patients.deleted_at')->orWhereNull('patients.id');
            })
            ->select(
                'invoices.*',
                'patients.surname', 'patients.othername', 'patients.email',
                'users.othername as addedBy',
                DB::raw('(SELECT COALESCE(SUM(ii.price * ii.qty), 0) FROM invoice_items ii WHERE ii.invoice_id = invoices.id AND ii.deleted_at IS NULL) as computed_total'),
                DB::raw('(SELECT COALESCE(SUM(ip.amount), 0) FROM invoice_payments ip WHERE ip.invoice_id = invoices.id) as computed_paid')
            );
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
     * Uses COALESCE to support both appointment-linked and direct billing invoices.
     */
    public function getPatientInvoices(int $patientId): Collection
    {
        return DB::table('invoices')
            ->leftJoin('appointments', 'appointments.id', 'invoices.appointment_id')
            ->whereNull('invoices.deleted_at')
            ->where(DB::raw('COALESCE(invoices.patient_id, appointments.patient_id)'), $patientId)
            ->select(
                'invoices.*',
                DB::raw('appointments.status as appointment_status'),
                DB::raw('(SELECT COALESCE(SUM(ii.price * ii.qty), 0) FROM invoice_items ii WHERE ii.invoice_id = invoices.id AND ii.deleted_at IS NULL) as computed_total'),
                DB::raw('(SELECT COALESCE(SUM(ip.amount), 0) FROM invoice_payments ip WHERE ip.invoice_id = invoices.id) as computed_paid')
            )
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
        $invoice = Invoice::findOrFail($invoiceId);

        // Support both appointment-linked and direct billing invoices
        if ($invoice->patient_id) {
            $patient = DB::table('patients')->where('id', $invoice->patient_id)->first();
        } elseif ($invoice->appointment_id) {
            $patient = DB::table('patients')
                ->join('appointments', 'appointments.patient_id', 'patients.id')
                ->where('appointments.id', $invoice->appointment_id)
                ->select('patients.*')
                ->first();
        } else {
            $patient = null;
        }

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
        $invoice = Invoice::with('items')->findOrFail($invoiceId);

        return [
            'invoice' => $invoice,
            'invoice_items' => $invoice->items,
        ];
    }

    /**
     * Get data needed for printing a receipt PDF.
     */
    public function getReceiptData(int $invoiceId): array
    {
        $invoice = Invoice::with(['items', 'payments'])->findOrFail($invoiceId);

        $fullNameExpr = app()->getLocale() === 'zh-CN'
            ? "CONCAT(patients.surname, patients.othername) as full_name"
            : "CONCAT(patients.surname, ' ', patients.othername) as full_name";

        // Support both appointment-linked and direct billing invoices
        if ($invoice->patient_id) {
            $patient = DB::table('patients')
                ->where('id', $invoice->patient_id)
                ->select('patients.*', DB::raw($fullNameExpr))
                ->first();
        } elseif ($invoice->appointment_id) {
            $patient = DB::table('patients')
                ->join('appointments', 'appointments.patient_id', 'patients.id')
                ->where('appointments.id', $invoice->appointment_id)
                ->select('patients.*', DB::raw($fullNameExpr))
                ->first();
        } else {
            $patient = null;
        }

        return [
            'patient' => $patient,
            'invoice' => $invoice,
            'invoice_items' => $invoice->items,
            'payments' => $invoice->payments,
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
        return $this->getInvoiceProcedures($invoiceId)->pluck('name')->implode('');
    }

    // ─── Export ───────────────────────────────────────────────────

    /**
     * Get invoice data for Excel export.
     */
    public function getExportData(?string $from, ?string $to): Collection
    {
        $query = DB::table('invoices')
            ->leftJoin('appointments', 'appointments.id', 'invoices.appointment_id')
            ->leftJoin('patients', 'patients.id', DB::raw('COALESCE(invoices.patient_id, appointments.patient_id)'))
            ->join('users', 'users.id', 'invoices._who_added')
            ->whereNull('invoices.deleted_at')
            ->where(function ($q) {
                $q->whereNull('patients.deleted_at')->orWhereNull('patients.id');
            })
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

        // AG-069: 追加服务增量库存扣减（事务外执行，与 createBillingInvoice 保持一致）
        $appointment = \App\Appointment::find($appointmentId);
        if ($appointment && !empty($items)) {
            $this->stockOutService->appendBillingStockOut(
                $invoice->id,
                $appointment->patient_id,
                $appointmentId,
                $items
            );
        }

        return ['message' => __('invoices.invoice_created_successfully'), 'status' => true];
    }

    /**
     * Delete (soft-delete) an invoice.
     * AG-050: 事务性回滚前台代销出库单 + 批次库存。
     *
     * @return array{message: string, status: bool}
     */
    public function deleteInvoice(int $id): array
    {
        $invoice = Invoice::find($id);

        if (!$invoice) {
            return ['message' => __('invoices.no_invoices_found'), 'status' => false];
        }

        // AG-050: 库存回滚 + 软删除在同一外层事务内，保证原子性
        // rollbackBillingStockOut 内部的 beginTransaction 在此处变为 savepoint
        DB::beginTransaction();
        try {
            $this->stockOutService->rollbackBillingStockOut($invoice->id);

            if (!$invoice->delete()) {
                DB::rollBack();
                return ['message' => __('messages.error_occurred'), 'status' => false];
            }

            DB::commit();
            return ['message' => __('invoices.invoice_deleted_successfully'), 'status' => true];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('deleteInvoice failed', ['invoice_id' => $id, 'error' => $e->getMessage()]);
            return ['message' => __('messages.error_occurred'), 'status' => false];
        }
    }

    // ─── Email ────────────────────────────────────────────────────

    /**
     * Get data for sending an invoice email and dispatch the job.
     */
    public function sendInvoiceEmail(int $invoiceId, string $email, ?string $message = null): void
    {
        $invoice = Invoice::with(['items', 'payments'])->findOrFail($invoiceId);

        $fullNameExpr = app()->getLocale() === 'zh-CN'
            ? "CONCAT(patients.surname, patients.othername) as full_name"
            : "CONCAT(patients.surname, ' ', patients.othername) as full_name";
        $selectCols = ['patients.surname', 'patients.othername', 'patients.email', 'patients.phone_no', DB::raw($fullNameExpr)];

        if ($invoice->patient_id) {
            $patient = DB::table('patients')
                ->where('id', $invoice->patient_id)
                ->select($selectCols)
                ->first();
        } elseif ($invoice->appointment_id) {
            $patient = DB::table('patients')
                ->join('appointments', 'appointments.patient_id', 'patients.id')
                ->where('appointments.id', $invoice->appointment_id)
                ->select($selectCols)
                ->first();
        } else {
            $patient = null;
        }

        $data = [
            'patient' => $patient,
            'invoice' => $invoice,
            'invoice_items' => $invoice->items,
            'payments' => $invoice->payments,
        ];

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

        if ($invoice->discount_approval_status !== Invoice::DISCOUNT_PENDING) {
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

        if ($invoice->discount_approval_status !== Invoice::DISCOUNT_PENDING) {
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

        if ($invoice->payment_status === Invoice::PAYMENT_PAID) {
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

    // ─── Billing (划价) ─────────────────────────────────────────

    /**
     * Get service category tree for billing left panel.
     * Cached for 6 hours.
     */
    public function getServiceCategoryTree(): array
    {
        return Cache::remember('billing_service_category_tree', 360 * 60, function () {
            $services = MedicalService::where('is_active', true)
                ->whereNull('deleted_at')
                ->orderBy('category')
                ->orderBy('name')
                ->get(['id', 'name', 'unit', 'price', 'category']);

            $tree = [];
            foreach ($services as $svc) {
                $cat = $svc->category ?: __('invoices.select_category');
                if (!isset($tree[$cat])) {
                    $tree[$cat] = [];
                }
                $tree[$cat][] = [
                    'id'       => $svc->id,
                    'name'     => $svc->name,
                    'unit'     => $svc->unit ?: '次',
                    'price'    => (string) $svc->price,
                    'category' => $cat,
                ];
            }

            return $tree;
        });
    }

    /**
     * Create a billing invoice (划价账单).
     *
     * @param int    $patientId
     * @param array  $items           [{medical_service_id, qty, price, discount_rate, discounted_price, actual_paid, arrears, tooth_no?, doctor_id?}]
     * @param array  $payments        [{payment_method, amount, cheque_no?, account_name?, bank_name?, insurance_company_id?, self_account_id?, transaction_ref?}]
     * @param float  $orderDiscountRate  整单折扣率 (0-100)
     * @param string|null $paymentDate
     * @param string $billingMode     'direct' or 'front_desk'
     * @return array{status: bool, message: string, invoice_id?: int}
     */
    public function createBillingInvoice(
        int $patientId,
        array $items,
        array $payments,
        float $orderDiscountRate = 100,
        ?string $paymentDate = null,
        string $billingMode = 'direct'
    ): array {
        if (empty($items)) {
            return ['status' => false, 'message' => __('invoices.no_billing_items')];
        }

        DB::beginTransaction();
        try {
            // Calculate totals from items (AG-065: bcmath for all monetary accumulation)
            $subtotal         = '0';
            $totalDiscounted  = '0';
            $totalActualPaid  = '0';
            $totalArrears     = '0';

            foreach ($items as $item) {
                $lineTotal       = bcmul((string) ($item['price'] ?? 0), (string) ($item['qty'] ?? 1), 2);
                $subtotal        = bcadd($subtotal, $lineTotal, 2);
                $totalDiscounted = bcadd($totalDiscounted, (string) ($item['discounted_price'] ?? $lineTotal), 2);
                $totalActualPaid = bcadd($totalActualPaid, (string) ($item['actual_paid'] ?? ($item['discounted_price'] ?? $lineTotal)), 2);
                $totalArrears    = bcadd($totalArrears, (string) ($item['arrears'] ?? 0), 2);
            }

            // Apply order discount
            $orderDiscountAmount = '0';
            if ($orderDiscountRate > 0 && $orderDiscountRate < 100) {
                $rate                = bcdiv((string) $orderDiscountRate, '100', 10);
                $orderDiscountAmount = bcsub($totalDiscounted, bcmul($totalDiscounted, $rate, 2), 2);
                $totalDiscounted     = bcmul($totalDiscounted, $rate, 2);
            }

            $discountAmount = bcsub($subtotal, $totalDiscounted, 2);

            // Create invoice
            $invoice = Invoice::create([
                'invoice_no'           => Invoice::InvoiceNo(),
                'invoice_date'         => $paymentDate ?? now()->format('Y-m-d'),
                'patient_id'           => $patientId,
                'subtotal'             => $subtotal,
                'discount_amount'      => $discountAmount,
                'order_discount_rate'  => $orderDiscountRate,
                'order_discount_amount' => $orderDiscountAmount,
                'total_amount'         => $totalDiscounted,
                'paid_amount'          => 0,
                'outstanding_amount'   => $totalDiscounted,
                'payment_status'       => Invoice::PAYMENT_UNPAID,
                'billing_mode'         => $billingMode,
                '_who_added'           => Auth::id(),
            ]);

            // Create invoice items
            foreach ($items as $item) {
                $lineTotal = bcmul((string)($item['price'] ?? 0), (string)($item['qty'] ?? 1), 2);
                InvoiceItem::create([
                    'invoice_id'         => $invoice->id,
                    'medical_service_id' => $item['medical_service_id'],
                    'qty'                => $item['qty'] ?? 1,
                    'price'              => $item['price'] ?? 0,
                    'discount_rate'      => $item['discount_rate'] ?? 100,
                    'discounted_price'   => $item['discounted_price'] ?? $lineTotal,
                    'actual_paid'        => $item['actual_paid'] ?? ($item['discounted_price'] ?? $lineTotal),
                    'arrears'            => $item['arrears'] ?? 0,
                    'tooth_no'           => $item['tooth_no'] ?? null,
                    'doctor_id'          => $item['doctor_id'] ?? null,
                    '_who_added'         => Auth::id(),
                ]);
            }

            // Check discount approval threshold (BR-035)
            if ($discountAmount > Invoice::DISCOUNT_APPROVAL_THRESHOLD) {
                $invoice->discount_approval_status = Invoice::DISCOUNT_PENDING;
                $invoice->save();
            }

            // Process payment for direct billing mode
            if ($billingMode === 'direct' && !empty($payments)) {
                // Only process payment if discount is approved or below threshold
                if ($invoice->canAcceptPayment()) {
                    $paymentService = app(InvoicePaymentService::class);
                    $paymentResult = $paymentService->processMixedPayment(
                        $invoice->id,
                        $payments,
                        $paymentDate
                    );

                    if (!$paymentResult['status']) {
                        DB::rollBack();
                        return $paymentResult;
                    }
                }
            }

            DB::commit();

            // 前台代销：发票创建成功后，事务外扣减库存（AG-049: 幂等；AG-051: 不足时允许收费）
            $this->stockOutService->createBillingStockOut(
                $invoice->id,
                $patientId,
                null, // 直接划价账单无 appointment_id
                $items
            );

            // Determine response message
            if ($billingMode === 'direct' && $invoice->discount_approval_status === Invoice::DISCOUNT_PENDING) {
                $message = __('invoices.billing_created_discount_pending', [
                    'amount' => Invoice::DISCOUNT_APPROVAL_THRESHOLD,
                ]);
            } elseif ($billingMode === 'front_desk') {
                $message = __('invoices.billing_created_pending');
            } else {
                $message = __('invoices.billing_created_successfully');
            }

            return ['status' => true, 'message' => $message, 'invoice_id' => $invoice->id];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('createBillingInvoice failed', ['patient_id' => $patientId, 'error' => $e->getMessage()]);
            return ['status' => false, 'message' => __('messages.error_occurred')];
        }
    }

    /**
     * Get patient receipts (payment records) for receipts tab.
     */
    public function getPatientReceipts(int $patientId): Collection
    {
        return DB::table('invoice_payments')
            ->join('invoices', 'invoices.id', 'invoice_payments.invoice_id')
            ->leftJoin('appointments', 'appointments.id', 'invoices.appointment_id')
            ->leftJoin('users', 'users.id', 'invoice_payments._who_added')
            ->whereNull('invoices.deleted_at')
            ->whereNull('invoice_payments.deleted_at')
            ->where(DB::raw('COALESCE(invoices.patient_id, appointments.patient_id)'), $patientId)
            ->select(
                'invoice_payments.*',
                'invoices.invoice_no',
                DB::raw("users.othername as added_by_name")
            )
            ->orderBy('invoice_payments.created_at', 'desc')
            ->get();
    }

    /**
     * Build DataTables response for patient receipts.
     */
    public function buildPatientReceiptsDataTable($data)
    {
        return DataTables::of($data)
            ->addIndexColumn()
            ->editColumn('payment_date', function ($row) {
                return $row->payment_date ?: ($row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('Y-m-d') : '-');
            })
            ->addColumn('amount_formatted', function ($row) {
                return number_format($row->amount, 2);
            })
            ->addColumn('method_label', function ($row) {
                return \App\DictItem::nameByCode('invoice_payment_method', $row->payment_method)
                    ?? $row->payment_method;
            })
            ->rawColumns([])
            ->make(true);
    }

    // ─── DataTable builders ─────────────────────────────────────

    /**
     * Build DataTables response for the invoice index page.
     */
    public function buildIndexDataTable($data)
    {
        return Datatables::of($data)
            ->addIndexColumn()
            ->editColumn('created_at', function ($row) {
                return $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('Y-m-d') : '-';
            })
            ->filter(function ($instance) {
            })
            ->addColumn('invoice_no', function ($row) {
                return '<a href="' . url('invoices/' . $row->id) . '">' . e($row->invoice_no) . '</a>';
            })
            ->addColumn('customer', function ($row) {
                return NameHelper::join($row->surname, $row->othername);
            })
            ->addColumn('amount', function ($row) {
                return number_format($row->computed_total);
            })
            ->addColumn('paid_amount', function ($row) {
                return number_format($row->computed_paid);
            })
            ->addColumn('due_amount', function ($row) {
                $balance = bcsub((string)$row->computed_total, (string)$row->computed_paid, 2);
                if (bccomp($balance, '0', 2) <= 0) {
                    return number_format($balance, 2);
                }
                return number_format($balance, 2) . '<br>
                    <a href="#" onclick="record_payment(' . $row->id . ')" class="text-primary">' . __('invoices.record_payment') . '</a>
                    ';
            })
            ->addColumn('action', function ($row) {
                return '
                      <div class="btn-group">
                        <button class="btn blue dropdown-toggle" type="button" data-toggle="dropdown"
                                aria-expanded="false"> ' . __('common.action') . '
                            <i class="fa fa-angle-down"></i>
                        </button>
                        <ul class="dropdown-menu" role="menu">
                        <li>
                        <a href="#" onClick="viewInvoiceProcedures('.$row->id.')"> ' . __('invoices.view_procedures_done') . '</a>
                    </li>
                    <li>
                                <a href="' . url('invoices/' . $row->id) . '"> ' . __('invoices.view_invoice_details') . '</a>
                            </li>
                             <li>
                                <a target="_blank" href="' . url('print-receipt/' . $row->id) . '"  > ' . __('invoices.print') . ' </a>
                            </li>
                              <li>
                         <a  href="#" onClick="shareInvoiceView(' . $row->id . ')"> ' . __('invoices.share_invoice') . ' </a>
                            </li>
                            <li class="divider"></li>
                            <li>
                                <a href="#" onClick="deleteInvoice(' . $row->id . ')" class="text-danger"> ' . __('invoices.delete_invoice') . '</a>
                            </li>
                        </ul>
                    </div>
                    ';
            })
            ->rawColumns(['invoice_no', 'due_amount', 'payment_classification', 'action', 'status'])
            ->make(true);
    }

    /**
     * Build DataTables response for patient-specific invoices.
     */
    public function buildPatientInvoicesDataTable($data)
    {
        return Datatables::of($data)
            ->addIndexColumn()
            ->editColumn('created_at', function ($row) {
                return $row->created_at ? \Carbon\Carbon::parse($row->created_at)->format('Y-m-d') : '-';
            })
            ->addColumn('amount', function ($row) {
                return number_format($row->computed_total, 2);
            })
            ->addColumn('paid_amount', function ($row) {
                return number_format($row->computed_paid, 2);
            })
            ->addColumn('statusBadge', function ($row) {
                $balance = bcsub((string)$row->computed_total, (string)$row->computed_paid, 2);
                $total = (string)$row->computed_total;
                if (bccomp($total, '0', 2) <= 0) {
                    $class = 'default';
                    $text = '-';
                } elseif (bccomp($balance, '0', 2) <= 0) {
                    $class = 'success';
                    $text = __('invoices.paid');
                } elseif (bccomp($balance, $total, 2) < 0) {
                    $class = 'warning';
                    $text = __('invoices.partially_paid');
                } else {
                    $class = 'danger';
                    $text = __('invoices.unpaid');
                }
                return '<span class="label label-' . $class . '">' . $text . '</span>';
            })
            ->addColumn('viewBtn', function ($row) {
                return '<a href="' . url('invoices/' . $row->id) . '" class="btn btn-info btn-sm">' . __('common.view') . '</a>';
            })
            ->rawColumns(['statusBadge', 'viewBtn'])
            ->make(true);
    }

    /**
     * Build DataTables response for pending discount approvals.
     */
    public function buildDiscountApprovalsDataTable($data)
    {
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('invoice_no', function ($row) {
                return '<a href="' . url('invoices/' . $row->id) . '">' . e($row->invoice_no) . '</a>';
            })
            ->addColumn('patient_name', function ($row) {
                return $row->patient ? $row->patient->full_name : '-';
            })
            ->addColumn('subtotal', function ($row) {
                return number_format($row->subtotal, 2);
            })
            ->addColumn('discount_amount', function ($row) {
                return number_format($row->discount_amount, 2);
            })
            ->addColumn('total_amount', function ($row) {
                return number_format($row->total_amount, 2);
            })
            ->addColumn('added_by', function ($row) {
                return $row->addedBy ? $row->addedBy->othername : '-';
            })
            ->addColumn('action', function ($row) {
                return '
                        <button class="btn btn-sm btn-success" onclick="approveDiscount(' . $row->id . ')">
                            <i class="fa fa-check"></i> ' . __('invoices.approve') . '
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="rejectDiscount(' . $row->id . ')">
                            <i class="fa fa-times"></i> ' . __('invoices.reject') . '
                        </button>
                    ';
            })
            ->rawColumns(['invoice_no', 'action'])
            ->make(true);
    }
}
