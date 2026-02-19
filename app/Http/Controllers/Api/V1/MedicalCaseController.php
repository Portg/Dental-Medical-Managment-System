<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\MedicalCaseResource;
use App\MedicalCase;
use App\Services\MedicalCaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MedicalCaseController extends ApiController
{
    public function __construct(
        protected MedicalCaseService $medicalCaseService
    ) {
        $this->middleware('can:manage-medical-cases');
    }

    public function index(Request $request): JsonResponse
    {
        $query = MedicalCase::with(['patient', 'doctor'])
            ->whereNull('deleted_at');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('case_no', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('chief_complaint', 'like', "%{$search}%")
                  ->orWhereHas('patient', fn ($pq) => $pq->where('surname', 'like', "%{$search}%")
                      ->orWhere('othername', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($doctorId = $request->input('doctor_id')) {
            $query->where('doctor_id', $doctorId);
        }

        if ($patientId = $request->input('patient_id')) {
            $query->where('patient_id', $patientId);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('case_date', [$request->input('start_date'), $request->input('end_date')]);
        }

        $paginator = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return $this->paginated($paginator, MedicalCaseResource::class);
    }

    public function show(int $id): JsonResponse
    {
        $case = MedicalCase::with(['patient', 'doctor', 'addedBy'])->findOrFail($id);

        return $this->success(new MedicalCaseResource($case));
    }

    public function store(Request $request): JsonResponse
    {
        $isDraft = $request->boolean('is_draft', false);

        $rules = [
            'patient_id' => 'required|exists:patients,id',
            'case_date'  => 'required|date',
        ];

        if (!$isDraft) {
            $rules['chief_complaint'] = 'required|string';
            $rules['examination']     = 'required|string';
            $rules['diagnosis']       = 'required|string';
            $rules['treatment']       = 'required|string';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $data = $this->medicalCaseService->buildCaseData($request->only([
            'patient_id', 'case_date', 'chief_complaint', 'history_of_present_illness',
            'examination', 'examination_teeth', 'auxiliary_examination', 'related_images',
            'diagnosis', 'diagnosis_code', 'related_teeth', 'treatment', 'treatment_services',
            'medical_orders', 'next_visit_date', 'next_visit_note', 'auto_create_followup',
            'visit_type', 'doctor_id',
        ]));
        $case = $this->medicalCaseService->createCase($data, $isDraft);

        if (!$case) {
            return $this->error('Failed to create medical case', 500);
        }

        $case->load(['patient', 'doctor']);

        return $this->success(new MedicalCaseResource($case), 'Medical case created', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $isDraft = $request->boolean('is_draft', false);

        $rules = [
            'case_date' => 'required|date',
        ];

        if (!$isDraft) {
            $rules['chief_complaint'] = 'required|string';
            $rules['examination']     = 'required|string';
            $rules['diagnosis']       = 'required|string';
            $rules['treatment']       = 'required|string';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $data   = $this->medicalCaseService->buildCaseData($request->only([
            'patient_id', 'case_date', 'chief_complaint', 'history_of_present_illness',
            'examination', 'examination_teeth', 'auxiliary_examination', 'related_images',
            'diagnosis', 'diagnosis_code', 'related_teeth', 'treatment', 'treatment_services',
            'medical_orders', 'next_visit_date', 'next_visit_note', 'auto_create_followup',
            'visit_type', 'doctor_id',
        ]), true);
        $result = $this->medicalCaseService->updateCase(
            $id,
            $data,
            $isDraft,
            $request->input('modification_reason'),
            $request->input('closing_status'),
            $request->input('closing_notes')
        );

        if (!empty($result['require_reason'])) {
            return $this->error('Modification reason required for locked case', 422, [
                'modification_reason' => ['This case is locked. A modification reason is required.'],
            ]);
        }

        if (!$result['status']) {
            return $this->error('Failed to update medical case', 500);
        }

        $case = MedicalCase::with(['patient', 'doctor'])->find($id);

        return $this->success(new MedicalCaseResource($case), 'Medical case updated');
    }

    public function destroy(int $id): JsonResponse
    {
        $status = $this->medicalCaseService->deleteCase($id);

        if (!$status) {
            return $this->error('Failed to delete medical case', 500);
        }

        return $this->success(null, 'Medical case deleted');
    }

    public function patientCases(int $patientId): JsonResponse
    {
        $cases = $this->medicalCaseService->getPatientCases($patientId);

        return $this->success($cases);
    }

    public function icd10Search(Request $request): JsonResponse
    {
        $results = $this->medicalCaseService->searchIcd10($request->input('q', ''));

        return $this->success($results);
    }
}
