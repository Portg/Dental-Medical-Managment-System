<?php

namespace App\Http\Controllers;

use App\AccessLog;
use App\Http\Helper\FunctionsHelper;
use App\OperationLog;
use App\Patient;
use App\Services\DataMaskingService;
use App\Services\PatientService;
use Illuminate\Http\Request;
use App\Exports\PatientExport;
use App\Exports\PatientImportTemplate;
use Maatwebsite\Excel\Facades\Excel;

class PatientController extends Controller
{
    private PatientService $patientService;

    public function __construct(PatientService $patientService)
    {
        $this->patientService = $patientService;

        $this->middleware('can:view-patients')->only(['index', 'show', 'filterPatients', 'patientMedicalHistory', 'exportPatients', 'downloadImportTemplate']);
        $this->middleware('can:create-patients')->only(['create', 'store', 'importPatients']);
        $this->middleware('can:edit-patients')->only(['edit', 'update', 'updateQuickInfo', 'batchUpdateTags', 'batchUpdateGroup', 'mergePreview', 'mergePatients']);
        $this->middleware('can:delete-patients')->only(['destroy']);
        $this->middleware('can:export-patients')->only(['exportPatients']);
        $this->middleware('can:view-sensitive-data')->only(['revealPii']);
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
                'filter_group', 'filter_sidebar_tag',
                'filter_age_min', 'filter_age_max',
                'filter_spend_min', 'filter_spend_max',
                'filter_doctor',
            ]));

            return $this->patientService->buildIndexDataTable($data);
        }

        $sidebarData = $this->patientService->getGroupSidebarData();
        return view('patients.index', $sidebarData);
    }

    public function exportPatients(Request $request)
    {
        $from = $request->session()->get('from') ?: null;
        $to = $request->session()->get('to') ?: null;

        $data = $this->patientService->getExportData($from, $to);

        OperationLog::log('export', '患者管理', 'Patient', null, null, [
            'record_count' => $data->count(),
            'masking_enabled' => DataMaskingService::isExportMaskingEnabled(),
        ]);
        OperationLog::checkExportFrequency();

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
            'insurance_company_id', 'source_id', 'referred_by', 'patient_group', 'notes',
            'drug_allergies', 'systemic_diseases',
            'drug_allergies_other', 'systemic_diseases_other', 'current_medication',
            'is_pregnant', 'is_breastfeeding',
        ]);
        $nameParts = $this->patientService->validateAndParseInput($patientFields);
        $data = $this->patientService->buildPatientData($patientFields, $nameParts);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('patients/photos', 'public');
        }

        $patient = $this->patientService->createPatient($data, $request->tags);

        if ($patient) {
            // Sync kin relations
            $this->patientService->syncKinRelations($patient->id, $request->kin_relations);

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
        AccessLog::log('Patient:view_detail', 'Patient', $id);

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
        AccessLog::log('Patient:edit_form', 'Patient', $id);

        return response()->json($this->patientService->getPatientForEdit($id));
    }

    /**
     * Reveal masked PII fields for a patient.
     * Requires 'view-sensitive-data' permission.
     */
    public function revealPii($id)
    {
        AccessLog::log('Patient:reveal_pii', 'Patient', $id);

        $patient = Patient::findOrFail($id);

        return response()->json([
            'full_name' => $patient->full_name,
            'full_name_summary' => $patient->full_name,
            'full_name_detail' => $patient->full_name,
            'phone_no' => $patient->phone_no,
            'alternative_no' => $patient->alternative_no,
            'nin' => $patient->nin,
            'email' => $patient->email,
            'address' => $patient->address,
            'next_of_kin' => $patient->next_of_kin,
            'next_of_kin_no' => $patient->next_of_kin_no,
            'next_of_kin_address' => $patient->next_of_kin_address,
        ]);
    }

    /**
     * Update tags and group from the detail page left panel (AJAX).
     */
    public function updateQuickInfo(Request $request, $id)
    {
        $patient = Patient::findOrFail($id);

        if ($request->has('patient_group')) {
            $patient->patient_group = $request->input('patient_group') ?: null;
            $patient->save();
        }

        if ($request->has('tag_ids')) {
            $tagIds = array_filter((array) $request->input('tag_ids', []));
            $patient->patientTags()->sync($tagIds);
        }

        return response()->json(['status' => 1, 'message' => __('common.saved_successfully')]);
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
            'insurance_company_id', 'source_id', 'referred_by', 'patient_group', 'notes',
            'drug_allergies', 'systemic_diseases',
            'drug_allergies_other', 'systemic_diseases_other', 'current_medication',
            'is_pregnant', 'is_breastfeeding',
        ]);
        $nameParts = $this->patientService->validateAndParseInput($patientFields);
        $data = $this->patientService->buildPatientData($patientFields, $nameParts, isUpdate: true);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('patients/photos', 'public');
        }

        $status = $this->patientService->updatePatient($id, $data, $request->tags);

        if ($status) {
            // Sync kin relations
            $this->patientService->syncKinRelations($id, $request->kin_relations);

            return response()->json(['message' => __('messages.patient_updated_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }

    /**
     * Batch update tags for multiple patients.
     */
    public function batchUpdateTags(Request $request)
    {
        $validated = $request->validate([
            'patient_ids' => 'required|array|min:1',
            'patient_ids.*' => 'integer|exists:patients,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:patient_tags,id',
            'mode' => 'required|in:append,replace',
        ]);
        $count = $this->patientService->batchUpdateTags(
            $validated['patient_ids'],
            $validated['tag_ids'] ?? [],
            $validated['mode']
        );
        return response()->json(['status' => 1, 'message' => __('patient.batch_tags_updated', ['count' => $count])]);
    }

    /**
     * Batch update group for multiple patients.
     */
    public function batchUpdateGroup(Request $request)
    {
        $validated = $request->validate([
            'patient_ids' => 'required|array|min:1',
            'patient_ids.*' => 'integer|exists:patients,id',
            'group_code' => 'nullable|string',
        ]);
        $count = $this->patientService->batchUpdateGroup(
            $validated['patient_ids'],
            $validated['group_code'] ?? null
        );
        return response()->json(['status' => 1, 'message' => __('patient.batch_group_updated', ['count' => $count])]);
    }

    /**
     * Merge preview: return comparison data for two patients.
     */
    public function mergePreview(Request $request)
    {
        $validated = $request->validate([
            'patient_a' => 'required|integer|exists:patients,id',
            'patient_b' => 'required|integer|exists:patients,id|different:patient_a',
        ]);

        $preview = $this->patientService->getMergePreview($validated['patient_a'], $validated['patient_b']);

        return response()->json(['status' => 1, 'data' => $preview]);
    }

    /**
     * Execute patient merge.
     */
    public function mergePatients(Request $request)
    {
        $validated = $request->validate([
            'primary_id' => 'required|integer|exists:patients,id',
            'secondary_id' => 'required|integer|exists:patients,id|different:primary_id',
            'field_overrides' => 'nullable|array',
        ]);

        try {
            $this->patientService->mergePatients(
                $validated['primary_id'],
                $validated['secondary_id'],
                $validated['field_overrides'] ?? []
            );

            return response()->json(['status' => 1, 'message' => __('patient.merge_success')]);
        } catch (\Exception $e) {
            return response()->json(['status' => 0, 'message' => __('patient.merge_failed')]);
        }
    }

    /**
     * Download patient import template.
     */
    public function downloadImportTemplate()
    {
        return Excel::download(new PatientImportTemplate(), __('patient.import_template_filename') . '.xlsx');
    }

    /**
     * Import patients from uploaded Excel file.
     */
    public function importPatients(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ], [
            'file.required' => __('patient.import_file_required'),
            'file.mimes' => __('patient.import_supported_formats'),
            'file.max' => __('patient.import_file_too_large'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()->first(),
            ]);
        }

        try {
            $results = $this->patientService->importPatients($request->file('file'));

            return response()->json([
                'status' => 1,
                'message' => __('patient.import_success_count', ['count' => $results['success']]),
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => __('messages.error_occurred') . ': ' . $e->getMessage(),
            ]);
        }
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
