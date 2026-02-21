<?php

namespace App\Http\Controllers;

use App\AccessLog;
use App\MedicalCaseAmendment;
use App\Services\MedicalCaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MedicalCaseController extends Controller
{
    private MedicalCaseService $medicalCaseService;

    public function __construct(MedicalCaseService $medicalCaseService)
    {
        $this->medicalCaseService = $medicalCaseService;
        $this->middleware('can:manage-medical-cases');
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->medicalCaseService->getAllCases([
                'search'     => $request->input('search.value', ''),
                'status'     => $request->input('status'),
                'doctor_id'  => $request->input('doctor_id'),
                'patient_id' => $request->input('patient_id'),
                'start_date' => $request->input('start_date'),
                'end_date'   => $request->input('end_date'),
            ]);

            return $this->medicalCaseService->buildIndexDataTable($data);
        }

        return view('medical_cases.index');
    }

    /**
     * Display cases for a specific patient.
     *
     * @param Request $request
     * @param int $patient_id
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function patientCases(Request $request, $patient_id)
    {
        if ($request->ajax()) {
            $data = $this->medicalCaseService->getPatientCases((int) $patient_id);

            return $this->medicalCaseService->buildPatientCasesDataTable($data);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $isDraft = $request->input('is_draft', '1') === '1';

        $rules = [
            'patient_id' => 'required|exists:patients,id',
            'case_date' => 'required|date',
        ];

        if (!$isDraft) {
            $rules['chief_complaint'] = 'required|string|min:10';
            $rules['examination'] = 'required|string';
            $rules['diagnosis'] = 'required|string';
            $rules['treatment'] = 'required|string';
        }

        Validator::make($request->all(), $rules, [
            'patient_id.required' => __('validation.custom.patient_id.required'),
            'case_date.required' => __('validation.custom.case_date.required'),
            'chief_complaint.required' => __('medical_cases.chief_complaint_required'),
            'chief_complaint.min' => __('medical_cases.chief_complaint_min'),
            'examination.required' => __('medical_cases.examination_required'),
            'diagnosis.required' => __('medical_cases.diagnosis_required'),
            'treatment.required' => __('medical_cases.treatment_required'),
        ])->validate();

        $data = $this->medicalCaseService->buildCaseData($request->only([
            'patient_id', 'case_date', 'chief_complaint', 'history_of_present_illness',
            'examination', 'examination_teeth', 'auxiliary_examination', 'related_images',
            'diagnosis', 'diagnosis_code', 'related_teeth', 'treatment', 'treatment_services',
            'medical_orders', 'next_visit_date', 'next_visit_note', 'auto_create_followup',
            'visit_type', 'doctor_id',
        ]));
        $case = $this->medicalCaseService->createCase($data, $isDraft);

        if ($case) {
            // Sign the case if signature data provided
            if (!$isDraft && $request->filled('signature')) {
                $case->sign($request->input('signature'));
            }

            return response()->json([
                'message' => $isDraft ? __('medical_cases.draft_saved') : __('medical_cases.record_submitted'),
                'status' => true,
                'id' => $case->id
            ]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        AccessLog::log('MedicalCase', 'view', $id);
        $detail = $this->medicalCaseService->getCaseDetail((int) $id);

        return view('medical_cases.show', $detail);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $data = $this->medicalCaseService->getCaseForEdit((int) $id);

        return view('medical_cases.edit', $data);
    }

    /**
     * Get case data as JSON for AJAX requests.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function getCase($id)
    {
        return response()->json($this->medicalCaseService->getCase((int) $id));
    }

    /**
     * Show the form for creating a new medical case.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data = $this->medicalCaseService->getCreateData();

        return view('medical_cases.edit', $data);
    }

    /**
     * Create a new medical case for a patient (form view).
     *
     * @param int $patient_id
     * @return \Illuminate\Http\Response
     */
    public function createForPatient($patient_id)
    {
        $data = $this->medicalCaseService->getCreateForPatientData((int) $patient_id);

        return view('medical_cases.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $isDraft = $request->input('is_draft', '1') === '1';

        $rules = [
            'patient_id' => 'required|exists:patients,id',
            'case_date' => 'required|date',
        ];

        if (!$isDraft) {
            $rules['chief_complaint'] = 'required|string|min:10';
            $rules['examination'] = 'required|string';
            $rules['diagnosis'] = 'required|string';
            $rules['treatment'] = 'required|string';
        }

        Validator::make($request->all(), $rules, [
            'patient_id.required' => __('validation.custom.patient_id.required'),
            'case_date.required' => __('validation.custom.case_date.required'),
            'chief_complaint.required' => __('medical_cases.chief_complaint_required'),
            'chief_complaint.min' => __('medical_cases.chief_complaint_min'),
            'examination.required' => __('medical_cases.examination_required'),
            'diagnosis.required' => __('medical_cases.diagnosis_required'),
            'treatment.required' => __('medical_cases.treatment_required'),
        ])->validate();

        $data = $this->medicalCaseService->buildCaseData($request->only([
            'patient_id', 'case_date', 'chief_complaint', 'history_of_present_illness',
            'examination', 'examination_teeth', 'auxiliary_examination', 'related_images',
            'diagnosis', 'diagnosis_code', 'related_teeth', 'treatment', 'treatment_services',
            'medical_orders', 'next_visit_date', 'next_visit_note', 'auto_create_followup',
            'visit_type', 'doctor_id',
        ]), isUpdate: true);
        $result = $this->medicalCaseService->updateCase(
            (int) $id,
            $data,
            $isDraft,
            $request->modification_reason,
            $request->status,
            $request->closing_notes
        );

        if (!empty($result['require_reason'])) {
            return response()->json([
                'message' => __('medical_cases.edit_requires_approval'),
                'status' => false,
                'require_reason' => true
            ]);
        }

        if (!empty($result['amendment_id'])) {
            return response()->json([
                'message' => __('medical_cases.amendment_submitted'),
                'status' => true,
                'amendment_id' => $result['amendment_id'],
                'id' => $id
            ]);
        }

        if ($result['status']) {
            return response()->json([
                'message' => $isDraft ? __('medical_cases.draft_saved') : __('medical_cases.case_updated_successfully'),
                'status' => true,
                'id' => $id
            ]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $status = $this->medicalCaseService->deleteCase((int) $id);
        if ($status) {
            return response()->json(['message' => __('medical_cases.case_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }

    /**
     * Print the specified medical case.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function printCase($id)
    {
        AccessLog::log('MedicalCase', 'print', $id);
        $data = $this->medicalCaseService->getPrintData((int) $id);

        return view('medical_cases.print', $data);
    }

    /**
     * Export medical case as PDF download.
     */
    public function exportPdf($id)
    {
        AccessLog::log('MedicalCase', 'export_pdf', $id);
        $data = $this->medicalCaseService->getPrintData((int) $id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('medical_cases.print', $data)
            ->setPaper('a4');

        $filename = $data['case']->case_no . '_v' . ($data['case']->version_number ?? 1) . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Archive medical case PDF to storage.
     */
    public function archivePdf($id)
    {
        $data = $this->medicalCaseService->getPrintData((int) $id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('medical_cases.print', $data)
            ->setPaper('a4');

        $filename = $data['case']->case_no . '_v' . ($data['case']->version_number ?? 1) . '.pdf';
        $path = 'medical_records/' . $filename;

        \Illuminate\Support\Facades\Storage::put($path, $pdf->output());

        return response()->json([
            'message' => __('medical_cases.pdf_archived'),
            'status' => true,
            'path' => $path,
        ]);
    }

    /**
     * Search ICD-10 codes for diagnosis.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function searchIcd10(Request $request)
    {
        $query = $request->input('q', '');
        return response()->json($this->medicalCaseService->searchIcd10($query));
    }

    /**
     * List amendments for a medical case.
     */
    public function amendments($id)
    {
        $amendments = MedicalCaseAmendment::forCase($id)
            ->with(['requestedBy', 'approvedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['status' => true, 'data' => $amendments]);
    }

    /**
     * Approve an amendment request.
     */
    public function approveAmendment(Request $request, $amendmentId)
    {
        $this->authorize('approve-medical-case-amendment');

        $amendment = MedicalCaseAmendment::findOrFail($amendmentId);

        if ($amendment->status !== MedicalCaseAmendment::STATUS_PENDING) {
            return response()->json([
                'message' => __('medical_cases.amendment_already_reviewed'),
                'status' => false,
            ]);
        }

        $amendment->approve(auth()->id(), $request->input('review_notes'));

        return response()->json([
            'message' => __('medical_cases.amendment_approved'),
            'status' => true,
        ]);
    }

    /**
     * Reject an amendment request.
     */
    public function rejectAmendment(Request $request, $amendmentId)
    {
        $this->authorize('approve-medical-case-amendment');

        $amendment = MedicalCaseAmendment::findOrFail($amendmentId);

        if ($amendment->status !== MedicalCaseAmendment::STATUS_PENDING) {
            return response()->json([
                'message' => __('medical_cases.amendment_already_reviewed'),
                'status' => false,
            ]);
        }

        Validator::make($request->all(), [
            'review_notes' => 'required|string|min:5',
        ])->validate();

        $amendment->reject(auth()->id(), $request->input('review_notes'));

        return response()->json([
            'message' => __('medical_cases.amendment_rejected'),
            'status' => true,
        ]);
    }

    /**
     * Get version history (audit trail) for a medical case.
     */
    public function versionHistory($id)
    {
        $case = \App\MedicalCase::findOrFail($id);
        $history = $case->versionHistory();

        return response()->json(['status' => true, 'data' => $history]);
    }
}
