<?php

namespace App\Http\Controllers;

use App\Invoice;
use App\Services\RefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use PDF;

/**
 * RefundController
 * PRD 4.1.4: 退费处理
 */
class RefundController extends Controller
{
    private RefundService $refundService;

    public function __construct(RefundService $refundService)
    {
        $this->refundService = $refundService;
        $this->middleware('can:manage-refunds');
    }

    /**
     * 退费列表
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->refundService->getRefundList([
                'search'     => $request->input('search.value', ''),
                'status'     => $request->input('status'),
                'start_date' => $request->input('start_date'),
                'end_date'   => $request->input('end_date'),
                'page'       => $request->input('page'),
                'per_page'   => $request->input('per_page'),
            ]);

            return $this->refundService->buildIndexDataTable($data);
        }

        return view('refunds.index');
    }

    /**
     * 创建退费表单
     */
    public function create(Request $request)
    {
        $invoice = null;
        if ($request->filled('invoice_id')) {
            $invoice = Invoice::with(['patient', 'items.medicalService', 'payments'])
                ->findOrFail($request->invoice_id);
        }
        return view('refunds.create', compact('invoice'));
    }

    /**
     * 保存退费申请
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required|exists:invoices,id',
            'refund_amount' => 'required|numeric|min:0.01',
            'refund_reason' => 'required|string|max:500',
            'refund_method' => 'required|in:cash,wechat,alipay,card,stored_value',
        ], [
            'invoice_id.required' => __('invoices.invoice_required'),
            'refund_amount.required' => __('invoices.refund_amount_required'),
            'refund_amount.min' => __('invoices.refund_amount_min'),
            'refund_reason.required' => __('invoices.refund_reason_required'),
            'refund_method.required' => __('invoices.refund_method_required'),
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'status' => false]);
        }

        return response()->json($this->refundService->createRefund($request->only(['invoice_id', 'refund_amount', 'refund_reason', 'refund_method'])));
    }

    /**
     * 退费详情
     */
    public function show($id)
    {
        $refund = $this->refundService->getRefundDetail((int) $id);
        return view('refunds.show', compact('refund'));
    }

    /**
     * 审批退费 - 批准
     */
    public function approve(Request $request, $id)
    {
        return response()->json($this->refundService->approveRefund((int) $id, Auth::id()));
    }

    /**
     * 审批退费 - 拒绝
     */
    public function reject(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'status' => false]);
        }

        return response()->json(
            $this->refundService->rejectRefund((int) $id, Auth::id(), $request->rejection_reason)
        );
    }

    /**
     * 打印退费单据
     */
    public function print($id)
    {
        $refund = $this->refundService->getRefundForPrint((int) $id);

        $pdf = PDF::loadView('refunds.print', compact('refund'));
        return $pdf->stream('refund_' . $refund->refund_no . '.pdf', ['Attachment' => false]);
    }

    /**
     * 删除退费记录
     */
    public function destroy($id)
    {
        return response()->json($this->refundService->deleteRefund((int) $id));
    }

    /**
     * 待审批退费列表
     */
    public function pendingApprovals(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->refundService->getPendingApprovals();

            return $this->refundService->buildPendingApprovalsDataTable($data);
        }

        return view('refunds.pending_approvals');
    }

    /**
     * 获取发票可退款金额
     */
    public function getRefundableAmount($invoiceId)
    {
        return response()->json($this->refundService->getRefundableAmount((int) $invoiceId));
    }
}
