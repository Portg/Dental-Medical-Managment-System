<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Services\InvoiceService;
use App\Services\InvoicePaymentService;
use App\Exports\InvoiceExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use PDF;

class InvoiceController extends Controller
{
    private InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;

        $this->middleware('can:view-invoices')->only(['index', 'show', 'previewInvoice', 'invoiceShareDetails', 'sendInvoice', 'invoiceAmount', 'patientInvoices', 'printReceipt', 'exportReport', 'invoiceProceduresToJson', 'searchInvoices', 'getServiceCategories', 'patientReceipts', 'billingDetail']);
        $this->middleware('can:create-invoices')->only(['create', 'store', 'createBilling']);
        $this->middleware('can:edit-invoices')->only(['edit', 'update', 'pendingDiscountApprovals', 'approveDiscount', 'rejectDiscount', 'setCredit']);
        // addOverduePayment 不在这一行：它干两件事（补收欠款 / 减免尾款），
        // 权限按请求实际带了什么在方法里分别判，见该方法开头的注释。
        $this->middleware('can:view-invoices')->only(['addOverduePayment']);
        $this->middleware('can:delete-invoices')->only(['destroy']);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            if (!empty($request->start_date) && !empty($request->end_date)) {
                FunctionsHelper::storeDateFilter($request);
            }

            $data = $this->invoiceService->getInvoiceList([
                'search'     => $request->input('search.value', ''),
                'status'     => $request->input('status'),
                'start_date' => $request->input('start_date'),
                'end_date'   => $request->input('end_date'),
                'page'       => $request->input('page'),
                'per_page'   => $request->input('per_page'),
            ]);

            return $this->invoiceService->buildIndexDataTable($data);
        }
        return view('invoices.index');
    }

    public function previewInvoice($invoice_id)
    {
        $data = $this->invoiceService->getPreviewData((int) $invoice_id);
        return view('invoices.preview', $data);
    }

    public function invoiceShareDetails(Request $request, $invoice_id)
    {
        return response()->json($this->invoiceService->getInvoiceShareDetails((int) $invoice_id));
    }

    public function sendInvoice(Request $request)
    {
        Validator::make($request->all(), [
            'invoice_id' => 'required',
            'email' => 'required',
        ], [
            'invoice_id.required' => __('validation.attributes.invoice_id') . ' ' . __('validation.required'),
            'email.required' => __('validation.attributes.email') . ' ' . __('validation.required'),
        ])->validate();

        $this->invoiceService->sendInvoiceEmail((int) $request->invoice_id, $request->email, $request->message);

        return response()->json(['message' => __('emails.invoice_sent_successfully'), 'status' => true]);
    }

    public function invoiceAmount($invoice_id)
    {
        return response()->json($this->invoiceService->getInvoiceAmountData((int) $invoice_id));
    }

    /**
     * Patient-specific invoices for patient detail page.
     */
    public function patientInvoices(Request $request, $patient_id)
    {
        if ($request->ajax()) {
            $data = $this->invoiceService->getPatientInvoices((int) $patient_id);

            return $this->invoiceService->buildPatientInvoicesDataTable($data);
        }
    }

    public function printReceipt($invoice_id)
    {
        $data = $this->invoiceService->getReceiptData((int) $invoice_id);

        $pdf = PDF::loadView('invoices.receipt_print', $data);
        return $pdf->stream('receipt', array("attachment" => false))->header('Content-Type', 'application/pdf');
    }

    public function exportReport(Request $request)
    {
        $from = $request->session()->get('from') ?: null;
        $to = $request->session()->get('to') ?: null;

        $data = $this->invoiceService->getExportData($from, $to);

        \App\OperationLog::log('export', '账单管理', 'Invoice');
        \App\OperationLog::checkExportFrequency();

        return Excel::download(new InvoiceExport($data), 'invoicing-report-' . date('Y-m-d') . '.xlsx');
    }

    public function invoiceProceduresToJson($InvoiceId)
    {
        return response()->json($this->invoiceService->getInvoiceProcedures((int) $InvoiceId));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'appointment_id'               => 'required|integer|exists:appointments,id',
            'addmore'                       => 'required|array|min:1',
            'addmore.*.medical_service_id' => 'required|integer|exists:medical_services,id',
            'addmore.*.qty'                => 'required|numeric|min:0.01',
            'addmore.*.price'              => 'required|numeric|min:0',
            'addmore.*.doctor_id'          => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'status' => false]);
        }

        $result = $this->invoiceService->createInvoice((int) $request->appointment_id, $request->addmore);
        return response()->json($result);
    }

    /**
     * Display the specified resource.
     */
    public function show($invoice)
    {
        $data = $this->invoiceService->getInvoiceDetail((int) $invoice);
        return view('invoices.show.index')->with($data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($invoice)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $invoice)
    {
        $validator = Validator::make($request->all(), [
            'doctor_id'    => 'nullable|exists:users,id',
            'nurse_id'     => 'nullable|exists:users,id',
            'assistant_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'status' => 0], 422);
        }

        $this->invoiceService->updateStaff(
            (int) $invoice,
            $request->only(['doctor_id', 'nurse_id', 'assistant_id'])
        );

        return response()->json(['message' => __('common.updated_successfully'), 'status' => 1]);
    }

    /**
     * 收费面板详情 (收费 Tab)
     */
    public function billingDetail($id)
    {
        $data = $this->invoiceService->getBillingDetail((int) $id);
        return response()->json(['status' => 1, 'data' => $data]);
    }

    /**
     * 补收欠款 / 减免尾款。
     *
     * 这个接口干两件性质不同的事，权限按请求实际带了什么分别判，而不是一条
     * edit-invoices 全包：
     *   amount              → 收钱，要 collect-payments
     *   additional_discount → 减免尾款（把欠款一笔勾掉），是授权动作，要 edit-invoices
     *
     * 原来整个方法挂在 edit-invoices 上，而 edit-invoices 只有 super-admin 和 admin
     * 有 —— 前台，诊所里唯一负责收钱的人，补收不了欠款。那不是权限设计上的取舍，
     * 是漏配。改法与 createBilling 里的判定同一个模式。
     */
    public function addOverduePayment(Request $request, $id)
    {
        $invoice = \App\Invoice::find($id);
        if (!$invoice) {
            return response()->json(['message' => __('messages.record_not_found'), 'status' => 0], 404);
        }

        $collectsMoney = bccomp((string) ($request->input('amount') ?? '0'), '0', 2) > 0;
        $writesOff     = bccomp((string) ($request->input('additional_discount') ?? '0'), '0', 2) > 0;

        // 两个都是 0 的请求没有意义，而且会绕开上面两道权限判定（都不触发），
        // 让只有 view-invoices 的角色也能走到 Service 的写入分支。先挡掉。
        if (!$collectsMoney && !$writesOff) {
            return response()->json(['message' => __('invoices.overdue_nothing_to_do'), 'status' => 0], 422);
        }

        if ($collectsMoney && !$request->user()->can('collect-payments')) {
            return response()->json(['message' => __('invoices.no_permission_to_collect'), 'status' => 0], 403);
        }

        if ($writesOff && !$request->user()->can('edit-invoices')) {
            return response()->json(['message' => __('invoices.no_permission_to_write_off'), 'status' => 0], 403);
        }

        $validator = Validator::make($request->all(), [
            'amount'               => 'required_without:additional_discount|nullable|numeric|min:0',
            'additional_discount'  => 'nullable|numeric|min:0',
            'payment_method'       => ['required_with:amount', 'nullable', 'string', Rule::in(array_keys(InvoicePaymentService::PAYMENT_METHODS))],
            'payment_date'         => 'nullable|date',
            'cheque_no'            => 'required_if:payment_method,Cheque',
            'bank_name'            => 'required_if:payment_method,Cheque',
            'insurance_company_id' => 'required_if:payment_method,Insurance|nullable|exists:insurance_companies,id',
            'self_account_id'      => 'required_if:payment_method,Self Account|nullable|exists:self_accounts,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'status' => 0], 422);
        }

        $outstanding   = (string) $invoice->outstanding_amount;
        $amountInput   = (string) ($request->input('amount') ?? '0');
        $discountInput = (string) ($request->input('additional_discount') ?? '0');

        // Guard: already paid
        if (bccomp($outstanding, '0', 2) <= 0) {
            return response()->json(['message' => __('invoices.invoice_already_paid'), 'status' => 0], 422);
        }

        $total = bcadd($amountInput, $discountInput, 2);
        if (bccomp($total, $outstanding, 2) > 0) {
            return response()->json(['message' => __('invoices.overdue_amount_exceeds'), 'status' => 0], 422);
        }

        try {
            $result = $this->invoiceService->addOverduePayment((int) $id, $request->all());
            return response()->json([
                'message' => __('invoices.overdue_payment_success'),
                'status'  => 1,
                'data'    => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => 0], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        return response()->json($this->invoiceService->deleteInvoice((int) $id));
    }

    /**
     * 待折扣审批列表 (PRD 4.1.2 BR-035)
     */
    public function pendingDiscountApprovals(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->invoiceService->getPendingDiscountApprovals();

            return $this->invoiceService->buildDiscountApprovalsDataTable($data);
        }

        return view('invoices.pending_discount_approvals');
    }

    /**
     * 审批折扣 - 批准 (PRD 4.1.2 BR-035)
     */
    public function approveDiscount(Request $request, $id)
    {
        return response()->json(
            $this->invoiceService->approveDiscount((int) $id, Auth::id(), $request->reason)
        );
    }

    /**
     * 审批折扣 - 拒绝 (PRD 4.1.2 BR-035)
     */
    public function rejectDiscount(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'status' => false]);
        }

        return response()->json(
            $this->invoiceService->rejectDiscount((int) $id, Auth::id(), $request->reason)
        );
    }

    /**
     * 设置为挂账 (PRD 4.1.3 欠费挂账)
     */
    public function setCredit(Request $request, $id)
    {
        return response()->json(
            $this->invoiceService->setCredit((int) $id, Auth::id())
        );
    }

    /**
     * 搜索发票 (用于退费页面)
     */
    public function searchInvoices(Request $request)
    {
        return response()->json(
            $this->invoiceService->searchInvoices($request->get('q', ''))
        );
    }

    /**
     * 获取诊疗项目分类树 (划价左侧面板)
     */
    public function getServiceCategories($patientId)
    {
        return response()->json([
            'status' => true,
            'data'   => $this->invoiceService->getServiceCategoryTree(),
        ]);
    }

    /**
     * 创建划价账单
     */
    public function createBilling(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'patient_id'               => 'required|integer|exists:patients,id',
            'items'                    => 'required|array|min:1',
            'items.*.medical_service_id' => 'required|integer',
            'items.*.qty'              => 'required|integer|min:1',
            'items.*.price'            => 'required|numeric|min:0',
            'billing_mode'             => 'in:direct,front_desk',
            'appointment_id'           => 'nullable|integer|exists:appointments,id',
            'round_off'                => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'status' => false]);
        }

        // 诊疗页划价会带 appointment_id，账单才能挂到这次就诊上。
        // 但 exists 只保证这个预约存在，不保证是这个患者的 —— 前端传来的两个 id
        // 各自独立，改一下请求就能把账单挂到别人的就诊记录上。这里必须交叉核对。
        $appointmentId = $request->appointment_id ? (int) $request->appointment_id : null;

        if ($appointmentId) {
            $belongsToPatient = \App\Appointment::where('id', $appointmentId)
                ->where('patient_id', (int) $request->patient_id)
                ->exists();

            if (!$belongsToPatient) {
                return response()->json([
                    'message' => __('invoices.appointment_patient_mismatch'),
                    'status'  => false,
                ], 422);
            }
        }

        // 划价本身只要 create-invoices（控制器中间件已挡），但这个接口顺带能收钱：
        // billing_mode=direct 且带 payments 时会当场登记收款。不在这里判一次的话，
        // 只有开单权限的人（比如医生）能从划价接口绕过 collect-payments 直接收钱，
        // 那条权限就形同虚设。前端隐藏按钮不算防护。
        $collectsMoney = ($request->billing_mode ?? 'direct') === 'direct'
            && !empty($request->payments);

        if ($collectsMoney && !$request->user()->can('collect-payments')) {
            return response()->json([
                'message' => __('invoices.no_permission_to_collect'),
                'status'  => false,
            ], 403);
        }

        $result = $this->invoiceService->createBillingInvoice(
            (int) $request->patient_id,
            $request->items,
            $request->payments ?? [],
            (float) ($request->order_discount_rate ?? 100),
            $request->payment_date,
            $request->billing_mode ?? 'direct',
            $appointmentId,
            (float) ($request->round_off ?? 0)
        );

        return response()->json($result);
    }

    /**
     * 患者收费单列表 (收费单 Tab)
     */
    public function patientReceipts(Request $request, $patient_id)
    {
        if ($request->ajax()) {
            $data = $this->invoiceService->getPatientReceipts((int) $patient_id);

            return $this->invoiceService->buildPatientReceiptsDataTable($data);
        }
    }
}
