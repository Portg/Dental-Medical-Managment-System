<?php

namespace App\Http\Controllers;

use App\StockOut;
use App\Services\StockOutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

/**
 * 申领单管理 Controller
 *
 * AG-052: pending_approval 不可编辑
 * AG-053: approverId 后端取 Auth::id()，不信任前端
 * AG-054: 单条 item qty 上限由 StockOutService 校验
 */
class RequisitionController extends Controller
{
    private StockOutService $service;

    public function __construct(StockOutService $service)
    {
        $this->service = $service;

        // 申领或管理库存权限均可访问列表/详情
        $this->middleware('can:request-inventory|manage-inventory')->only(['index', 'show']);
        // 仅申领权限（医生）
        $this->middleware('can:request-inventory')->only(['create', 'store', 'update', 'submit', 'clone']);
        // 仅管理库存权限（管理员/库管）
        $this->middleware('can:manage-inventory')->only(['approve', 'reject']);
    }

    /**
     * 申领单列表。
     * 管理员看全部，仅有 request-inventory 的用户只看自己的。
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $user = Auth::user();
            // 拥有 manage-inventory 权限的用户（管理员/库管）可看全部
            $ownerId = $user->can('manage-inventory') ? null : $user->id;

            $data = $this->service->getRequisitionList(
                $request->only(['status']),
                $ownerId
            );

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('items_preview', function ($row) {
                    $names = $row->items->take(3)->map(fn($i) => $i->inventoryItem->name ?? '-');
                    $label = $names->implode('、');
                    if ($row->items->count() > 3) {
                        $label .= ' ...';
                    }
                    return $label ?: '-';
                })
                ->addColumn('total_qty', function ($row) {
                    return $row->items->sum('qty');
                })
                ->addColumn('added_by_name', function ($row) {
                    return $row->addedBy ? $row->addedBy->othername : '-';
                })
                ->addColumn('status_badge', function ($row) {
                    $classMap = [
                        'draft'            => 'badge-secondary',
                        'pending_approval' => 'badge-warning',
                        'confirmed'        => 'badge-success',
                        'rejected'         => 'badge-danger',
                        'cancelled'        => 'badge-default',
                    ];
                    $class = $classMap[$row->status] ?? 'badge-default';
                    $label = \App\DictItem::nameByCode('stock_out_status', $row->status) ?? $row->status;
                    return '<span class="badge ' . $class . '">' . $label . '</span>';
                })
                ->addColumn('actions', function ($row) use ($request) {
                    $user   = Auth::user();
                    $isOwner = $row->_who_added == $user->id;
                    $canManage = $user->can('manage-inventory');
                    $html = '<a href="' . url('requisitions/' . $row->id) . '" class="btn btn-info btn-xs">'
                          . __('common.view') . '</a> ';

                    if ($row->isDraft() && $isOwner) {
                        $html .= '<a href="' . url('requisitions/' . $row->id . '/edit') . '" class="btn btn-primary btn-xs">'
                              . __('common.edit') . '</a> ';
                        $html .= '<button onclick="submitRequisition(' . $row->id . ')" class="btn btn-warning btn-xs">'
                              . __('inventory.submit_for_approval') . '</button> ';
                        $html .= '<button onclick="deleteRequisition(' . $row->id . ')" class="btn btn-danger btn-xs">'
                              . __('common.delete') . '</button>';
                    } elseif ($row->isPendingApproval()) {
                        if ($canManage) {
                            $html .= '<button onclick="approveRequisition(' . $row->id . ')" class="btn btn-success btn-xs">'
                                  . __('inventory.approve') . '</button> ';
                            $html .= '<button onclick="rejectRequisition(' . $row->id . ')" class="btn btn-danger btn-xs">'
                                  . __('inventory.reject') . '</button>';
                        }
                    } elseif ($row->isRejected() && $isOwner) {
                        $html .= '<button onclick="cloneRequisition(' . $row->id . ')" class="btn btn-default btn-xs">'
                              . __('inventory.reapply') . '</button>';
                    }

                    return $html;
                })
                ->rawColumns(['status_badge', 'actions'])
                ->make(true);
        }

        $pendingCount = StockOut::where('out_type', StockOut::OUT_TYPE_REQUISITION)
            ->where('status', StockOut::STATUS_PENDING_APPROVAL)
            ->count();

        return view('inventory.requisitions.index', compact('pendingCount'));
    }

    /**
     * 创建申领单表单。
     */
    public function create()
    {
        $stockOutNo = StockOut::generateStockOutNo();
        $user       = Auth::user();
        return view('inventory.requisitions.create', compact('stockOutNo', 'user'));
    }

    /**
     * 保存申领单草稿。
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'stock_out_date'         => 'required|date',
            'items'                  => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|integer|exists:inventory_items,id',
            'items.*.qty'            => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $result = $this->service->createRequisition(
            $request->only(['stock_out_date', 'recipient', 'department', 'notes', 'branch_id', 'items']),
            Auth::id()
        );

        if ($result['status']) {
            return response()->json([
                'status'   => true,
                'message'  => __('inventory.stock_out_created_successfully'),
                'redirect' => url('requisitions/' . $result['stock_out']->id),
            ]);
        }

        return response()->json(['status' => false, 'message' => $result['message']]);
    }

    /**
     * 申领单详情页。
     */
    public function show(int $id)
    {
        $stockOut = $this->service->getRequisitionDetail($id);
        if (!$stockOut) {
            abort(404);
        }

        // 医生只能看自己的
        $user = Auth::user();
        if (!$user->can('manage-inventory') && $stockOut->_who_added != $user->id) {
            abort(403);
        }

        return view('inventory.requisitions.show', compact('stockOut'));
    }

    /**
     * 编辑申领单表单（AG-052：仅 draft 可编辑）。
     */
    public function edit(int $id)
    {
        $stockOut = $this->service->getRequisitionDetail($id);
        if (!$stockOut || !$stockOut->isDraft()) {
            abort(404);
        }

        $user = Auth::user();
        if ($stockOut->_who_added != $user->id) {
            abort(403);
        }

        return view('inventory.requisitions.create', compact('stockOut', 'user'));
    }

    /**
     * 更新申领单草稿。
     */
    public function update(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'stock_out_date'            => 'required|date',
            'items'                     => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|integer|exists:inventory_items,id',
            'items.*.qty'               => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $result = $this->service->updateRequisition(
            $id,
            $request->only(['stock_out_date', 'recipient', 'department', 'notes', 'branch_id', 'items']),
            Auth::id()
        );

        return response()->json(['status' => $result['status'], 'message' => $result['message']]);
    }

    /**
     * 提交申领单（draft → pending_approval）。
     * AG-052: 提交后不可再编辑。
     */
    public function submit(int $id)
    {
        $result = $this->service->submitRequisition($id, Auth::id());
        return response()->json(['status' => $result['status'], 'message' => $result['message']]);
    }

    /**
     * 审批通过。
     * AG-053: approverId 从 Auth::id() 取，不信任前端。
     */
    public function approve(int $id)
    {
        $result = $this->service->approveRequisition($id, Auth::id());
        return response()->json(['status' => $result['status'], 'message' => $result['message']]);
    }

    /**
     * 驳回申领单。
     * AG-053: approverId 从 Auth::id() 取。
     */
    public function reject(Request $request, int $id)
    {
        $result = $this->service->rejectRequisition(
            $id,
            Auth::id(),
            $request->input('rejection_reason')
        );
        return response()->json(['status' => $result['status'], 'message' => $result['message']]);
    }

    /**
     * 重新申请（复制 rejected 为新 draft，重定向到新单详情页）。
     */
    public function clone(int $id)
    {
        $result = $this->service->cloneRequisition($id, Auth::id());

        if ($result['status']) {
            return response()->json([
                'status'   => true,
                'message'  => $result['message'],
                'redirect' => url('requisitions/' . $result['stock_out']->id . '/edit'),
            ]);
        }

        return response()->json(['status' => false, 'message' => $result['message']]);
    }

    /**
     * 删除草稿申领单。
     */
    public function destroy(int $id)
    {
        $stockOut = StockOut::where('out_type', StockOut::OUT_TYPE_REQUISITION)->find($id);
        if (!$stockOut || !$stockOut->isDraft()) {
            return response()->json(['status' => false, 'message' => __('inventory.cannot_delete_confirmed')]);
        }

        $user = Auth::user();
        if ($stockOut->_who_added != $user->id) {
            return response()->json(['status' => false, 'message' => __('messages.unauthorized')]);
        }

        $stockOut->delete();
        return response()->json(['status' => true, 'message' => __('inventory.stock_out_deleted_successfully')]);
    }
}
