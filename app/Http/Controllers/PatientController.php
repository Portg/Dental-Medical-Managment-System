<?php

namespace App\Http\Controllers;

use App\ChronicDisease;
use App\Http\Helper\FunctionsHelper;
use App\InsuranceCompany;
use App\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use ExcelReport;

class PatientController extends Controller
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
            $query = DB::table('patients')
                ->leftJoin('insurance_companies', 'insurance_companies.id', 'patients.insurance_company_id')
                ->leftJoin('patient_sources', 'patient_sources.id', 'patients.source_id')
                ->leftJoin('users', 'users.id', 'patients._who_added')
                ->whereNull('patients.deleted_at')
                ->select('patients.*', 'patients.surname', 'patients.othername',
                    'insurance_companies.name', 'patient_sources.name as source_name', 'users.surname as addedBy');

            // Quick search filter (from custom search box)
            if (!empty($request->get('quick_search'))) {
                $search = $request->get('quick_search');
                $query->where(function($q) use ($search) {
                    $q->where('patients.surname', 'like', '%' . $search . '%')
                      ->orWhere('patients.othername', 'like', '%' . $search . '%')
                      ->orWhere('patients.phone_no', 'like', '%' . $search . '%')
                      ->orWhere('patients.patient_no', 'like', '%' . $search . '%');
                });
            }

            // Search filter (DataTables default search)
            if (!empty($request->get('search'))) {
                $search = $request->get('search');
                if (is_array($search) && !empty($search['value'])) {
                    $searchValue = $search['value'];
                    $query->where(function($q) use ($searchValue) {
                        $q->where('patients.surname', 'like', '%' . $searchValue . '%')
                          ->orWhere('patients.othername', 'like', '%' . $searchValue . '%')
                          ->orWhere('patients.phone_no', 'like', '%' . $searchValue . '%');
                    });
                }
            }

            // Date range filter
            if (!empty($request->start_date) && !empty($request->end_date)) {
                FunctionsHelper::storeDateFilter($request);
                $query->whereBetween(DB::raw('DATE(patients.created_at)'), array($request->start_date, $request->end_date));
            }

            // Insurance company filter
            if (!empty($request->insurance_company)) {
                $query->where('patients.insurance_company_id', $request->insurance_company);
            }

            // Source filter
            if (!empty($request->filter_source)) {
                $query->where('patients.source_id', $request->filter_source);
            }

            // Tags filter
            if (!empty($request->filter_tags) && is_array($request->filter_tags)) {
                $tagIds = $request->filter_tags;
                $query->whereIn('patients.id', function($subquery) use ($tagIds) {
                    $subquery->select('patient_id')
                        ->from('patient_tag_pivot')
                        ->whereIn('tag_id', $tagIds);
                });
            }

            $data = $query->orderBy('patients.id', 'desc')->get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('full_name', function ($row) {
                    return $row->surname . ' ' . $row->othername;
                })
                ->addColumn('gender', function ($row) {
                    if ($row->gender == 'Male') {
                        return __('patient.male');
                    } elseif ($row->gender == 'Female') {
                        return __('patient.female');
                    }
                    return $row->gender ?: '';
                })
                ->addColumn('patient_no', function ($row) {
                    return '<a href="#"> ' . $row->patient_no . '</a>';
                })
                ->addColumn('tags_badges', function ($row) {
                    $tags = DB::table('patient_tag_pivot')
                        ->join('patient_tags', 'patient_tags.id', 'patient_tag_pivot.tag_id')
                        ->where('patient_tag_pivot.patient_id', $row->id)
                        ->select('patient_tags.name')
                        ->pluck('name')
                        ->toArray();
                    return count($tags) > 0 ? implode(', ', $tags) : '';
                })
                ->addColumn('source_name', function ($row) {
                    return $row->source_name ?: '';
                })
                ->addColumn('medical_insurance', function ($row) {
                    if ($row->has_insurance == "Yes" && $row->insurance_company_id != null) {
                        return $row->name;
                    } elseif ($row->has_insurance == "Yes") {
                        return __('common.yes');
                    } else {
                        return __('common.no');
                    }
                })
                ->addColumn('Medical_History', function ($row) {
                    $btn = '<a href="' . url('/medical-history/' . $row->id) . '" class="btn btn-success">' . __('patient.medical_history') . '</a>';
                    return $btn;
                })
                ->addColumn('addedBy', function ($row) {
                    return $row->addedBy;
                })
                ->addColumn('status', function ($row) {
                    if ($row->deleted_at != null) {
                        return '<span class="text-danger">' . __('common.inactive') . '</span>';
                    } else {
                        return '<span class="text-primary">' . __('common.active') . '</span>';
                    }
                })
                ->addColumn('action', function ($row) {
                    $btn = '
                      <div class="btn-group">
                        <button class="btn blue dropdown-toggle" type="button" data-toggle="dropdown"
                                aria-expanded="false"> ' . __('common.action') . '
                        </button>
                        <ul class="dropdown-menu" role="menu">
                            <li>
                                <a href="' . url('patients/' . $row->id) . '">' . __('patient.patient_details') . '</a>
                            </li>
                            <li>
                                <a href="#" onclick="editRecord(' . $row->id . ')">' . __('patient.patient_profile') . '</a>
                            </li>
                             <li>
                               <a href="#" onclick="getPatientMedicalHistory(' . $row->id . ')" >' . __('patient.patient_history') . '</a>
                            </li>
                             <li>
                               <a href="#" onclick="deleteRecord(' . $row->id . ')" >' . __('patient.delete_patient') . '</a>
                            </li>
                        </ul>
                    </div>
                    ';
                    return $btn;
                })
                ->rawColumns(['patient_no', 'medical_insurance', 'Medical_History', 'status', 'action'])
                ->make(true);
        }
        return view('patients.index');
    }


    public function exportPatients(Request $request)
    {
        if ($request->session()->get('from') != '' && $request->session()->get('to') != '') {
            $queryBuilder = DB::table('patients')
                ->leftJoin('insurance_companies', 'insurance_companies.id', 'patients.insurance_company_id')
                ->whereBetween(DB::raw('DATE(patients.created_at)'), array($request->session()->get('from'),
                    $request->session()->get('to')))
                ->select('patients.*', 'insurance_companies.name as insurance_company')
                ->orderBy('created_at', 'ASC');
        } else {
            $queryBuilder = DB::table('patients')
                ->leftJoin('insurance_companies', 'insurance_companies.id', 'patients.insurance_company_id')
                ->select('patients.*', 'insurance_companies.name as insurance_company')
                ->orderBy('created_at', 'ASC');
        }

        $columns = ['surname', 'othername', 'gender', 'dob', 'phone_no', 'alternative_no', 'address', 'profession', 'next_of_kin', 'has_insurance', 'insurance_company'];

        return ExcelReport::of(null,
            [
                'Patients Registered Report ' => "From:   " . $request->session()->get('from') . "    To:    " .
                    $request->session()
                        ->get('to'),
            ], $queryBuilder, $columns)
            ->simple()
            ->download('patients' . date('Y-m-d H:m:s'));
    }

    public function filterPatients(Request $request)
    {
        $data = [];
        $name = $request->q;
        $fullData = $request->has('full'); // Return full patient data if 'full' parameter is set

        if ($name) {
            $search = $name;
            $data = Patient::where(function($query) use ($search) {
                    $query->where('surname', 'LIKE', "%$search%")
                        ->orWhere('othername', 'LIKE', "%$search%")
                        ->orWhere('phone_no', 'LIKE', "%$search%")
                        ->orWhere('email', 'LIKE', "%$search%")
                        ->orWhere('patient_no', 'LIKE', "%$search%");
                })
                ->whereNull('deleted_at')
                ->limit(20)
                ->get();

            if ($fullData) {
                // Return full patient data for medical case creation
                return \Response::json($data);
            }

            // Return simple format for other uses
            $formatted_tags = [];
            foreach ($data as $tag) {
                $formatted_tags[] = ['id' => $tag->id, 'text' => $tag->surname . " " . $tag->othername];
            }
            return \Response::json($formatted_tags);
        }

        return \Response::json([]);
    }

    public function patientMedicalHistory($patientId)
    {
        $medicalHistory = DB::table('treatments')
            ->leftJoin('appointments', 'appointments.id', 'treatments.appointment_id')
            ->leftJoin('users', 'users.id', 'treatments._who_added')
            ->whereNull('treatments.deleted_at')
            ->where('appointments.patient_id', $patientId)
            ->orderBy('treatments.updated_at', 'desc')
            ->select('treatments.id', 'clinical_notes', 'treatment', 'treatments.created_at')
            ->get();
        $patient = Patient::findOrfail($patientId);
        return Response()->json(['patientInfor' => $patient, 'treatmentHistory' => $medicalHistory]);
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
        Validator::make($request->all(), [
            'surname' => 'required',
            'othername' => 'required',
            'gender' => 'required',
            'telephone' => 'required'
        ], [
            'surname.required' => __('validation.required', ['attribute' => __('patient.surname')]),
            'othername.required' => __('validation.required', ['attribute' => __('patient.othername')]),
            'gender.required' => __('validation.required', ['attribute' => __('patient.gender')]),
            'telephone.required' => __('validation.required', ['attribute' => __('patient.phone_no')])
        ])->validate();

        // Build data array, only include fields with values
        $data = [
            'patient_no' => Patient::PatientNumber(),
            'surname' => $request->surname,
            'othername' => $request->othername,
            'gender' => $request->gender,
            'telephone' => $request->telephone,
            '_who_added' => Auth::User()->id
        ];

        // Optional fields - only include if they have values
        // Form field 'dob' maps to database column 'date_of_birth'
        if ($request->filled('dob')) {
            $data['date_of_birth'] = $request->dob;
        }
        if ($request->filled('age')) {
            $data['age'] = $request->age;
        }
        if ($request->filled('ethnicity')) {
            $data['ethnicity'] = $request->ethnicity;
        }
        if ($request->filled('marital_status')) {
            $data['marital_status'] = $request->marital_status;
        }
        if ($request->filled('education')) {
            $data['education'] = $request->education;
        }
        if ($request->filled('blood_type')) {
            $data['blood_type'] = $request->blood_type;
        }
        if ($request->filled('email')) {
            $data['email'] = $request->email;
        }
        if ($request->filled('phone_no')) {
            $data['phone_no'] = $request->phone_no;
        }
        if ($request->filled('alternative_no')) {
            $data['alternative_no'] = $request->alternative_no;
        }
        if ($request->filled('address')) {
            $data['address'] = $request->address;
        }
        if ($request->filled('medication_history')) {
            $data['medication_history'] = $request->medication_history;
        }
        if ($request->filled('nin')) {
            $data['nin'] = $request->nin;
        }
        if ($request->filled('profession')) {
            $data['profession'] = $request->profession;
        }
        if ($request->filled('next_of_kin')) {
            $data['next_of_kin'] = $request->next_of_kin;
        }
        if ($request->filled('next_of_kin_no')) {
            $data['next_of_kin_no'] = $request->next_of_kin_no;
        }
        if ($request->filled('next_of_kin_address')) {
            $data['next_of_kin_address'] = $request->next_of_kin_address;
        }
        if ($request->filled('insurance_company_id')) {
            $data['insurance_company_id'] = $request->insurance_company_id;
        }
        if ($request->filled('source_id')) {
            $data['source_id'] = $request->source_id;
        }
        if ($request->filled('notes')) {
            $data['notes'] = $request->notes;
        }

        // Health info fields
        if ($request->has('drug_allergies')) {
            $data['drug_allergies'] = $request->drug_allergies;
        }
        if ($request->filled('drug_allergies_other')) {
            $data['drug_allergies_other'] = $request->drug_allergies_other;
        }
        if ($request->has('systemic_diseases')) {
            $data['systemic_diseases'] = $request->systemic_diseases;
        }
        if ($request->filled('systemic_diseases_other')) {
            $data['systemic_diseases_other'] = $request->systemic_diseases_other;
        }
        if ($request->filled('current_medication')) {
            $data['current_medication'] = $request->current_medication;
        }
        $data['is_pregnant'] = $request->has('is_pregnant') ? true : false;
        $data['is_breastfeeding'] = $request->has('is_breastfeeding') ? true : false;

        $patient = Patient::create($data);

        // Sync tags if provided
        if ($patient && $request->has('tags')) {
            $patient->patientTags()->sync($request->tags);
        }

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
        $patient = Patient::with(['InsuranceCompany'])->findOrFail($id);

        // Get related data counts for tabs
        $appointmentsCount = DB::table('appointments')
            ->where('patient_id', $id)
            ->whereNull('deleted_at')
            ->count();

        $medicalCasesCount = DB::table('medical_cases')
            ->where('patient_id', $id)
            ->whereNull('deleted_at')
            ->count();

        $imagesCount = DB::table('patient_images')
            ->where('patient_id', $id)
            ->whereNull('deleted_at')
            ->count();

        $followupsCount = DB::table('patient_followups')
            ->where('patient_id', $id)
            ->whereNull('deleted_at')
            ->count();

        $invoicesCount = DB::table('invoices')
            ->leftJoin('appointments', 'appointments.id', 'invoices.appointment_id')
            ->where('appointments.patient_id', $id)
            ->whereNull('invoices.deleted_at')
            ->count();

        return view('patients.show', compact(
            'patient',
            'appointmentsCount',
            'medicalCasesCount',
            'imagesCount',
            'followupsCount',
            'invoicesCount'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Patient $patient
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $company = '';
        $patient = Patient::with(['patientTags', 'source'])->where('id', $id)->first();
        if ($patient->insurance_company_id != null) {
            //now get the insurance company
            $row = InsuranceCompany::where('id', $patient->insurance_company_id)->first();
            $company = $row->name;
        } else {
            $company = '';
        }
        // Return source as object for select2
        $source = $patient->source ? ['id' => $patient->source->id, 'name' => $patient->source->name] : null;
        // Return tags as objects for select2
        $tags = $patient->patientTags->map(function($tag) {
            return ['id' => $tag->id, 'name' => $tag->name];
        });
        return response()->json([
            'patient' => $patient,
            'company' => $company,
            'source' => $source,
            'tags' => $tags
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Patient $patient
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'surname' => 'required',
            'othername' => 'required',
            'gender' => 'required',
            'telephone' => 'required'
        ], [
            'surname.required' => __('validation.required', ['attribute' => __('patient.surname')]),
            'othername.required' => __('validation.required', ['attribute' => __('patient.othername')]),
            'gender.required' => __('validation.required', ['attribute' => __('patient.gender')]),
            'telephone.required' => __('validation.required', ['attribute' => __('patient.phone_no')])
        ])->validate();

        // Build data array with required fields
        $data = [
            'surname' => $request->surname,
            'othername' => $request->othername,
            'gender' => $request->gender,
            'telephone' => $request->telephone,
        ];

        // Optional fields - Form field 'dob' maps to database column 'date_of_birth'
        // For update, we set null if empty to allow clearing values
        $data['date_of_birth'] = $request->filled('dob') ? $request->dob : null;
        $data['age'] = $request->filled('age') ? $request->age : null;
        $data['ethnicity'] = $request->filled('ethnicity') ? $request->ethnicity : null;
        $data['marital_status'] = $request->filled('marital_status') ? $request->marital_status : null;
        $data['education'] = $request->filled('education') ? $request->education : null;
        $data['blood_type'] = $request->filled('blood_type') ? $request->blood_type : null;
        $data['email'] = $request->filled('email') ? $request->email : null;
        $data['phone_no'] = $request->filled('phone_no') ? $request->phone_no : null;
        $data['alternative_no'] = $request->filled('alternative_no') ? $request->alternative_no : null;
        $data['address'] = $request->filled('address') ? $request->address : null;
        $data['medication_history'] = $request->filled('medication_history') ? $request->medication_history : null;
        $data['nin'] = $request->filled('nin') ? $request->nin : null;
        $data['profession'] = $request->filled('profession') ? $request->profession : null;
        $data['next_of_kin'] = $request->filled('next_of_kin') ? $request->next_of_kin : null;
        $data['next_of_kin_no'] = $request->filled('next_of_kin_no') ? $request->next_of_kin_no : null;
        $data['next_of_kin_address'] = $request->filled('next_of_kin_address') ? $request->next_of_kin_address : null;
        $data['insurance_company_id'] = $request->filled('insurance_company_id') ? $request->insurance_company_id : null;
        $data['source_id'] = $request->filled('source_id') ? $request->source_id : null;
        $data['notes'] = $request->filled('notes') ? $request->notes : null;

        // Health info fields
        $data['drug_allergies'] = $request->has('drug_allergies') ? $request->drug_allergies : [];
        $data['drug_allergies_other'] = $request->filled('drug_allergies_other') ? $request->drug_allergies_other : null;
        $data['systemic_diseases'] = $request->has('systemic_diseases') ? $request->systemic_diseases : [];
        $data['systemic_diseases_other'] = $request->filled('systemic_diseases_other') ? $request->systemic_diseases_other : null;
        $data['current_medication'] = $request->filled('current_medication') ? $request->current_medication : null;
        $data['is_pregnant'] = $request->has('is_pregnant') ? true : false;
        $data['is_breastfeeding'] = $request->has('is_breastfeeding') ? true : false;

        $status = Patient::where('id', $id)->update($data);

        // Sync tags if provided
        $patient = Patient::find($id);
        if ($patient) {
            $patient->patientTags()->sync($request->tags ?? []);
        }

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
        $status = Patient::where('id', $id)->delete();
        if ($status) {
            return response()->json(['message' => __('messages.patient_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);

    }

}