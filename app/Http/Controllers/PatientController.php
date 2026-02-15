<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Http\Helper\NameHelper;
use App\Services\PatientService;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Exports\PatientExport;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class PatientController extends Controller
{
    private PatientService $patientService;

    public function __construct(PatientService $patientService)
    {
        $this->patientService = $patientService;
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

            $data = $this->patientService->getPatientList($request->all());

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('full_name', function ($row) {
                    return NameHelper::join($row->surname, $row->othername);
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
                    return '<a href="' . url('/medical-history/' . $row->id) . '" class="btn btn-success">' . __('patient.medical_history') . '</a>';
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
                    return '
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
                })
                ->rawColumns(['patient_no', 'medical_insurance', 'Medical_History', 'status', 'action'])
                ->make(true);
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
        $nameParts = $this->patientService->validateAndParseInput($request->all());
        $data = $this->patientService->buildPatientData($request->all(), $nameParts);
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
        $nameParts = $this->patientService->validateAndParseInput($request->all());
        $data = $this->patientService->buildPatientData($request->all(), $nameParts, isUpdate: true);
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
