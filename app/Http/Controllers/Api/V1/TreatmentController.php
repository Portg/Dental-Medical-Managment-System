<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\TreatmentResource;
use App\Services\TreatmentService;
use App\Treatment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @group Treatments
 */
class TreatmentController extends ApiController
{
    public function __construct(
        protected TreatmentService $service
    ) {
        $this->middleware('can:manage-treatments');
    }

    public function index(Request $request): JsonResponse
    {
        $query = Treatment::with('addedBy')->whereNull('deleted_at');

        if ($request->filled('patient_id')) {
            $query->whereHas('appointment', function ($q) use ($request) {
                $q->where('patient_id', $request->input('patient_id'));
            });
        }

        if ($request->filled('appointment_id')) {
            $query->where('appointment_id', $request->input('appointment_id'));
        }

        $paginator = $query->orderBy('updated_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return $this->paginated($paginator, TreatmentResource::class);
    }

    public function show(int $id): JsonResponse
    {
        $treatment = Treatment::with('addedBy')->find($id);

        if (!$treatment) {
            return $this->error('Treatment not found', 404);
        }

        return $this->success(new TreatmentResource($treatment));
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'clinical_notes' => 'required|string',
            'treatment'      => 'required|string',
            'appointment_id' => 'required|exists:appointments,id',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $treatment = $this->service->createTreatment(
            $request->input('clinical_notes'),
            $request->input('treatment'),
            $request->input('appointment_id')
        );

        if (!$treatment) {
            return $this->error('Failed to create treatment', 500);
        }

        $treatment->load('addedBy');

        return $this->success(new TreatmentResource($treatment), 'Treatment created', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'clinical_notes' => 'required|string',
            'treatment'      => 'required|string',
            'appointment_id' => 'required|exists:appointments,id',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $status = $this->service->updateTreatment(
            $id,
            $request->input('clinical_notes'),
            $request->input('treatment'),
            $request->input('appointment_id')
        );

        if (!$status) {
            return $this->error('Failed to update treatment', 500);
        }

        $treatment = Treatment::with('addedBy')->find($id);

        return $this->success(new TreatmentResource($treatment), 'Treatment updated');
    }

    public function destroy(int $id): JsonResponse
    {
        $status = $this->service->deleteTreatment($id);

        if (!$status) {
            return $this->error('Failed to delete treatment', 500);
        }

        return $this->success(null, 'Treatment deleted');
    }
}
