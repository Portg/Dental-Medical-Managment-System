<?php

namespace App\Http\Controllers;

use App\Http\Helper\ActionColumnHelper;
use App\Services\MedicalCaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class MedicalCaseController extends Controller
{
    private MedicalCaseService $medicalCaseService;

    public function __construct(MedicalCaseService $medicalCaseService)
    {
        $this->medicalCaseService = $medicalCaseService;
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
            $data = $this->medicalCaseService->getAllCases($request->all());

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('statusBadge', function ($row) {
                    $class = 'default';
                    if ($row->status == 'Open') $class = 'success';
                    elseif ($row->status == 'In Progress') $class = 'info';
                    elseif ($row->status == 'Closed') $class = 'danger';
                    elseif ($row->status == 'Follow-up') $class = 'warning';
                    $statusKey = strtolower(str_replace([' ', '-'], '_', $row->status));
                    return '<span class="label label-' . $class . '">' . __('medical_cases.status_' . $statusKey) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return ActionColumnHelper::make($row->id)
                        ->primaryIf($row->deleted_at == null, 'edit')
                        ->add('delete')
                        ->render();
                })
                ->rawColumns(['statusBadge', 'action'])
                ->make(true);
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
            $data = $this->medicalCaseService->getPatientCases($patient_id);

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('viewBtn', function ($row) {
                    return '<a href="' . url('medical-cases/' . $row->id) . '" class="btn btn-info btn-sm">' . __('common.view') . '</a>';
                })
                ->addColumn('statusBadge', function ($row) {
                    $class = 'default';
                    if ($row->status == 'Open') $class = 'success';
                    elseif ($row->status == 'Closed') $class = 'danger';
                    elseif ($row->status == 'Follow-up') $class = 'warning';
                    return '<span class="label label-' . $class . '">' . __('medical_cases.status_' . strtolower(str_replace('-', '_', $row->status))) . '</span>';
                })
                ->rawColumns(['viewBtn', 'statusBadge'])
                ->make(true);
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

        $data = $this->medicalCaseService->buildCaseData($request->all());
        $case = $this->medicalCaseService->createCase($data, $isDraft);

        if ($case) {
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
        $detail = $this->medicalCaseService->getCaseDetail($id);

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
        $data = $this->medicalCaseService->getCaseForEdit($id);

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
        return response()->json($this->medicalCaseService->getCase($id));
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
        $data = $this->medicalCaseService->getCreateForPatientData($patient_id);

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

        $data = $this->medicalCaseService->buildCaseData($request->all(), isUpdate: true);
        $result = $this->medicalCaseService->updateCase(
            $id,
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
        $status = $this->medicalCaseService->deleteCase($id);
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
        $data = $this->medicalCaseService->getPrintData($id);

        return view('medical_cases.print', $data);
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
}
