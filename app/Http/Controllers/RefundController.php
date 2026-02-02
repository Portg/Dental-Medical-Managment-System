<?php

namespace App\Http\Controllers;

use App\Http\Helper\NameHelper;
use App\Invoice;
use App\InvoicePayment;
use App\Patient;
use App\Refund;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;
use PDF;

/**
 * RefundController
 * PRD 4.1.4: 退费处理
 */
class RefundController extends Controller
{
    // 退费审批阈值 (BR-037, BR-038)
    const REFUND_APPROVAL_THRESHOLD = 100;

    /**
     * 退费列表
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Refund::with(['invoice', 'patient', 'approvedBy', 'whoAdded'])
                ->whereNull('deleted_at');

            // 日期范围筛选
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween('refund_date', [$request->start_date, $request->end_date]);
            }

            // 状态筛选
            if ($request->filled('status')) {
                $query->where('approval_status', $request->status);
            }

            // 搜索
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('refund_no', 'like', "%{$search}%")
                        ->orWhereHas('patient', function ($pq) use ($search) {
                            NameHelper::addNameSearch($pq, $search);
                        })
                        ->orWhereHas('invoice', function ($iq) use ($search) {
                            $iq->where('invoice_no', 'like', "%{$search}%");
                        });
                });
            }

            $data = $query->orderBy('id', 'desc')->get();

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

                    // 审批操作 (仅待审批状态)
                    if ($row->approval_status === 'pending') {
                        $btn .= '<li><a href="#" onclick="approveRefund(' . $row->id . ')">' . __('invoices.approve_refund') . '</a></li>';
                        $btn .= '<li><a href="#" onclick="rejectRefund(' . $row->id . ')">' . __('invoices.reject_refund') . '</a></li>';
                    }

                    // 打印 (仅已审批)
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
     * PRD: BR-037, BR-038, BR-039, BR-040, BR-041
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
            return response()->json([
                'message' => $validator->errors()->first(),
                'status' => false
            ]);
        }

        $invoice = Invoice::findOrFail($request->invoice_id);

        // BR-040: 检查是否已有退款记录
        $existingRefund = Refund::where('invoice_id', $request->invoice_id)
            ->whereIn('approval_status', ['pending', 'approved'])
            ->first();
        if ($existingRefund) {
            return response()->json([
                'message' => __('invoices.refund_already_exists'),
                'status' => false
            ]);
        }

        // 检查退款金额不能超过已付金额
        $maxRefundable = $invoice->paid_amount - ($invoice->total_refunded ?? 0);
        if ($request->refund_amount > $maxRefundable) {
            return response()->json([
                'message' => __('invoices.refund_exceeds_paid', ['max' => number_format($maxRefundable, 2)]),
                'status' => false
            ]);
        }

        DB::beginTransaction();
        try {
            // 确定审批状态 (BR-037, BR-038)
            $approvalStatus = 'pending';
            $approvedBy = null;
            $approvedAt = null;

            // BR-037: ≤100元前台可直接操作
            if ($request->refund_amount <= self::REFUND_APPROVAL_THRESHOLD) {
                $approvalStatus = 'approved';
                $approvedBy = Auth::id();
                $approvedAt = now();
            }

            $refund = Refund::create([
                'refund_no' => Refund::generateRefundNo(),
                'invoice_id' => $request->invoice_id,
                'patient_id' => $invoice->patient_id ?? ($invoice->appointment ? $invoice->appointment->patient_id : null),
                'refund_amount' => $request->refund_amount,
                'refund_reason' => $request->refund_reason,
                'refund_date' => now(),
                'refund_method' => $request->refund_method,
                'approval_status' => $approvalStatus,
                'approved_by' => $approvedBy,
                'approved_at' => $approvedAt,
                'branch_id' => Auth::user()->branch_id ?? null,
                '_who_added' => Auth::id(),
            ]);

            // 如果自动批准，执行退款逻辑
            if ($approvalStatus === 'approved') {
                $this->executeRefund($refund, $invoice);
            }

            DB::commit();

            $message = $approvalStatus === 'approved'
                ? __('invoices.refund_processed_successfully')
                : __('invoices.refund_pending_approval');

            return response()->json([
                'message' => $message,
                'status' => true,
                'refund_id' => $refund->id,
                'needs_approval' => $approvalStatus === 'pending'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => __('messages.error_occurred') . ': ' . $e->getMessage(),
                'status' => false
            ]);
        }
    }

    /**
     * 退费详情
     */
    public function show($id)
    {
        $refund = Refund::with(['invoice.items.medicalService', 'invoice.payments', 'patient', 'approvedBy', 'whoAdded'])
            ->findOrFail($id);
        return view('refunds.show', compact('refund'));
    }

    /**
     * 审批退费 - 批准
     * PRD: BR-038
     */
    public function approve(Request $request, $id)
    {
        $refund = Refund::findOrFail($id);

        if ($refund->approval_status !== 'pending') {
            return response()->json([
                'message' => __('invoices.refund_not_pending'),
                'status' => false
            ]);
        }

        DB::beginTransaction();
        try {
            $refund->approval_status = 'approved';
            $refund->approved_by = Auth::id();
            $refund->approved_at = now();
            $refund->save();

            // 执行退款逻辑
            $invoice = Invoice::findOrFail($refund->invoice_id);
            $this->executeRefund($refund, $invoice);

            DB::commit();

            return response()->json([
                'message' => __('invoices.refund_approved_successfully'),
                'status' => true
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => __('messages.error_occurred') . ': ' . $e->getMessage(),
                'status' => false
            ]);
        }
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
            return response()->json([
                'message' => $validator->errors()->first(),
                'status' => false
            ]);
        }

        $refund = Refund::findOrFail($id);

        if ($refund->approval_status !== 'pending') {
            return response()->json([
                'message' => __('invoices.refund_not_pending'),
                'status' => false
            ]);
        }

        $refund->approval_status = 'rejected';
        $refund->approved_by = Auth::id();
        $refund->approved_at = now();
        $refund->rejection_reason = $request->rejection_reason;
        $refund->save();

        return response()->json([
            'message' => __('invoices.refund_rejected_successfully'),
            'status' => true
        ]);
    }

    /**
     * 执行退款逻辑
     * PRD: BR-041 - 储值卡退费退回储值余额，现金支付退现金
     */
    private function executeRefund(Refund $refund, Invoice $invoice)
    {
        // 更新发票已付金额
        $invoice->paid_amount = max(0, $invoice->paid_amount - $refund->refund_amount);
        $invoice->save();

        // BR-041: 储值卡退费退回储值余额
        if ($refund->refund_method === 'stored_value') {
            $patient = Patient::find($refund->patient_id);
            if ($patient && isset($patient->stored_balance)) {
                $patient->stored_balance = ($patient->stored_balance ?? 0) + $refund->refund_amount;
                $patient->save();

                // 记录会员交易
                if (class_exists('\App\MemberTransaction')) {
                    \App\MemberTransaction::create([
                        'patient_id' => $patient->id,
                        'type' => 'refund',
                        'amount' => $refund->refund_amount,
                        'balance_after' => $patient->stored_balance,
                        'description' => __('invoices.refund_to_stored_value', ['refund_no' => $refund->refund_no]),
                        '_who_added' => Auth::id(),
                    ]);
                }
            }
        }
    }

    /**
     * 打印退费单据
     */
    public function print($id)
    {
        $refund = Refund::with(['invoice', 'patient', 'approvedBy', 'whoAdded', 'branch'])
            ->findOrFail($id);

        if ($refund->approval_status !== 'approved') {
            abort(403, __('invoices.refund_not_approved_for_print'));
        }

        $pdf = PDF::loadView('refunds.print', compact('refund'));
        return $pdf->stream('refund_' . $refund->refund_no . '.pdf', ['Attachment' => false]);
    }

    /**
     * 删除退费记录
     */
    public function destroy($id)
    {
        $refund = Refund::findOrFail($id);

        // 只有待审批状态可以删除
        if ($refund->approval_status === 'approved') {
            return response()->json([
                'message' => __('invoices.cannot_delete_approved_refund'),
                'status' => false
            ]);
        }

        $refund->delete();

        return response()->json([
            'message' => __('invoices.refund_deleted_successfully'),
            'status' => true
        ]);
    }

    /**
     * 待审批退费列表
     */
    public function pendingApprovals(Request $request)
    {
        if ($request->ajax()) {
            $data = Refund::with(['invoice', 'patient', 'whoAdded'])
                ->pending()
                ->orderBy('created_at', 'asc')
                ->get();

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
        $invoice = Invoice::findOrFail($invoiceId);
        $maxRefundable = $invoice->paid_amount - ($invoice->total_refunded ?? 0);

        return response()->json([
            'invoice_no' => $invoice->invoice_no,
            'paid_amount' => $invoice->paid_amount,
            'refunded_amount' => $invoice->total_refunded ?? 0,
            'max_refundable' => max(0, $maxRefundable),
        ]);
    }
}