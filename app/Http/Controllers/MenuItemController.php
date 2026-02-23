<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Permission;
use App\Services\MenuService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MenuItemController extends Controller
{
    private MenuService $menuService;

    public function __construct(MenuService $menuService)
    {
        $this->menuService = $menuService;
        $this->middleware('can:manage-menu-items');
    }

    /**
     * 菜单管理页面
     */
    public function index()
    {
        $permissions = Permission::orderBy('module')->orderBy('name')->get(['id', 'name', 'slug', 'module']);

        // 顶级菜单项（用于父级下拉）
        $topItems = MenuItem::whereNull('parent_id')
            ->orderBy('sort_order')
            ->get(['id', 'title_key']);

        return view('menu_items.index', compact('permissions', 'topItems'));
    }

    /**
     * AJAX: 获取完整菜单树
     */
    public function tree()
    {
        $items = MenuItem::with(['children' => function ($q) {
                $q->orderBy('sort_order')
                  ->with(['children' => function ($q2) {
                      $q2->orderBy('sort_order');
                  }]);
            }])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        $tree = $this->buildTreeData($items);

        return response()->json(['status' => true, 'data' => $tree]);
    }

    /**
     * 新增菜单项
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title_key'     => 'required|string|max:100',
            'url'           => 'nullable|string|max:255',
            'icon'          => 'nullable|string|max:50',
            'parent_id'     => 'nullable|integer|exists:menu_items,id',
            'permission_id' => 'nullable|integer|exists:permissions,id',
            'sort_order'    => 'integer|min:0',
            'is_active'     => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'status' => false]);
        }

        DB::beginTransaction();
        try {
            $item = MenuItem::create([
                'parent_id'     => $request->input('parent_id'),
                'title_key'     => $request->input('title_key'),
                'url'           => $request->input('url') ?: null,
                'icon'          => $request->input('icon') ?: null,
                'permission_id' => $request->input('permission_id'),
                'sort_order'    => $request->input('sort_order', 0),
                'is_active'     => $request->input('is_active', true),
            ]);

            DB::commit();
            $this->menuService->clearAllCache();

            return response()->json([
                'message' => __('menu_items.created'),
                'status'  => true,
                'data'    => $item->toArray(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

    /**
     * 更新菜单项
     */
    public function update(Request $request, $id)
    {
        $item = MenuItem::find($id);
        if (!$item) {
            return response()->json(['message' => __('menu_items.not_found'), 'status' => false]);
        }

        $validator = Validator::make($request->all(), [
            'title_key'     => 'required|string|max:100',
            'url'           => 'nullable|string|max:255',
            'icon'          => 'nullable|string|max:50',
            'parent_id'     => 'nullable|integer|exists:menu_items,id',
            'permission_id' => 'nullable|integer|exists:permissions,id',
            'sort_order'    => 'integer|min:0',
            'is_active'     => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'status' => false]);
        }

        // 防止设为自身的子节点
        if ($request->input('parent_id') == $id) {
            return response()->json(['message' => 'Cannot set item as its own parent', 'status' => false]);
        }

        DB::beginTransaction();
        try {
            $item->update([
                'parent_id'     => $request->input('parent_id'),
                'title_key'     => $request->input('title_key'),
                'url'           => $request->input('url') ?: null,
                'icon'          => $request->input('icon') ?: null,
                'permission_id' => $request->input('permission_id'),
                'sort_order'    => $request->input('sort_order', 0),
                'is_active'     => $request->input('is_active', true),
            ]);

            DB::commit();
            $this->menuService->clearAllCache();

            return response()->json([
                'message' => __('menu_items.updated'),
                'status'  => true,
                'data'    => $item->fresh()->toArray(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

    /**
     * 删除菜单项（级联删除子项）
     */
    public function destroy($id)
    {
        $item = MenuItem::find($id);
        if (!$item) {
            return response()->json(['message' => __('menu_items.not_found'), 'status' => false]);
        }

        DB::beginTransaction();
        try {
            $this->deleteWithChildren($item);
            DB::commit();
            $this->menuService->clearAllCache();

            return response()->json(['message' => __('menu_items.deleted'), 'status' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

    /**
     * 拖拽排序
     */
    public function reorder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items'              => 'required|array',
            'items.*.id'         => 'required|integer|exists:menu_items,id',
            'items.*.sort_order' => 'required|integer|min:0',
            'items.*.parent_id'  => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'status' => false]);
        }

        DB::beginTransaction();
        try {
            foreach ($request->input('items') as $data) {
                MenuItem::where('id', $data['id'])->update([
                    'sort_order' => $data['sort_order'],
                    'parent_id'  => $data['parent_id'] ?? null,
                ]);
            }

            DB::commit();
            $this->menuService->clearAllCache();

            return response()->json(['message' => __('menu_items.reorder_success'), 'status' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

    // ── Private helpers ──────────────────────────────────────────

    /**
     * 递归构建树结构数据（用于前端渲染）
     */
    private function buildTreeData($items): array
    {
        $result = [];
        foreach ($items as $item) {
            $node = [
                'id'            => $item->id,
                'title_key'     => $item->title_key,
                'title'         => __($item->title_key),
                'url'           => $item->url,
                'icon'          => $item->icon,
                'permission_id' => $item->permission_id,
                'parent_id'     => $item->parent_id,
                'sort_order'    => $item->sort_order,
                'is_active'     => $item->is_active,
                'children'      => [],
            ];

            if ($item->children && $item->children->isNotEmpty()) {
                $node['children'] = $this->buildTreeData($item->children);
            }

            $result[] = $node;
        }
        return $result;
    }

    /**
     * 递归删除菜单项及其子项
     */
    private function deleteWithChildren(MenuItem $item): void
    {
        $children = MenuItem::where('parent_id', $item->id)->get();
        foreach ($children as $child) {
            $this->deleteWithChildren($child);
        }
        $item->roles()->detach();
        $item->delete();
    }
}
