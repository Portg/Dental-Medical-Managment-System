<?php

namespace App\Http\Controllers;

use App\Services\VitalSignService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class VitalSignController extends Controller
{
    private VitalSignService $service;

    public function __construct(VitalSignService $service)
    {
        $this->service = $service;
        $this->middleware('can:edit-patients');
    }

    /**
     * Display a listing of the resource for a specific patient.
     *
     * @param Request $request
     * @param int $patient_id
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function index(Request $request, $patient_id)
    {
        if ($request->ajax()) {
            $data = $this->service->getByPatient($patient_id);

            return $this->buildVitalSignDatatable($data);
        }
    }

    /**
     * Display vital signs for a specific medical case.
     *
     * @param Request $request
     * @param int $case_id
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function caseIndex(Request $request, $case_id)
    {
        if ($request->ajax()) {
            $patientId = $this->service->getPatientIdFromCase($case_id);
            if (!$patientId) {
                return Datatables::of(collect([]))->make(true);
            }

            $data = $this->service->getByPatient($patientId);

            return $this->buildVitalSignDatatable($data);
        }
    }

    /**
     * Build DataTable response for vital signs (shared by index and caseIndex).
     */
    private function buildVitalSignDatatable($data)
    {
        return Datatables::of($data)
            ->addIndexColumn()
            ->addColumn('blood_pressure', function ($row) {
                if ($row->blood_pressure_systolic && $row->blood_pressure_diastolic) {
                    return $row->blood_pressure_systolic . '/' . $row->blood_pressure_diastolic . ' mmHg';
                }
                return '-';
            })
            ->addColumn('heart_rate_display', function ($row) {
                return $row->heart_rate ? $row->heart_rate . ' bpm' : '-';
            })
            ->addColumn('temperature_display', function ($row) {
                return $row->temperature ? $row->temperature . ' °C' : '-';
            })
            ->addColumn('editBtn', function ($row) {
                return '<a href="#" onclick="editVitalSign(' . $row->id . ')" class="btn btn-primary btn-sm">' . __('common.edit') . '</a>';
            })
            ->addColumn('deleteBtn', function ($row) {
                return '<a href="#" onclick="deleteVitalSign(' . $row->id . ')" class="btn btn-danger btn-sm">' . __('common.delete') . '</a>';
            })
            ->rawColumns(['editBtn', 'deleteBtn'])
            ->make(true);
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
            'recorded_at' => 'required|date',
            'patient_id' => 'required|exists:patients,id',
        ], [
            'recorded_at.required' => __('validation.custom.recorded_at.required'),
            'patient_id.required' => __('validation.custom.patient_id.required'),
        ])->validate();

        $status = $this->service->createVitalSign([
            'blood_pressure_systolic' => $request->blood_pressure_systolic,
            'blood_pressure_diastolic' => $request->blood_pressure_diastolic,
            'heart_rate' => $request->heart_rate,
            'temperature' => $request->temperature,
            'respiratory_rate' => $request->respiratory_rate,
            'oxygen_saturation' => $request->oxygen_saturation,
            'weight' => $request->weight,
            'height' => $request->height,
            'notes' => $request->notes,
            'recorded_at' => $request->recorded_at,
            'appointment_id' => $request->appointment_id,
            'patient_id' => $request->patient_id,
        ]);

        if ($status) {
            return response()->json(['message' => __('medical_cases.vital_sign_added_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $vitalSign = $this->service->getVitalSign($id);
        return response()->json($vitalSign);
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
            'recorded_at' => 'required|date',
        ], [
            'recorded_at.required' => __('validation.custom.recorded_at.required'),
        ])->validate();

        $status = $this->service->updateVitalSign($id, [
            'blood_pressure_systolic' => $request->blood_pressure_systolic,
            'blood_pressure_diastolic' => $request->blood_pressure_diastolic,
            'heart_rate' => $request->heart_rate,
            'temperature' => $request->temperature,
            'respiratory_rate' => $request->respiratory_rate,
            'oxygen_saturation' => $request->oxygen_saturation,
            'weight' => $request->weight,
            'height' => $request->height,
            'notes' => $request->notes,
            'recorded_at' => $request->recorded_at,
        ]);

        if ($status) {
            return response()->json(['message' => __('medical_cases.vital_sign_updated_successfully'), 'status' => true]);
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
        $status = $this->service->deleteVitalSign($id);
        if ($status) {
            return response()->json(['message' => __('medical_cases.vital_sign_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }

    /**
     * Get the latest vital signs for a patient.
     *
     * @param int $patient_id
     * @return \Illuminate\Http\Response
     */
    public function latest($patient_id)
    {
        $latestVitalSign = $this->service->getLatestForPatient($patient_id);

        return response()->json($latestVitalSign);
    }
}
