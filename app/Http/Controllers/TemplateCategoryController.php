<?php

namespace App\Http\Controllers;

use App\Services\TemplateCategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * 病历模板的学科分类树。
 *
 * 权限沿用模板模块既有的两档，不另造 slug（新 slug 要配种子、要进角色配置，
 * 而这棵树本来就是模板的一部分）：
 *   读树   —— edit-patients **或** manage-medical-services
 *   改树   —— manage-medical-services，与模板管理页一致，属设置类操作
 *
 * 读树为什么是「或」而不是单挑一个：这棵树有两类读者，两边的准入本来就不同。
 *   - 医生在病历里按学科找模板，走的是 edit-patients（与模板浮层 search 一致）
 *   - 管理员在模板管理页维护这棵树，走的是 manage-medical-services
 * 只配 edit-patients 的话，只有 manage-medical-services 的模板管理员进得了
 * 页面却拿不到树 —— 表现就是分类管理弹窗点了没反应。这个坑已经踩过一次。
 */
class TemplateCategoryController extends Controller
{
    private TemplateCategoryService $service;

    public function __construct(TemplateCategoryService $service)
    {
        $this->service = $service;

        // can: 中间件只接一个能力，两者取其一得显式判
        $this->middleware(function ($request, $next) {
            if (Gate::denies('edit-patients') && Gate::denies('manage-medical-services')) {
                abort(403);
            }
            return $next($request);
        })->only(['index']);

        $this->middleware('can:manage-medical-services')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        return response()->json([
            'status' => true,
            'data'   => $this->service->tree($request->boolean('only_active')),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:100',
            'parent_id'  => 'nullable|integer|exists:template_categories,id',
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'nullable|boolean',
        ]);

        $result = $this->service->create(
            $request->only(['name', 'parent_id', 'sort_order', 'is_active']),
            Auth::id()
        );

        if (!$result['success']) {
            return response()->json(['status' => false, 'message' => $result['message']], 422);
        }

        return response()->json([
            'status'  => true,
            'message' => __('templates.category_created'),
            'data'    => ['id' => $result['id']],
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name'       => 'sometimes|required|string|max:100',
            'parent_id'  => 'nullable|integer|exists:template_categories,id',
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'nullable|boolean',
        ]);

        $result = $this->service->update(
            (int) $id,
            $request->only(['name', 'parent_id', 'sort_order', 'is_active']),
            Auth::id()
        );

        if (!$result['success']) {
            return response()->json(['status' => false, 'message' => $result['message']], 422);
        }

        return response()->json(['status' => true, 'message' => __('templates.category_updated')]);
    }

    public function destroy($id)
    {
        $result = $this->service->delete((int) $id);

        if (!$result['success']) {
            return response()->json(['status' => false, 'message' => $result['message']], 422);
        }

        return response()->json(['status' => true, 'message' => __('templates.category_deleted')]);
    }
}
