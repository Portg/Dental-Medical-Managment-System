<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\MedicalServiceResource;
use App\MedicalService;
use App\Services\MedicalServiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MedicalServiceController extends ApiController
{
    public function __construct(
        protected MedicalServiceService $service
    ) {
        $this->middleware('can:manage-medical-services');
    }

    public function index(Request $request): JsonResponse
    {
        $query = MedicalService::whereNull('deleted_at');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $paginator = $query->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 15));

        return $this->paginated($paginator, MedicalServiceResource::class);
    }

    public function show(int $id): JsonResponse
    {
        $service = $this->service->getServiceForEdit($id);

        if (!$service) {
            return $this->error('Medical service not found', 404);
        }

        return $this->success(new MedicalServiceResource($service));
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'  => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $service = $this->service->createService($request->only(['name', 'price']));

        if (!$service) {
            return $this->error('Failed to create medical service', 500);
        }

        return $this->success(new MedicalServiceResource($service), 'Medical service created', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'  => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $status = $this->service->updateService($id, $request->only(['name', 'price']));

        if (!$status) {
            return $this->error('Failed to update medical service', 500);
        }

        $service = $this->service->getServiceForEdit($id);

        return $this->success(new MedicalServiceResource($service), 'Medical service updated');
    }

    public function destroy(int $id): JsonResponse
    {
        $status = $this->service->deleteService($id);

        if (!$status) {
            return $this->error('Failed to delete medical service', 500);
        }

        return $this->success(null, 'Medical service deleted');
    }
}
