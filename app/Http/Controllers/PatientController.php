<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Services\PatientService;
use Illuminate\Http\Request;
use App\Exports\PatientExport;
use Maatwebsite\Excel\Facades\Excel;

class PatientController extends Controller
{
    private PatientService $patientService;

    public function __construct(PatientService $patientService)
    {
        $this->patientService = $patientService;

        $this->middleware('can:view-patients')->only(['index', 'show', 'filterPatients', 'patientMedicalHistory', 'exportPatients']);
        $this->middleware('can:create-patients')->only(['create', 'store']);
        $this->middleware('can:edit-patients')->only(['edit', 'update']);
        $this->middleware('can:delete-patients')->only(['destroy']);
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
            if (!empty($request->start_date) && !empty($request->end_date)) {
                FunctionsHelper::storeDateFilter($request);
            }

            $data = $this->patientService->getPatientList($request->only([
                'quick_search', 'search', 'start_date', 'end_date',
                'insurance_company', 'filter_source', 'filter_tags',
            ]));

            return $this->patientService->buildIndexDataTable($data);
        }
        return view('patients.index');
    }

    public function exportPatients(Request $request)
    {
        $from = $request->session()->get('from') ?: null;
        $to = $request->session()->get('to') ?: null;

        $data = $this->patientService->getExportData($from, $to);

        return Excel::download(new PatientExport($data), 'patients-' . date('Y-m-d') . '.xlsx');
    }

    public function filterPatients(Request $request)
    {
        $keyword = $request->q;

        if (!$keyword) {
            return \Response::json([]);
        }

        $result = $this->patientService->searchPatients($keyword, $request->has('full'));

        return \Response::json($result);
    }

    public function patientMedicalHistory($patientId)
    {
        return response()->json($this->patientService->getMedicalHistory($patientId));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $patientFields = $request->only([
            'full_name', 'surname', 'othername', 'gender', 'telephone',
            'dob', 'age', 'ethnicity', 'marital_status', 'education', 'blood_type',
            'email', 'phone_no', 'alternative_no', 'address', 'medication_history',
            'nin', 'profession', 'next_of_kin', 'next_of_kin_no', 'next_of_kin_address',
            'insurance_company_id', 'source_id', 'notes',
            'drug_allergies', 'systemic_diseases',
            'drug_allergies_other', 'systemic_diseases_other', 'current_medication',
            'is_pregnant', 'is_breastfeeding',
        ]);
        $nameParts = $this->patientService->validateAndParseInput($patientFields);
        $data = $this->patientService->buildPatientData($patientFields, $nameParts);
        $patient = $this->patientService->createPatient($data, $request->tags);

        if ($patient) {
            return response()->json(['message' => __('messages.patient_added_successfully'), 'status' => true]);
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
        $detail = $this->patientService->getPatientDetail($id);

        return view('patients.show', $detail);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        return response()->json($this->patientService->getPatientForEdit($id));
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
        $patientFields = $request->only([
            'full_name', 'surname', 'othername', 'gender', 'telephone',
            'dob', 'age', 'ethnicity', 'marital_status', 'education', 'blood_type',
            'email', 'phone_no', 'alternative_no', 'address', 'medication_history',
            'nin', 'profession', 'next_of_kin', 'next_of_kin_no', 'next_of_kin_address',
            'insurance_company_id', 'source_id', 'notes',
            'drug_allergies', 'systemic_diseases',
            'drug_allergies_other', 'systemic_diseases_other', 'current_medication',
            'is_pregnant', 'is_breastfeeding',
        ]);
        $nameParts = $this->patientService->validateAndParseInput($patientFields);
        $data = $this->patientService->buildPatientData($patientFields, $nameParts, isUpdate: true);
        $status = $this->patientService->updatePatient($id, $data, $request->tags);

        if ($status) {
            return response()->json(['message' => __('messages.patient_updated_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $status = $this->patientService->deletePatient($id);

        if ($status) {
            return response()->json(['message' => __('messages.patient_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }
}
