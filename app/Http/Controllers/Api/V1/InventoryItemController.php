<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\InventoryItemResource;
use App\InventoryItem;
use App\Services\InventoryItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InventoryItemController extends ApiController
{
    public function __construct(
        protected InventoryItemService $service
    ) {
        $this->middleware('can:manage-inventory');
    }

    public function index(Request $request): JsonResponse
    {
        $query = InventoryItem::with('category')->whereNull('deleted_at');

        if ($search = $request->input('search')) {
            $query->search($search);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->has('is_active') && $request->input('is_active') !== '') {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $paginator = $query->orderBy('updated_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return $this->paginated($paginator, InventoryItemResource::class);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'item_code'     => 'required|string|max:50|unique:inventory_items,item_code',
            'name'          => 'required|string|max:255',
            'unit'          => 'required|string|max:50',
            'category_id'   => 'required|exists:inventory_categories,id',
            'reference_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'specification' => 'nullable|string|max:255',
            'brand'         => 'nullable|string|max:100',
            'track_expiry'  => 'nullable|boolean',
            'stock_warning_level' => 'nullable|integer|min:0',
            'storage_location' => 'nullable|string|max:255',
            'notes'         => 'nullable|string',
            'is_active'     => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $item = $this->service->createItem($request->only([
            'item_code', 'name', 'unit', 'category_id', 'reference_price', 'selling_price',
            'specification', 'brand', 'track_expiry', 'stock_warning_level',
            'storage_location', 'notes', 'is_active',
        ]));

        if (!$item) {
            return $this->error('Failed to create inventory item', 500);
        }

        $item->load('category');

        return $this->success(new InventoryItemResource($item), 'Inventory item created', 201);
    }

    public function show(int $id): JsonResponse
    {
        $item = $this->service->getItemForEdit($id);

        if (!$item) {
            return $this->error('Inventory item not found', 404);
        }

        return $this->success(new InventoryItemResource($item));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'item_code'     => 'required|string|max:50|unique:inventory_items,item_code,' . $id,
            'name'          => 'required|string|max:255',
            'unit'          => 'required|string|max:50',
            'category_id'   => 'required|exists:inventory_categories,id',
            'reference_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'specification' => 'nullable|string|max:255',
            'brand'         => 'nullable|string|max:100',
            'track_expiry'  => 'nullable|boolean',
            'stock_warning_level' => 'nullable|integer|min:0',
            'storage_location' => 'nullable|string|max:255',
            'notes'         => 'nullable|string',
            'is_active'     => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $status = $this->service->updateItem($id, $request->only([
            'item_code', 'name', 'unit', 'category_id', 'reference_price', 'selling_price',
            'specification', 'brand', 'track_expiry', 'stock_warning_level',
            'storage_location', 'notes', 'is_active',
        ]));

        if (!$status) {
            return $this->error('Failed to update inventory item', 500);
        }

        $item = InventoryItem::with('category')->find($id);

        return $this->success(new InventoryItemResource($item), 'Inventory item updated');
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->deleteItem($id);

        if (!$result['status']) {
            return $this->error($result['message']);
        }

        return $this->success(null, $result['message']);
    }

    public function search(Request $request): JsonResponse
    {
        $keyword = $request->input('q', '');
        $items = $this->service->searchItems($keyword);

        return $this->success($items);
    }

    public function lowStock(): JsonResponse
    {
        $items = $this->service->getLowStockItems();

        return $this->success(InventoryItemResource::collection($items));
    }

    public function expiring(Request $request): JsonResponse
    {
        $days = $request->input('days', 30);
        $batches = $this->service->getExpiryWarningBatches((int) $days);

        return $this->success($batches);
    }
}
