<?php

namespace App\Http\Controllers;

use App\Http\Helper\NameHelper;
use App\Invoice;
use App\Services\RefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;
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
    }

    /**
     * 退费列表
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->refundService->getRefundList($request->all());

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('refund_no', function ($row) {
                    return '<a href="' . url('refunds/' . $row->id) . '">' . $row->refund_no . '</a>';
                })
                ->addColumn('patient_name', function ($row) {
                    return $row->patient ? $row->patient->full_name : '-';
                })
                ->addColumn('invoice_no', function ($row) {
                    return $row->invoice ? '<a href="' . url('invoices/' . $row->invoice_id) . '">' . $row->invoice->invoice_no . '</a>' : '-';
                })
                ->addColumn('refund_amount', function ($row) {
                    return number_format($row->refund_amount, 2);
                })
                ->addColumn('refund_date', function ($row) {
                    return $row->refund_date ? $row->refund_date->format('Y-m-d') : '-';
                })
                ->addColumn('status', function ($row) {
                    $statusClasses = [
                        'pending' => 'label-warning',
                        'approved' => 'label-success',
                        'rejected' => 'label-danger',
                    ];
                    $statusLabels = [
                        'pending' => __('invoices.refund_pending'),
                        'approved' => __('invoices.refund_approved'),
                        'rejected' => __('invoices.refund_rejected'),
                    ];
                    $class = $statusClasses[$row->approval_status] ?? 'label-default';
                    $label = $statusLabels[$row->approval_status] ?? $row->approval_status;
                    return '<span class="label ' . $class . '">' . $label . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<div class="btn-group">';
                    $btn .= '<button class="btn blue dropdown-toggle" type="button" data-toggle="dropdown">';
                    $btn .= __('common.action') . ' <i class="fa fa-angle-down"></i></button>';
                    $btn .= '<ul class="dropdown-menu" role="menu">';
                    $btn .= '<li><a href="' . url('refunds/' . $row->id) . '">' . __('common.view') . '</a></li>';

                    if ($row->approval_status === 'pending') {
                        $btn .= '<li><a href="#" onclick="approveRefund(' . $row->id . ')">' . __('invoices.approve_refund') . '</a></li>';
                        $btn .= '<li><a href="#" onclick="rejectRefund(' . $row->id . ')">' . __('invoices.reject_refund') . '</a></li>';
                    }

                    if ($row->approval_status === 'approved') {
                        $btn .= '<li><a href="' . url('refunds/' . $row->id . '/print') . '" target="_blank">' . __('print.print_refund') . '</a></li>';
                    }

                    $btn .= '<li class="divider"></li>';
                    $btn .= '<li><a href="#" onclick="deleteRefund(' . $row->id . ')" class="text-danger">' . __('common.delete') . '</a></li>';
                    $btn .= '</ul></div>';
                    return $btn;
                })
                ->rawColumns(['refund_no', 'invoice_no', 'status', 'action'])
                ->make(true);
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

        return response()->json($this->refundService->createRefund($request->all()));
    }

    /**
     * 退费详情
     */
    public function show($id)
    {
        $refund = $this->refundService->getRefundDetail($id);
        return view('refunds.show', compact('refund'));
    }

    /**
     * 审批退费 - 批准
     */
    public function approve(Request $request, $id)
    {
        return response()->json($this->refundService->approveRefund($id, Auth::id()));
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
            $this->refundService->rejectRefund($id, Auth::id(), $request->rejection_reason)
        );
    }

    /**
     * 打印退费单据
     */
    public function print($id)
    {
        $refund = $this->refundService->getRefundForPrint($id);

        $pdf = PDF::loadView('refunds.print', compact('refund'));
        return $pdf->stream('refund_' . $refund->refund_no . '.pdf', ['Attachment' => false]);
    }

    /**
     * 删除退费记录
     */
    public function destroy($id)
    {
        return response()->json($this->refundService->deleteRefund($id));
    }

    /**
     * 待审批退费列表
     */
    public function pendingApprovals(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->refundService->getPendingApprovals();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('refund_no', function ($row) {
                    return $row->refund_no;
                })
                ->addColumn('patient_name', function ($row) {
                    return $row->patient ? $row->patient->full_name : '-';
                })
                ->addColumn('refund_amount', function ($row) {
                    return number_format($row->refund_amount, 2);
                })
                ->addColumn('refund_reason', function ($row) {
                    return $row->refund_reason;
                })
                ->addColumn('requested_by', function ($row) {
                    return $row->whoAdded ? $row->whoAdded->othername : '-';
                })
                ->addColumn('requested_at', function ($row) {
                    return $row->created_at->format('Y-m-d H:i');
                })
                ->addColumn('action', function ($row) {
                    return '
                        <button class="btn btn-sm btn-success" onclick="approveRefund(' . $row->id . ')">
                            <i class="fa fa-check"></i> ' . __('invoices.approve') . '
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="rejectRefund(' . $row->id . ')">
                            <i class="fa fa-times"></i> ' . __('invoices.reject') . '
                        </button>
                    ';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('refunds.pending_approvals');
    }

    /**
     * 获取发票可退款金额
     */
    public function getRefundableAmount($invoiceId)
    {
        return response()->json($this->refundService->getRefundableAmount($invoiceId));
    }
}
