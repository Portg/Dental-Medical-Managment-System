<?php

namespace App\Http\Controllers;

use App\Services\ServiceCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ServiceCategoryController extends Controller
{
    public function __construct(private ServiceCategoryService $service)
    {
        $this->middleware('can:manage-service-categories');
    }

    public function index(): JsonResponse
    {
        return response()->json(['status' => 1, 'data' => $this->service->getAll()]);
    }

    public function store(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'name' => 'required|string|max:50|unique:service_categories,name',
        ]);
        if ($v->fails()) {
            return response()->json(['status' => 0, 'message' => $v->errors()->first()]);
        }
        $this->service->create($request->only(['name', 'sort_order', 'is_active']));
        return response()->json(['status' => 1, 'message' => __('common.created_successfully')]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'name' => "required|string|max:50|unique:service_categories,name,{$id}",
        ]);
        if ($v->fails()) {
            return response()->json(['status' => 0, 'message' => $v->errors()->first()]);
        }
        $this->service->update($id, $request->only(['name', 'sort_order', 'is_active']));
        return response()->json(['status' => 1, 'message' => __('common.updated_successfully')]);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->delete($id);
        if (!$result) {
            return response()->json(['status' => 0, 'message' => __('messages.error_occurred')]);
        }
        return response()->json(['status' => 1, 'message' => __('common.deleted_successfully')]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'order'   => 'required|array',
            'order.*' => 'required|integer|exists:service_categories,id',
        ]);
        if ($v->fails()) {
            return response()->json(['status' => 0, 'message' => $v->errors()->first()]);
        }
        $this->service->reorder($request->input('order'));
        return response()->json(['status' => 1]);
    }
}
