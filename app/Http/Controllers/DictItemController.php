<?php

namespace App\Http\Controllers;

use App\DictItem;
use Illuminate\Http\Request;

class DictItemController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-patient-settings');
    }

    /**
     * 字典管理页面
     */
    public function index()
    {
        $types = DictItem::select('type')->distinct()->pluck('type');
        $items = DictItem::ordered()->get();
        $grouped = $items->groupBy('type');

        return view('dict_items.index', compact('types', 'grouped'));
    }

    /**
     * 按类型获取字典项列表（供 Select2 / 侧边栏使用）
     */
    public function list(Request $request)
    {
        $type = $request->input('type');
        if (!$type) {
            return response()->json([]);
        }

        $items = DictItem::ofType($type)->active()->ordered()
            ->get(['id', 'code', 'name'])
            ->map(fn($item) => ['id' => $item->code, 'text' => $item->name]);

        return response()->json($items);
    }

    /**
     * 新增字典项
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string|max:50',
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:100',
        ]);

        $exists = DictItem::where('type', $request->type)
            ->where('code', $request->code)
            ->exists();

        if ($exists) {
            return response()->json(['status' => 0, 'message' => __('common.already_exists')]);
        }

        $maxSort = DictItem::ofType($request->type)->max('sort_order') ?? 0;

        DictItem::create([
            'type' => $request->type,
            'code' => $request->code,
            'name' => $request->name,
            'sort_order' => $maxSort + 1,
        ]);

        return response()->json(['status' => 1, 'message' => __('common.created_successfully')]);
    }

    /**
     * 更新字典项
     */
    public function update(Request $request, $id)
    {
        $item = DictItem::findOrFail($id);

        $item->update($request->only(['name', 'sort_order', 'is_active']));

        return response()->json(['status' => 1, 'message' => __('common.updated_successfully')]);
    }

    /**
     * 删除字典项
     */
    public function destroy($id)
    {
        DictItem::findOrFail($id)->delete();

        return response()->json(['status' => 1, 'message' => __('common.deleted_successfully')]);
    }
}
