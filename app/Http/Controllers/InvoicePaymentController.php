<?php

namespace App\Http\Controllers;

use App\Services\InvoicePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class InvoicePaymentController extends Controller
{
    private InvoicePaymentService $invoicePaymentService;

    public function __construct(InvoicePaymentService $invoicePaymentService)
    {
        $this->invoicePaymentService = $invoicePaymentService;

        // 收款按「读 / 登记 / 改删」三档分流，不再一律要求 edit-invoices。
        //
        // edit-invoices 同时守着折扣审批、设置挂账、编辑账单与删除收款记录，
        // 属于财务控制点；而登记收款是前台柜台的日常作业。前台持有 view-invoices
        // 与 create-invoices、不持有 edit-invoices，此前连自己刚开的账单收了多少钱
        // 都看不到，弹窗一律 403——收款的人反而是唯一收不了款的人。
        // 直接给前台发 edit-invoices 会顺带给出折扣审批权，故改为在此分流。
        //
        // 医生持有 view-invoices，据此可查看收款记录，但不能登记、修改或删除。
        $this->middleware('can:view-invoices')->only(['index', 'show', 'create', 'getPaymentMethods', 'calculateChange']);
        // 收款走 collect-payments，与开单（create-invoices）分开。
        // 拆分理由见 2026_08_17_100000_split_collect_payments_permission 迁移：
        // 一条权限管两件事，「医生划价、前台收费」这种行业标准分工就没法表达。
        $this->middleware('can:collect-payments')->only(['store', 'storeMixed']);
        $this->middleware('can:edit-invoices')->only(['edit', 'update', 'destroy']);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param $invoice_id
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function index(Request $request, $invoice_id)
    {
        if ($request->ajax()) {

            $data = $this->invoicePaymentService->getPaymentsByInvoice((int) $invoice_id);
            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('amount', function ($row) {
                    return number_format($row->amount);
                })
                ->addColumn('added_by', function ($row) {
                    // 同 InvoiceItemController：录入人可能已被删除，null 会让整张收据表卡住
                    return $row->addedBy
                        ? \App\Http\Helper\NameHelper::join($row->addedBy->surname, $row->addedBy->othername)
                        : '-';
                })
                ->addColumn('editBtn', function ($row) {
                    $btn = '<a href="#" onclick="edit_Payment(' . $row->id . ')" class="btn btn-primary">' . __('common.edit') . '</a>';
                    return $btn;
                })
                ->addColumn('deleteBtn', function ($row) {
                    $btn = '<a href="#" onclick="delete_payment(' . $row->id . ')" class="btn btn-danger">' . __('common.delete') . '</a>';
                    return $btn;
                })
                ->rawColumns(['editBtn', 'deleteBtn'])
                ->make(true);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // 登记收款此前只校验「有没有填」，比同一控制器里的 update() 宽得多：
        // amount 可以是 -500 或 "abc"（落库变 0），payment_method 可以是任意字符串
        // （收据打印页认不出就原样印出去），invoice_id 指到不存在的账单只会在
        // 服务层炸成 500。四个字段一起补齐，与 update() 的规则对齐。
        // 登记收款此前只校验「有没有填」，比同一控制器里的 update() 宽得多：
        // amount 可以是 -500 或 "abc"（落库变 0），payment_method 可以是任意字符串
        // （收据打印页认不出就原样印出去），invoice_id 指到不存在的账单只会在
        // 服务层炸成 500。四个字段一起补齐，与 update() 的规则对齐。
        //
        // 支票号 / 银行 / 保险公司 / 往来账户这四项弹窗一直在提交
        // （resources/views/invoices/payment/create.blade.php），但既没校验、
        // 也没往服务层传 —— createPayment() 里那几个 `?? null` 于是永远取到 null。
        // 结果是支票、保险、自有账户三种收款都能保存成功，单据上却查不到是哪张
        // 支票、哪家保司，对账时无从追溯。规则与 update() 保持一致。
        Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'invoice_id' => 'required|integer|exists:invoices,id',
        ] + InvoicePaymentService::detailRules())->validate();

        try {
            $status = $this->invoicePaymentService->createPayment($request->only([
                'amount', 'payment_date', 'payment_method', 'invoice_id',
                'cheque_no', 'bank_name', 'account_name',
                'insurance_company_id', 'self_account_id',
            ]));
        } catch (\RuntimeException $e) {
            // 超收 / 折扣待审批：把真实原因给到柜台，别一律「稍后再试」
            return response()->json(['message' => $e->getMessage(), 'status' => false], 422);
        }

        if ($status) {
            return response()->json(['message' => __('messages.payment_recorded_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\InvoicePayment $invoicePayment
     * @return \Illuminate\Http\Response
     */
    public function show(\App\InvoicePayment $invoicePayment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        return response()->json($this->invoicePaymentService->getPaymentForEdit((int) $id));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $payment = \App\InvoicePayment::find($id);
        if (!$payment) {
            return response()->json(['message' => __('messages.record_not_found'), 'status' => false], 404);
        }

        $validator = Validator::make($request->all(), [
            'amount'       => 'nullable|numeric|min:0',
            'payment_date' => 'nullable|date',
        ] + InvoicePaymentService::detailRules());

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'status' => false], 422);
        }

        // Preserve existing amount and date unless request explicitly provides them
        $data = array_merge(
            [
                'amount'       => $payment->amount,
                'payment_date' => $payment->payment_date,
            ],
            $request->only([
                'payment_method', 'amount', 'payment_date',
                'cheque_no', 'bank_name', 'account_name',
                'insurance_company_id', 'self_account_id',
            ])
        );

        try {
            $status = $this->invoicePaymentService->updatePayment((int) $id, $data);
        } catch (\RuntimeException $e) {
            // 超收 / 折扣待审批 / 储值收款不可就地改
            return response()->json(['message' => $e->getMessage(), 'status' => false], 422);
        }

        if ($status) {
            return response()->json(['message' => __('invoices.payment_method_updated'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $status = $this->invoicePaymentService->deletePayment((int) $id);
        } catch (\RuntimeException $e) {
            // 账单挂着退费时不许撤销收款 —— 得给出原因，不能让它冒成 500
            return response()->json(['message' => $e->getMessage(), 'status' => false], 422);
        }

        if ($status) {
            return response()->json(['message' => __('messages.payment_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);

    }

    /**
     * 混合支付 - 支持多种支付方式组合
     * PRD 4.1.3: 混合支付
     */
    public function storeMixed(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required|exists:invoices,id',
            'payments' => 'required|array|min:1',
            'payments.*.amount' => 'required|numeric|min:0.01',
        ] + InvoicePaymentService::detailRules('payments.*.'));

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'status' => false
            ]);
        }

        $result = $this->invoicePaymentService->processMixedPayment(
            (int) $request->invoice_id,
            $request->payments,
            $request->payment_date
        );

        return response()->json($result);
    }

    /**
     * 获取支付方式列表
     */
    public function getPaymentMethods()
    {
        return response()->json($this->invoicePaymentService->getPaymentMethodsList());
    }

    /**
     * 计算找零金额
     */
    public function calculateChange(Request $request)
    {
        $receivedAmount = floatval($request->received_amount ?? 0);
        return response()->json(
            $this->invoicePaymentService->calculateChange((int) $request->invoice_id, $receivedAmount)
        );
    }
}
