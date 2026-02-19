<?php

namespace App\Http\Controllers;

use App\Http\Helper\NameHelper;
use App\Services\TreatmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class TreatmentController extends Controller
{
    private TreatmentService $treatmentService;

    public function __construct(TreatmentService $treatmentService)
    {
        $this->treatmentService = $treatmentService;
        $this->middleware('can:manage-treatments');
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param $patient_id
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function index(Request $request, $patient_id)
    {
        if ($request->ajax()) {
            $data = $this->treatmentService->getTreatmentsByPatient($patient_id);

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('editBtn', function ($row) {
                    $btn = '<a href="#" onclick="editTreatment(' . $row->id . ')" class="btn btn-primary">' . __('common.edit') . '</a>';
                    return $btn;
                })
                ->addColumn('deleteBtn', function ($row) {
                    $btn = '<a href="#" onclick="deleteTreatment(' . $row->id . ')" class="btn btn-danger">' . __('common.delete') . '</a>';
                    return $btn;
                })
                ->rawColumns(['editBtn', 'deleteBtn'])
                ->make(true);
        }
    }

    public function treatmentHistory(Request $request, $patient_id)
    {
        if ($request->ajax()) {
            $data = $this->treatmentService->getTreatmentHistory($patient_id);

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('doctor', function ($row) {
                    return NameHelper::join($row->surname, $row->othername);
                })
                ->make(true);
        }
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
            'clinical_notes' => 'required',
            'treatment' => 'required'
        ], [
            'clinical_notes.required' => __('validation.custom.clinical_notes.required'),
            'treatment.required' => __('validation.custom.treatment.required')
        ])->validate();

        $status = $this->treatmentService->createTreatment(
            $request->clinical_notes,
            $request->treatment,
            $request->appointment_id
        );

        if ($status) {
            return response()->json(['message' => __('medical_treatment.treatment_captured_successfully'), 'status' => true]);
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
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $treatment = $this->treatmentService->find($id);
        return response()->json($treatment);
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
        Validator::make($request->all(), [
            'clinical_notes' => 'required',
            'treatment' => 'required',
            'appointment_id' => 'required'
        ], [
            'clinical_notes.required' => __('validation.custom.clinical_notes.required'),
            'treatment.required' => __('validation.custom.treatment.required'),
            'appointment_id.required' => __('validation.custom.appointment_id.required')
        ])->validate();

        $status = $this->treatmentService->updateTreatment(
            $id,
            $request->clinical_notes,
            $request->treatment,
            $request->appointment_id
        );

        if ($status) {
            return response()->json(['message' => __('medical_treatment.treatment_updated_successfully'), 'status' => true]);
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
        $status = $this->treatmentService->deleteTreatment($id);
        if ($status) {
            return response()->json(['message' => __('medical_treatment.treatment_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }
}
