<?php

namespace App\Http\Controllers;

use App\InventoryCategory;
use App\InventoryCheck;
use App\Services\InventoryCheckService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

/**
 * 盘点单管理 Controller（Week 4 Phase 7）
 *
 * AG-058：创建前 Service 查 draft 状态的同分类同日期记录，存在则拒绝。
 * AG-059：system_qty 快照于创建时，之后出入库不影响。
 * AG-060：偏差超阈值时返回 needs_confirm，前端二次确认后再传 force_confirm=1。
 * AG-067：confirmCheck() 后端重新计算偏差率，不信任前端传入的任何数值。
 */
class InventoryCheckController extends Controller
{
    private InventoryCheckService $service;

    public function __construct(InventoryCheckService $service)
    {
        $this->service = $service;

        // manage-inventory 或 operate-inventory 均可访问（OR 逻辑通过闭包实现）
        $this->middleware(function ($req, $next) {
            $user = auth()->user();
            if (!$user->can('manage-inventory') && !$user->can('operate-inventory')) {
                abort(403);
            }
            return $next($req);
        });
    }

    /**
     * 盘点单列表页。
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->datatableData($request);
        }

        $categories = InventoryCategory::orderBy('name')->get();
        return view('inventory.checks.index', compact('categories'));
    }

    /**
     * DataTable JSON 数据。
     */
    public function datatableData(Request $request)
    {
        $user      = Auth::user();
        $manageAll = $user->can('manage-inventory');

        $data = $this->service->getChecksDataTable($request, $manageAll, $user->id);

        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('category_name', fn($row) => $row->category ? $row->category->name : '-')
            ->addColumn('items_count', fn($row) => $row->items->count())
            ->addColumn('added_by_name', fn($row) => $row->addedBy ? $row->addedBy->othername : '-')
            ->addColumn('status_badge', function ($row) {
                $classMap = [
                    'draft'     => 'badge-secondary',
                    'confirmed' => 'badge-success',
                ];
                $class = $classMap[$row->status] ?? 'badge-default';
                return '<span class="badge ' . $class . '">' . $row->status_label . '</span>';
            })
            ->addColumn('actions', function ($row) {
                $html = '<a href="' . url('inventory-checks/' . $row->id) . '" class="btn btn-info btn-xs">'
                      . __('common.view') . '</a> ';

                if ($row->isDraft()) {
                    $html .= '<button onclick="deleteCheck(' . $row->id . ')" class="btn btn-danger btn-xs">'
                           . __('common.delete') . '</button>';
                }

                return $html;
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    /**
     * 创建表单。
     */
    public function create()
    {
        $categories = InventoryCategory::orderBy('name')->get();
        return view('inventory.checks.create', compact('categories'));
    }

    /**
     * 保存新盘点单（调用 createCheck()，跳转到 show 页）。
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|integer|exists:inventory_categories,id',
            'check_date'  => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $result = $this->service->createCheck(
            (int) $request->input('category_id'),
            $request->input('check_date'),
            Auth::id(),
            (string) $request->input('notes', '')
        );

        if ($result['status']) {
            return response()->json([
                'status'   => true,
                'message'  => __('inventory.check_created_successfully'),
                'redirect' => url('inventory-checks/' . $result['check']->id),
            ]);
        }

        return response()->json(['status' => false, 'message' => $result['message']]);
    }

    /**
     * 盘点单详情 + 录入 actual_qty。
     */
    public function show(int $id)
    {
        $check = $this->service->getCheckDetail($id);
        if (!$check) {
            abort(404);
        }

        return view('inventory.checks.show', compact('check'));
    }

    /**
     * AJAX：批量更新 actual_qty。
     */
    public function updateQty(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'items'              => 'required|array',
            'items.*.id'         => 'required|integer',
            'items.*.actual_qty' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $result = $this->service->updateActualQty($id, $request->input('items'), Auth::id());

        return response()->json(['status' => $result['status'], 'message' => $result['message']]);
    }

    /**
     * 确认盘点单。
     * AG-060：若偏差超阈值且 force_confirm != 1，返回 needs_confirm=true + 偏差物品列表。
     * AG-067：后端重新计算偏差，不信任前端数值。
     */
    public function confirm(Request $request, int $id)
    {
        $forceConfirm = (bool) $request->input('force_confirm', 0);

        $result = $this->service->confirmCheck($id, Auth::id(), $forceConfirm);

        return response()->json($result);
    }

    /**
     * 删除草稿盘点单。
     */
    public function destroy(int $id)
    {
        $check = InventoryCheck::find($id);
        if (!$check || !$check->isDraft()) {
            return response()->json([
                'status'  => false,
                'message' => __('inventory.cannot_delete_confirmed'),
            ]);
        }

        $check->items()->delete();
        $check->delete();

        return response()->json([
            'status'  => true,
            'message' => __('inventory.check_deleted_successfully'),
        ]);
    }
}
