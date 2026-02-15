<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\TreatmentPlanResource;
use App\Services\TreatmentPlanService;
use App\TreatmentPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TreatmentPlanController extends ApiController
{
    public function __construct(
        protected TreatmentPlanService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = TreatmentPlan::with('patient')->whereNull('deleted_at');

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->input('patient_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $paginator = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return $this->paginated($paginator, TreatmentPlanResource::class);
    }

    public function show(int $id): JsonResponse
    {
        $plan = TreatmentPlan::with(['patient', 'items', 'stages'])->find($id);

        if (!$plan) {
            return $this->error('Treatment plan not found', 404);
        }

        return $this->success(new TreatmentPlanResource($plan));
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'plan_name'              => 'required|string|max:255',
            'patient_id'             => 'required|exists:patients,id',
            'description'            => 'nullable|string',
            'planned_procedures'     => 'nullable|string',
            'estimated_cost'         => 'nullable|numeric|min:0',
            'status'                 => 'nullable|in:draft,planned,confirmed,in_progress,completed,cancelled',
            'priority'               => 'nullable|string|max:50',
            'start_date'             => 'nullable|date',
            'target_completion_date' => 'nullable|date',
            'medical_case_id'        => 'nullable|exists:medical_cases,id',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $plan = $this->service->createPlan($request->all());

        if (!$plan) {
            return $this->error('Failed to create treatment plan', 500);
        }

        $plan->load(['patient', 'items', 'stages']);

        return $this->success(new TreatmentPlanResource($plan), 'Treatment plan created', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'plan_name'              => 'required|string|max:255',
            'description'            => 'nullable|string',
            'planned_procedures'     => 'nullable|string',
            'estimated_cost'         => 'nullable|numeric|min:0',
            'actual_cost'            => 'nullable|numeric|min:0',
            'status'                 => 'nullable|in:draft,planned,confirmed,in_progress,completed,cancelled',
            'priority'               => 'nullable|string|max:50',
            'start_date'             => 'nullable|date',
            'target_completion_date' => 'nullable|date',
            'completion_notes'       => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $status = $this->service->updatePlan($id, $request->all());

        if (!$status) {
            return $this->error('Failed to update treatment plan', 500);
        }

        $plan = TreatmentPlan::with(['patient', 'items', 'stages'])->find($id);

        return $this->success(new TreatmentPlanResource($plan), 'Treatment plan updated');
    }

    public function destroy(int $id): JsonResponse
    {
        $status = $this->service->deletePlan($id);

        if (!$status) {
            return $this->error('Failed to delete treatment plan', 500);
        }

        return $this->success(null, 'Treatment plan deleted');
    }

    public function patientPlans(int $patientId): JsonResponse
    {
        $plans = $this->service->getPatientPlans($patientId);

        return $this->success($plans);
    }
}
