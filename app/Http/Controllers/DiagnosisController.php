<?php

namespace App\Http\Controllers;

use App\Services\DiagnosisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class DiagnosisController extends Controller
{
    private DiagnosisService $service;

    public function __construct(DiagnosisService $service)
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

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('editBtn', function ($row) {
                    return '<a href="#" onclick="editDiagnosis(' . $row->id . ')" class="btn btn-primary btn-sm">' . __('common.edit') . '</a>';
                })
                ->addColumn('deleteBtn', function ($row) {
                    return '<a href="#" onclick="deleteDiagnosis(' . $row->id . ')" class="btn btn-danger btn-sm">' . __('common.delete') . '</a>';
                })
                ->addColumn('statusBadge', function ($row) {
                    $class = 'default';
                    if ($row->status == 'Active') $class = 'warning';
                    elseif ($row->status == 'Resolved') $class = 'success';
                    elseif ($row->status == 'Chronic') $class = 'danger';
                    return '<span class="label label-' . $class . '">' . __('medical_cases.diagnosis_status_' . strtolower($row->status)) . '</span>';
                })
                ->addColumn('severityBadge', function ($row) {
                    if (!$row->severity) return '-';
                    $class = 'default';
                    if ($row->severity == 'Mild') $class = 'success';
                    elseif ($row->severity == 'Moderate') $class = 'warning';
                    elseif ($row->severity == 'Severe') $class = 'danger';
                    return '<span class="label label-' . $class . '">' . __('medical_cases.severity_' . strtolower($row->severity)) . '</span>';
                })
                ->rawColumns(['editBtn', 'deleteBtn', 'statusBadge', 'severityBadge'])
                ->make(true);
        }
    }

    /**
     * Display diagnoses for a specific medical case.
     *
     * @param Request $request
     * @param int $case_id
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function caseIndex(Request $request, $case_id)
    {
        if ($request->ajax()) {
            $data = $this->service->getByCase($case_id);

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('editBtn', function ($row) {
                    return '<a href="#" onclick="editDiagnosis(' . $row->id . ')" class="btn btn-primary btn-sm">' . __('common.edit') . '</a>';
                })
                ->addColumn('deleteBtn', function ($row) {
                    return '<a href="#" onclick="deleteDiagnosis(' . $row->id . ')" class="btn btn-danger btn-sm">' . __('common.delete') . '</a>';
                })
                ->addColumn('statusBadge', function ($row) {
                    $class = 'default';
                    if ($row->status == 'Active') $class = 'warning';
                    elseif ($row->status == 'Resolved') $class = 'success';
                    elseif ($row->status == 'Chronic') $class = 'danger';
                    return '<span class="label label-' . $class . '">' . __('medical_cases.diagnosis_status_' . strtolower($row->status)) . '</span>';
                })
                ->addColumn('severityBadge', function ($row) {
                    if (!$row->severity) return '-';
                    $class = 'default';
                    if ($row->severity == 'Mild') $class = 'success';
                    elseif ($row->severity == 'Moderate') $class = 'warning';
                    elseif ($row->severity == 'Severe') $class = 'danger';
                    return '<span class="label label-' . $class . '">' . __('medical_cases.severity_' . strtolower($row->severity)) . '</span>';
                })
                ->rawColumns(['editBtn', 'deleteBtn', 'statusBadge', 'severityBadge'])
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
        Validator::make($request->all(), [
            'diagnosis_name' => 'required|string|max:255',
            'diagnosis_date' => 'required|date',
            'patient_id' => 'required|exists:patients,id',
        ], [
            'diagnosis_name.required' => __('validation.custom.diagnosis_name.required'),
            'diagnosis_date.required' => __('validation.custom.diagnosis_date.required'),
            'patient_id.required' => __('validation.custom.patient_id.required'),
        ])->validate();

        $status = $this->service->createDiagnosis($request->only(['diagnosis_name', 'diagnosis_date', 'patient_id']));

        if ($status) {
            return response()->json(['message' => __('medical_cases.diagnosis_added_successfully'), 'status' => true]);
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
        $diagnosis = $this->service->find($id);
        return response()->json($diagnosis);
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
            'diagnosis_name' => 'required|string|max:255',
            'diagnosis_date' => 'required|date',
        ], [
            'diagnosis_name.required' => __('validation.custom.diagnosis_name.required'),
            'diagnosis_date.required' => __('validation.custom.diagnosis_date.required'),
        ])->validate();

        $status = $this->service->updateDiagnosis($id, $request->only(['diagnosis_name', 'diagnosis_date']));

        if ($status) {
            return response()->json(['message' => __('medical_cases.diagnosis_updated_successfully'), 'status' => true]);
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
        $status = $this->service->deleteDiagnosis($id);
        if ($status) {
            return response()->json(['message' => __('medical_cases.diagnosis_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }
}
