<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\LabCaseResource;
use App\LabCase;
use App\Services\LabCaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @group Lab Cases
 */
class LabCaseController extends ApiController
{
    public function __construct(
        protected LabCaseService $service
    ) {
        $this->middleware('can:manage-labs');
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'lab_id', 'doctor_id', 'search']);
        $cases = $this->service->getLabCaseList($filters);

        return $this->success($cases);
    }

    public function show(int $id): JsonResponse
    {
        $case = $this->service->getLabCase($id);

        if (!$case) {
            return $this->error(__('lab_cases.case_not_found'), 404);
        }

        return $this->success(new LabCaseResource($case));
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'patient_id'           => 'required|exists:patients,id',
            'doctor_id'            => 'required|exists:users,id',
            'lab_id'               => 'required|exists:labs,id',
            'prosthesis_type'      => 'required|string|max:100',
            'material'             => 'nullable|string|max:100',
            'color_shade'          => 'nullable|string|max:50',
            'teeth_positions'      => 'nullable|array',
            'special_requirements' => 'nullable|string|max:2000',
            'expected_return_date' => 'nullable|date|after_or_equal:today',
            'lab_fee'              => 'nullable|numeric|min:0',
            'patient_charge'       => 'nullable|numeric|min:0',
            'appointment_id'       => 'nullable|exists:appointments,id',
            'medical_case_id'      => 'nullable|exists:medical_cases,id',
            'notes'                => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->error(__('common.validation_failed'), 422, $validator->errors());
        }

        $case = $this->service->createLabCase($request->only([
            'patient_id', 'doctor_id', 'lab_id', 'prosthesis_type',
            'material', 'color_shade', 'teeth_positions', 'special_requirements',
            'expected_return_date', 'lab_fee', 'patient_charge',
            'appointment_id', 'medical_case_id', 'notes',
        ]));

        $case->load(['patient', 'doctor', 'lab']);

        return $this->success(new LabCaseResource($case), __('lab_cases.case_created'), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'prosthesis_type'      => 'nullable|string|max:100',
            'material'             => 'nullable|string|max:100',
            'color_shade'          => 'nullable|string|max:50',
            'teeth_positions'      => 'nullable|array',
            'special_requirements' => 'nullable|string|max:2000',
            'expected_return_date' => 'nullable|date',
            'lab_fee'              => 'nullable|numeric|min:0',
            'patient_charge'       => 'nullable|numeric|min:0',
            'quality_rating'       => 'nullable|integer|min:1|max:5',
            'notes'                => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->error(__('common.validation_failed'), 422, $validator->errors());
        }

        $status = $this->service->updateLabCase($id, $request->only([
            'prosthesis_type', 'material', 'color_shade', 'teeth_positions',
            'special_requirements', 'expected_return_date', 'lab_fee',
            'patient_charge', 'quality_rating', 'notes',
        ]));

        if (!$status) {
            return $this->error(__('lab_cases.error_updating_case'), 500);
        }

        $case = $this->service->getLabCase($id);

        return $this->success(new LabCaseResource($case), __('lab_cases.case_updated'));
    }

    public function destroy(int $id): JsonResponse
    {
        $status = $this->service->deleteLabCase($id);

        if (!$status) {
            return $this->error(__('lab_cases.error_deleting_case'), 500);
        }

        return $this->success(null, __('lab_cases.case_deleted'));
    }

    /**
     * Update lab case status.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validStatuses = implode(',', array_keys(LabCase::STATUSES));

        $validator = Validator::make($request->all(), [
            'status'        => "required|in:{$validStatuses}",
            'rework_reason' => 'nullable|required_if:status,rework|string|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->error(__('common.validation_failed'), 422, $validator->errors());
        }

        $status = $this->service->updateStatus(
            $id,
            $request->input('status'),
            $request->only(['rework_reason', 'sent_date', 'actual_return_date'])
        );

        if (!$status) {
            return $this->error(__('lab_cases.error_updating_status'), 500);
        }

        $case = $this->service->getLabCase($id);

        return $this->success(new LabCaseResource($case), __('lab_cases.status_updated'));
    }

    /**
     * Get overdue lab cases.
     */
    public function overdue(): JsonResponse
    {
        $cases = $this->service->getOverdueCases();

        return $this->success($cases);
    }

    /**
     * Get lab cases for a patient.
     */
    public function patientCases(int $patientId): JsonResponse
    {
        $cases = $this->service->getPatientCases($patientId);

        return $this->success(LabCaseResource::collection($cases));
    }

    /**
     * Get lab case statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->service->getStatistics();

        return $this->success($stats);
    }

    /**
     * Get enum options (prosthesis types, materials, statuses).
     */
    public function options(): JsonResponse
    {
        return $this->success([
            'prosthesis_types' => LabCase::PROSTHESIS_TYPES,
            'materials'        => LabCase::MATERIALS,
            'statuses'         => LabCase::STATUSES,
        ]);
    }
}
