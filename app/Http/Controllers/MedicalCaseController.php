<?php

namespace App\Http\Controllers;

use App\Http\Helper\ActionColumnHelper;
use App\MedicalCase;
use App\Patient;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class MedicalCaseController extends Controller
{
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
            $data = DB::table('medical_cases')
                ->leftJoin('patients', 'patients.id', 'medical_cases.patient_id')
                ->leftJoin('users as doctors', 'doctors.id', 'medical_cases.doctor_id')
                ->leftJoin('users as added_by', 'added_by.id', 'medical_cases._who_added')
                ->whereNull('medical_cases.deleted_at')
                ->orderBy('medical_cases.created_at', 'desc')
                ->select(
                    'medical_cases.*',
                    DB::raw(app()->getLocale() === 'zh-CN' ? "CONCAT(patients.surname, patients.othername) as patient_name" : "CONCAT(patients.surname, ' ', patients.othername) as patient_name"),
                    'patients.patient_no',
                    DB::raw(app()->getLocale() === 'zh-CN' ? "CONCAT(doctors.surname, doctors.othername) as doctor_name" : "CONCAT(doctors.surname, ' ', doctors.othername) as doctor_name"),
                    DB::raw(app()->getLocale() === 'zh-CN' ? "CONCAT(added_by.surname, added_by.othername) as added_by_name" : "CONCAT(added_by.surname, ' ', added_by.othername) as added_by_name")
                )
                ->get();

            // Apply filters
            if ($request->filled('search_term')) {
                $searchTerm = $request->search_term;
                $data = $data->filter(function ($item) use ($searchTerm) {
                    return stripos($item->case_no, $searchTerm) !== false ||
                           stripos($item->title, $searchTerm) !== false ||
                           stripos($item->patient_name, $searchTerm) !== false;
                });
            }
            if ($request->filled('status')) {
                $status = $request->status;
                $data = $data->filter(function ($item) use ($status) {
                    return $item->status == $status;
                });
            }
            if ($request->filled('doctor_id')) {
                $doctorId = $request->doctor_id;
                $data = $data->filter(function ($item) use ($doctorId) {
                    return $item->doctor_id == $doctorId;
                });
            }
            if ($request->filled('patient_id')) {
                $patientId = $request->patient_id;
                $data = $data->filter(function ($item) use ($patientId) {
                    return $item->patient_id == $patientId;
                });
            }
            if ($request->filled('start_date')) {
                $startDate = $request->start_date;
                $data = $data->filter(function ($item) use ($startDate) {
                    return $item->case_date >= $startDate;
                });
            }
            if ($request->filled('end_date')) {
                $endDate = $request->end_date;
                $data = $data->filter(function ($item) use ($endDate) {
                    return $item->case_date <= $endDate;
                });
            }

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
            $data = DB::table('medical_cases')
                ->leftJoin('users as doctors', 'doctors.id', 'medical_cases.doctor_id')
                ->whereNull('medical_cases.deleted_at')
                ->where('medical_cases.patient_id', $patient_id)
                ->orderBy('medical_cases.created_at', 'desc')
                ->select(
                    'medical_cases.*',
                    DB::raw(app()->getLocale() === 'zh-CN' ? "CONCAT(doctors.surname, doctors.othername) as doctor_name" : "CONCAT(doctors.surname, ' ', doctors.othername) as doctor_name")
                )
                ->get();

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
        // Different validation rules based on is_draft
        $isDraft = $request->input('is_draft', '1') === '1';

        $rules = [
            'patient_id' => 'required|exists:patients,id',
            'case_date' => 'required|date',
        ];

        // Only require SOAP fields for submission, not draft
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

        // Generate title from chief complaint or default
        $title = $request->chief_complaint
            ? mb_substr($request->chief_complaint, 0, 50)
            : __('medical_cases.medical_record_edit') . ' ' . $request->case_date;

        $case = MedicalCase::create([
            'case_no' => MedicalCase::CaseNumber(),
            'title' => $title,
            'chief_complaint' => $request->chief_complaint,
            'history_of_present_illness' => $request->history_of_present_illness,
            'examination' => $request->examination,
            'examination_teeth' => $request->examination_teeth ? json_decode($request->examination_teeth, true) : null,
            'auxiliary_examination' => $request->auxiliary_examination,
            'related_images' => $request->related_images ? json_decode($request->related_images, true) : null,
            'diagnosis' => $request->diagnosis,
            'diagnosis_code' => $request->diagnosis_code,
            'related_teeth' => $request->related_teeth ? json_decode($request->related_teeth, true) : null,
            'treatment' => $request->treatment,
            'treatment_services' => $request->treatment_services ? json_decode($request->treatment_services, true) : null,
            'medical_orders' => $request->medical_orders,
            'next_visit_date' => $request->next_visit_date,
            'next_visit_note' => $request->next_visit_note,
            'auto_create_followup' => $request->has('auto_create_followup'),
            'visit_type' => $request->visit_type ?? 'initial',
            'status' => 'Open',
            'is_draft' => $isDraft,
            'case_date' => $request->case_date,
            'patient_id' => $request->patient_id,
            'doctor_id' => $request->doctor_id ?? Auth::user()->id,
            '_who_added' => Auth::user()->id
        ]);

        // If not draft, lock the record
        if (!$isDraft) {
            $case->lock();

            // Create follow-up reminder if requested
            if ($request->has('auto_create_followup') && $request->next_visit_date) {
                // TODO: Create follow-up task
            }
        }

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
        $case = MedicalCase::with(['patient', 'doctor', 'addedBy'])->findOrFail($id);
        $doctors = User::where('is_doctor', 'Yes')->whereNull('deleted_at')->orderBy('surname')->get();

        return view('medical_cases.show', compact('case', 'doctors'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $case = MedicalCase::with(['patient', 'doctor'])->findOrFail($id);
        $doctors = User::where('is_doctor', 'Yes')->whereNull('deleted_at')->orderBy('surname')->get();

        // Get history records for this patient
        $historyRecords = MedicalCase::where('patient_id', $case->patient_id)
            ->where('id', '!=', $id)
            ->whereNull('deleted_at')
            ->orderBy('case_date', 'desc')
            ->limit(10)
            ->get();

        return view('medical_cases.edit', compact('case', 'doctors', 'historyRecords'));
    }

    /**
     * Get case data as JSON for AJAX requests.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function getCase($id)
    {
        $case = MedicalCase::where('id', $id)->first();
        return response()->json($case);
    }

    /**
     * Show the form for creating a new medical case.
     * If no patient_id is provided, shows patient selector first.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $doctors = User::where('is_doctor', 'Yes')->whereNull('deleted_at')->orderBy('surname')->get();
        $patients = Patient::whereNull('deleted_at')->orderBy('surname')->get();

        return view('medical_cases.edit', compact('doctors', 'patients'));
    }

    /**
     * Create a new medical case for a patient (form view).
     *
     * @param int $patient_id
     * @return \Illuminate\Http\Response
     */
    public function createForPatient($patient_id)
    {
        $patient = Patient::findOrFail($patient_id);
        $doctors = User::where('is_doctor', 'Yes')->whereNull('deleted_at')->orderBy('surname')->get();

        // Get history records for this patient
        $historyRecords = MedicalCase::where('patient_id', $patient_id)
            ->whereNull('deleted_at')
            ->orderBy('case_date', 'desc')
            ->limit(10)
            ->get();

        // Auto-determine visit type
        $hasExistingCase = MedicalCase::where('patient_id', $patient_id)->whereNull('deleted_at')->exists();

        return view('medical_cases.edit', compact('patient', 'doctors', 'historyRecords', 'hasExistingCase'));
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
        $case = MedicalCase::findOrFail($id);

        // Check if case can be modified
        if ($case->is_locked && !$case->canModifyWithoutApproval()) {
            // Require modification reason after 24 hours
            if (!$request->modification_reason) {
                return response()->json([
                    'message' => __('medical_cases.edit_requires_approval'),
                    'status' => false,
                    'require_reason' => true
                ]);
            }
        }

        // Different validation rules based on is_draft
        $isDraft = $request->input('is_draft', '1') === '1';

        $rules = [
            'patient_id' => 'required|exists:patients,id',
            'case_date' => 'required|date',
        ];

        // Only require SOAP fields for submission, not draft
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

        // Generate title from chief complaint or default
        $title = $request->chief_complaint
            ? mb_substr($request->chief_complaint, 0, 50)
            : __('medical_cases.medical_record_edit') . ' ' . $request->case_date;

        $updateData = [
            'title' => $title,
            'chief_complaint' => $request->chief_complaint,
            'history_of_present_illness' => $request->history_of_present_illness,
            'examination' => $request->examination,
            'examination_teeth' => $request->examination_teeth ? json_decode($request->examination_teeth, true) : null,
            'auxiliary_examination' => $request->auxiliary_examination,
            'related_images' => $request->related_images ? json_decode($request->related_images, true) : null,
            'diagnosis' => $request->diagnosis,
            'diagnosis_code' => $request->diagnosis_code,
            'related_teeth' => $request->related_teeth ? json_decode($request->related_teeth, true) : null,
            'treatment' => $request->treatment,
            'treatment_services' => $request->treatment_services ? json_decode($request->treatment_services, true) : null,
            'medical_orders' => $request->medical_orders,
            'next_visit_date' => $request->next_visit_date,
            'next_visit_note' => $request->next_visit_note,
            'auto_create_followup' => $request->has('auto_create_followup'),
            'visit_type' => $request->visit_type ?? 'initial',
            'case_date' => $request->case_date,
            'patient_id' => $request->patient_id,
            'doctor_id' => $request->doctor_id ?? Auth::user()->id,
            'is_draft' => $isDraft,
        ];

        if ($request->status == 'Closed') {
            $updateData['status'] = 'Closed';
            $updateData['closed_date'] = now();
            $updateData['closing_notes'] = $request->closing_notes;
        }

        // Record modification if case was already locked
        if ($case->is_locked && $request->modification_reason) {
            $case->recordModification($request->modification_reason);
        }

        $status = MedicalCase::where('id', $id)->update($updateData);

        // If transitioning from draft to submitted, lock the record
        if (!$isDraft && $case->is_draft) {
            $case->refresh();
            $case->lock();

            // Create follow-up reminder if requested
            if ($request->has('auto_create_followup') && $request->next_visit_date) {
                // TODO: Create follow-up task
            }
        }

        if ($status !== false) {
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
        $status = MedicalCase::where('id', $id)->delete();
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
        $case = MedicalCase::with(['patient', 'doctor', 'addedBy'])->findOrFail($id);

        // Get related data
        $diagnoses = \App\Diagnosis::where('medical_case_id', $id)
            ->whereNull('deleted_at')
            ->orderBy('diagnosis_date', 'desc')
            ->get();

        $treatmentPlans = \App\TreatmentPlan::where('medical_case_id', $id)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->get();

        $latestVitalSign = \App\VitalSign::where('medical_case_id', $id)
            ->whereNull('deleted_at')
            ->orderBy('recorded_at', 'desc')
            ->first();

        return view('medical_cases.print', compact('case', 'diagnoses', 'treatmentPlans', 'latestVitalSign'));
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

        // Common dental ICD-10 codes
        $icd10Codes = [
            ['id' => 'K00.0', 'text' => 'K00.0 - ' . __('odontogram.anodontia')],
            ['id' => 'K00.1', 'text' => 'K00.1 - ' . __('odontogram.supernumerary_teeth')],
            ['id' => 'K01.0', 'text' => 'K01.0 - ' . __('odontogram.embedded_teeth')],
            ['id' => 'K01.1', 'text' => 'K01.1 - ' . __('odontogram.impacted_teeth')],
            ['id' => 'K02.0', 'text' => 'K02.0 - ' . __('odontogram.caries_enamel')],
            ['id' => 'K02.1', 'text' => 'K02.1 - ' . __('odontogram.caries_dentin')],
            ['id' => 'K02.2', 'text' => 'K02.2 - ' . __('odontogram.caries_cementum')],
            ['id' => 'K02.3', 'text' => 'K02.3 - ' . __('odontogram.arrested_caries')],
            ['id' => 'K03.0', 'text' => 'K03.0 - ' . __('odontogram.attrition')],
            ['id' => 'K03.1', 'text' => 'K03.1 - ' . __('odontogram.abrasion')],
            ['id' => 'K03.2', 'text' => 'K03.2 - ' . __('odontogram.erosion')],
            ['id' => 'K04.0', 'text' => 'K04.0 - ' . __('odontogram.pulpitis')],
            ['id' => 'K04.1', 'text' => 'K04.1 - ' . __('odontogram.pulp_necrosis')],
            ['id' => 'K04.4', 'text' => 'K04.4 - ' . __('odontogram.acute_apical_periodontitis')],
            ['id' => 'K04.5', 'text' => 'K04.5 - ' . __('odontogram.chronic_apical_periodontitis')],
            ['id' => 'K04.6', 'text' => 'K04.6 - ' . __('odontogram.periapical_abscess')],
            ['id' => 'K04.7', 'text' => 'K04.7 - ' . __('odontogram.periapical_abscess_sinus')],
            ['id' => 'K05.0', 'text' => 'K05.0 - ' . __('odontogram.acute_gingivitis')],
            ['id' => 'K05.1', 'text' => 'K05.1 - ' . __('odontogram.chronic_gingivitis')],
            ['id' => 'K05.2', 'text' => 'K05.2 - ' . __('odontogram.acute_periodontitis')],
            ['id' => 'K05.3', 'text' => 'K05.3 - ' . __('odontogram.chronic_periodontitis')],
            ['id' => 'K05.4', 'text' => 'K05.4 - ' . __('odontogram.periodontosis')],
            ['id' => 'K06.0', 'text' => 'K06.0 - ' . __('odontogram.gingival_recession')],
            ['id' => 'K06.1', 'text' => 'K06.1 - ' . __('odontogram.gingival_enlargement')],
            ['id' => 'K07.3', 'text' => 'K07.3 - ' . __('odontogram.tooth_position_anomaly')],
            ['id' => 'K08.0', 'text' => 'K08.0 - ' . __('odontogram.exfoliation_systemic')],
            ['id' => 'K08.1', 'text' => 'K08.1 - ' . __('odontogram.loss_due_accident')],
            ['id' => 'K08.2', 'text' => 'K08.2 - ' . __('odontogram.loss_due_periodontal')],
            ['id' => 'K08.3', 'text' => 'K08.3 - ' . __('odontogram.retained_root')],
        ];

        // Filter based on query
        if ($query) {
            $icd10Codes = array_filter($icd10Codes, function($code) use ($query) {
                return stripos($code['id'], $query) !== false || stripos($code['text'], $query) !== false;
            });
        }

        return response()->json(array_values($icd10Codes));
    }
}
