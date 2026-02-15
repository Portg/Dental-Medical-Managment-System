<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\LabResource;
use App\Services\LabService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LabController extends ApiController
{
    public function __construct(
        protected LabService $service
    ) {}

    public function index(): JsonResponse
    {
        $labs = $this->service->getLabList();

        return $this->success($labs);
    }

    public function show(int $id): JsonResponse
    {
        $lab = $this->service->getLab($id);

        if (!$lab) {
            return $this->error(__('lab_cases.lab_not_found'), 404);
        }

        return $this->success(new LabResource($lab));
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'                => 'required|string|max:255',
            'contact'             => 'nullable|string|max:255',
            'phone'               => 'nullable|string|max:50',
            'address'             => 'nullable|string|max:500',
            'specialties'         => 'nullable|string|max:500',
            'avg_turnaround_days' => 'nullable|integer|min:1|max:365',
            'notes'               => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->error(__('common.validation_failed'), 422, $validator->errors());
        }

        $lab = $this->service->createLab(array_merge(
            $request->only(['name', 'contact', 'phone', 'address', 'specialties', 'avg_turnaround_days', 'notes']),
            ['_who_added' => Auth::id()]
        ));

        return $this->success(new LabResource($lab), __('lab_cases.lab_created'), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'                => 'required|string|max:255',
            'contact'             => 'nullable|string|max:255',
            'phone'               => 'nullable|string|max:50',
            'address'             => 'nullable|string|max:500',
            'specialties'         => 'nullable|string|max:500',
            'avg_turnaround_days' => 'nullable|integer|min:1|max:365',
            'notes'               => 'nullable|string|max:1000',
            'is_active'           => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error(__('common.validation_failed'), 422, $validator->errors());
        }

        $status = $this->service->updateLab($id, $request->only([
            'name', 'contact', 'phone', 'address', 'specialties', 'avg_turnaround_days', 'notes', 'is_active',
        ]));

        if (!$status) {
            return $this->error(__('lab_cases.error_updating_lab'), 500);
        }

        $lab = $this->service->getLab($id);

        return $this->success(new LabResource($lab), __('lab_cases.lab_updated'));
    }

    public function destroy(int $id): JsonResponse
    {
        $status = $this->service->deleteLab($id);

        if (!$status) {
            return $this->error(__('lab_cases.lab_has_active_cases'));
        }

        return $this->success(null, __('lab_cases.lab_deleted'));
    }
}
