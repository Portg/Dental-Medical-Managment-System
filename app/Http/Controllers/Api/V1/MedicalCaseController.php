<?php

namespace App\Http\Controllers\Api\V1;

use App\AccessLog;
use App\Http\Resources\MedicalCaseResource;
use App\MedicalCase;
use App\MedicalCaseAmendment;
use App\Services\MedicalCaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @group Medical Cases
 */
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

        if (!empty($result['amendment_id'])) {
            return $this->success([
                'amendment_id' => $result['amendment_id'],
            ], 'Amendment submitted for approval', 202);
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

    // ─── Compliance Endpoints ────────────────────────────────────────────

    /**
     * List amendments
     *
     * Get all amendment requests for a medical case.
     *
     * @group Medical Cases
     * @subgroup Compliance
     */
    public function amendments(int $id): JsonResponse
    {
        MedicalCase::findOrFail($id);

        $amendments = MedicalCaseAmendment::where('medical_case_id', $id)
            ->with(['requestedBy', 'approvedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success($amendments);
    }

    /**
     * Version history
     *
     * Get the audit trail / version history for a medical case.
     *
     * @group Medical Cases
     * @subgroup Compliance
     */
    public function versionHistory(int $id): JsonResponse
    {
        $case = MedicalCase::findOrFail($id);

        return $this->success($case->versionHistory());
    }

    /**
     * Approve amendment
     *
     * Approve a pending amendment request. Requires `approve-medical-case-amendment` permission.
     *
     * @group Medical Cases
     * @subgroup Compliance
     */
    public function approveAmendment(Request $request, int $id): JsonResponse
    {
        $this->authorize('approve-medical-case-amendment');

        $amendment = MedicalCaseAmendment::findOrFail($id);

        if ($amendment->status !== MedicalCaseAmendment::STATUS_PENDING) {
            return $this->error('Amendment already reviewed', 409);
        }

        $amendment->approve(auth()->id(), $request->input('review_notes'));

        return $this->success(null, 'Amendment approved');
    }

    /**
     * Reject amendment
     *
     * Reject a pending amendment request. Requires `approve-medical-case-amendment` permission.
     *
     * @group Medical Cases
     * @subgroup Compliance
     * @bodyParam review_notes string required Reason for rejection (min 5 chars). Example: Insufficient justification for change
     */
    public function rejectAmendment(Request $request, int $id): JsonResponse
    {
        $this->authorize('approve-medical-case-amendment');

        $amendment = MedicalCaseAmendment::findOrFail($id);

        if ($amendment->status !== MedicalCaseAmendment::STATUS_PENDING) {
            return $this->error('Amendment already reviewed', 409);
        }

        $validator = Validator::make($request->all(), [
            'review_notes' => 'required|string|min:5',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $amendment->reject(auth()->id(), $request->input('review_notes'));

        return $this->success(null, 'Amendment rejected');
    }

    /**
     * Export PDF
     *
     * Download the medical case as a compliance PDF with signature, version number, and watermark.
     *
     * @group Medical Cases
     * @subgroup Compliance
     * @response file
     */
    public function exportPdf(int $id)
    {
        AccessLog::log('MedicalCase', 'export_pdf', $id);
        $data = $this->medicalCaseService->getPrintData($id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('medical_cases.print', $data)
            ->setPaper('a4');

        $filename = $data['case']->case_no . '_v' . ($data['case']->version_number ?? 1) . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Archive PDF
     *
     * Generate and store a compliance PDF to server storage for long-term archival.
     *
     * @group Medical Cases
     * @subgroup Compliance
     */
    public function archivePdf(int $id): JsonResponse
    {
        $data = $this->medicalCaseService->getPrintData($id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('medical_cases.print', $data)
            ->setPaper('a4');

        $filename = $data['case']->case_no . '_v' . ($data['case']->version_number ?? 1) . '.pdf';
        $path = 'medical_records/' . $filename;

        \Illuminate\Support\Facades\Storage::put($path, $pdf->output());

        return $this->success(['path' => $path], 'PDF archived successfully');
    }
}
