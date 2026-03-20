<?php

namespace App\Http\Controllers;

use App\Services\StockOutService;
use App\StockOut;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class StockOutController extends Controller
{
    private StockOutService $service;

    public function __construct(StockOutService $service)
    {
        $this->service = $service;
        // 操作库存权限可以提交报损/退货审批
        $this->middleware('can:operate-inventory|manage-inventory')->only(['submitApproval']);
        // 管理库存权限才可审批通过/驳回
        $this->middleware('can:manage-inventory')->except(['submitApproval']);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->service->getStockOutList($request->only(['status', 'out_type', 'start_date', 'end_date']));

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('out_type_label', function ($row) {
                    return $row->out_type_label;
                })
                ->addColumn('patient_name', function ($row) {
                    return $row->patient ? $row->patient->fullname : '-';
                })
                ->addColumn('added_by', function ($row) {
                    return $row->addedBy ? $row->addedBy->othername : '-';
                })
                ->addColumn('items_count', function ($row) {
                    return $row->items()->count();
                })
                ->addColumn('status_label', function ($row) {
                    $classes = [
                        'draft'            => 'badge-secondary',
                        'confirmed'        => 'badge-success',
                        'cancelled'        => 'badge-danger',
                        'pending_approval' => 'badge-warning',
                        'rejected'         => 'badge-danger',
                    ];
                    $class = $classes[$row->status] ?? 'badge-default';
                    $label = \App\DictItem::nameByCode('stock_out_status', $row->status) ?? $row->status;
                    $html = '<span class="badge ' . $class . '">' . $label . '</span>';
                    if ($row->invoice_id) {
                        $html .= ' <span class="badge badge-info">' . __('inventory.auto_billing') . '</span>';
                    }
                    if ($row->stock_insufficient) {
                        $html .= ' <span class="badge badge-warning">' . __('inventory.stock_insufficient_badge') . '</span>';
                    }
                    return $html;
                })
                ->addColumn('viewBtn', function ($row) {
                    return '<a href="' . route('stock-outs.show', $row->id) . '" class="btn btn-info btn-sm">' . __('common.view') . '</a>';
                })
                ->addColumn('editBtn', function ($row) {
                    if ($row->isDraft()) {
                        return '<a href="' . route('stock-outs.edit', $row->id) . '" class="btn btn-primary btn-sm">' . __('common.edit') . '</a>';
                    }
                    return '';
                })
                ->addColumn('deleteBtn', function ($row) {
                    if ($row->isDraft()) {
                        return '<a href="#" onclick="deleteRecord(' . $row->id . ')" class="btn btn-danger btn-sm">' . __('common.delete') . '</a>';
                    }
                    return '';
                })
                ->addColumn('approvalBtn', function ($row) {
                    $isApprovalType = in_array($row->out_type, [
                        \App\StockOut::OUT_TYPE_DAMAGE,
                        \App\StockOut::OUT_TYPE_SUPPLIER_RETURN,
                    ]);
                    if (!$isApprovalType) {
                        return '';
                    }
                    $user = Auth::user();
                    $html = '';
                    if ($row->isDraft() && $user->can('operate-inventory')) {
                        $html .= '<button onclick="submitDamageOrReturn(' . $row->id . ')" class="btn btn-warning btn-sm">'
                              . __('inventory.submit_approval') . '</button>';
                    } elseif ($row->isPendingApproval() && $user->can('manage-inventory')) {
                        $html .= '<button onclick="approveDamageOrReturn(' . $row->id . ')" class="btn btn-success btn-sm">'
                              . __('inventory.approve') . '</button> ';
                        $html .= '<button onclick="rejectDamageOrReturn(' . $row->id . ')" class="btn btn-danger btn-sm">'
                              . __('inventory.reject') . '</button>';
                    }
                    return $html;
                })
                ->rawColumns(['status_label', 'viewBtn', 'editBtn', 'deleteBtn', 'approvalBtn'])
                ->make(true);
        }

        $pendingCount = \App\StockOut::where('status', \App\StockOut::STATUS_PENDING_APPROVAL)->count();
        return view('inventory.stock_outs.index', compact('pendingCount'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data = $this->service->getCreateFormData();
        return view('inventory.stock_outs.create')->with($data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'stock_out_date' => 'required|date',
            'out_type' => 'required|in:' . \App\DictItem::listByType('stock_out_type')->pluck('code')->implode(','),
        ], [
            'stock_out_date.required' => __('inventory.stock_out_date_required'),
            'out_type.required' => __('inventory.out_type_required'),
        ])->validate();

        $stockOut = $this->service->createStockOut($request->only([
            'stock_out_date', 'out_type', 'patient_id', 'appointment_id',
            'department', 'notes', 'branch_id',
        ]));

        if ($stockOut) {
            return response()->json([
                'message' => __('inventory.stock_out_created_successfully'),
                'status' => true,
                'redirect' => route('stock-outs.edit', $stockOut->id)
            ]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $data['stockOut'] = $this->service->getStockOutDetail((int) $id);
        if (!$data['stockOut']) {
            abort(404);
        }
        return view('inventory.stock_outs.show')->with($data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $data = $this->service->getStockOutForEdit((int) $id);
        if (!$data) {
            abort(404);
        }
        return view('inventory.stock_outs.create')->with($data);
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
        Validator::make($request->all(), [
            'stock_out_date' => 'required|date',
            'out_type' => 'required|in:' . \App\DictItem::listByType('stock_out_type')->pluck('code')->implode(','),
        ])->validate();

        $result = $this->service->updateStockOut((int) $id, $request->only([
            'stock_out_date', 'out_type', 'patient_id', 'appointment_id',
            'department', 'notes', 'branch_id',
        ]));
        return response()->json(['message' => $result['message'], 'status' => $result['status']]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $result = $this->service->deleteStockOut((int) $id);
        return response()->json(['message' => $result['message'], 'status' => $result['status']]);
    }

    /**
     * Confirm the stock out and update inventory.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function confirm($id)
    {
        $result = $this->service->confirmStockOut((int) $id);
        return response()->json(['message' => $result['message'], 'status' => $result['status']]);
    }

    /**
     * Cancel the stock out.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function cancel($id)
    {
        $result = $this->service->cancelStockOut((int) $id);
        return response()->json(['message' => $result['message'], 'status' => $result['status']]);
    }

    /**
     * 提交报损/退货单进入审批（draft → pending_approval）。
     * 权限：operate-inventory 或 manage-inventory（由构造函数中间件控制）
     *
     * AG-055: 报损/退货必须经过审批流程
     */
    public function submitApproval(int $id): \Illuminate\Http\JsonResponse
    {
        $result = $this->service->submitDamageOrReturn($id, Auth::id());
        return response()->json(['status' => $result['status'], 'message' => $result['message']]);
    }

    /**
     * 审批通过报损/退货。
     * AG-053: approverId 从 Auth::id() 取，后端校验 != _who_added
     * 权限：manage-inventory（由构造函数中间件控制）
     */
    public function approveStockOut(int $id): \Illuminate\Http\JsonResponse
    {
        $result = $this->service->approveDamageOrReturn($id, Auth::id());
        return response()->json(['status' => $result['status'], 'message' => $result['message']]);
    }

    /**
     * 驳回报损/退货。
     * AG-053: approverId 从 Auth::id() 取
     * 权限：manage-inventory（由构造函数中间件控制）
     */
    public function rejectStockOut(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $result = $this->service->rejectDamageOrReturn(
            $id,
            Auth::id(),
            $request->input('rejection_reason')
        );
        return response()->json(['status' => $result['status'], 'message' => $result['message']]);
    }
}
