<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\SupplierResource;
use App\Services\SupplierService;
use App\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @group Suppliers
 */
class SupplierController extends ApiController
{
    public function __construct(
        protected SupplierService $service
    ) {
        $this->middleware('can:manage-inventory');
    }

    public function index(Request $request): JsonResponse
    {
        $query = Supplier::whereNull('deleted_at');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $paginator = $query->orderBy('updated_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return $this->paginated($paginator, SupplierResource::class);
    }

    public function show(int $id): JsonResponse
    {
        $supplier = $this->service->getSupplier($id);

        if (!$supplier) {
            return $this->error('Supplier not found', 404);
        }

        return $this->success(new SupplierResource($supplier));
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $supplier = $this->service->createSupplier($request->only(['name']));

        if (!$supplier) {
            return $this->error('Failed to create supplier', 500);
        }

        return $this->success(new SupplierResource($supplier), 'Supplier created', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $status = $this->service->updateSupplier($id, $request->only(['name']));

        if (!$status) {
            return $this->error('Failed to update supplier', 500);
        }

        $supplier = $this->service->getSupplier($id);

        return $this->success(new SupplierResource($supplier), 'Supplier updated');
    }

    public function destroy(int $id): JsonResponse
    {
        $status = $this->service->deleteSupplier($id);

        if (!$status) {
            return $this->error('Failed to delete supplier', 500);
        }

        return $this->success(null, 'Supplier deleted');
    }
}
