<?php

namespace App\Http\Controllers;

use App\TreatmentPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class TreatmentPlanController extends Controller
{
    /**
     * Display all treatment plans listing.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function listAll(Request $request)
    {
        if ($request->ajax()) {
            $data = DB::table('treatment_plans')
                ->leftJoin('patients', 'patients.id', 'treatment_plans.patient_id')
                ->leftJoin('medical_cases', 'medical_cases.id', 'treatment_plans.medical_case_id')
                ->leftJoin('users', 'users.id', 'treatment_plans._who_added')
                ->whereNull('treatment_plans.deleted_at')
                ->whereNull('patients.deleted_at')
                ->orderBy('treatment_plans.created_at', 'desc')
                ->select(
                    'treatment_plans.*',
                    'patients.patient_no',
                    DB::raw("CONCAT(patients.surname, ' ', patients.othername) as patient_name"),
                    'medical_cases.case_no',
                    'medical_cases.title as case_title',
                    DB::raw("CONCAT(users.surname, ' ', users.othername) as added_by")
                )
                ->get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('viewBtn', function ($row) {
                    return '<a href="#" onclick="viewTreatmentPlan(' . $row->id . ')" class="btn btn-info btn-sm">' . __('common.view') . '</a>';
                })
                ->addColumn('editBtn', function ($row) {
                    return '<a href="#" onclick="editTreatmentPlan(' . $row->id . ')" class="btn btn-primary btn-sm">' . __('common.edit') . '</a>';
                })
                ->addColumn('deleteBtn', function ($row) {
                    return '<a href="#" onclick="deleteTreatmentPlan(' . $row->id . ')" class="btn btn-danger btn-sm">' . __('common.delete') . '</a>';
                })
                ->addColumn('statusBadge', function ($row) {
                    $class = 'default';
                    if ($row->status == 'Planned') $class = 'info';
                    elseif ($row->status == 'In Progress') $class = 'warning';
                    elseif ($row->status == 'Completed') $class = 'success';
                    elseif ($row->status == 'Cancelled') $class = 'danger';
                    return '<span class="label label-' . $class . '">' . __('medical_cases.plan_status_' . strtolower(str_replace(' ', '_', $row->status))) . '</span>';
                })
                ->addColumn('priorityBadge', function ($row) {
                    $class = 'default';
                    if ($row->priority == 'Low') $class = 'success';
                    elseif ($row->priority == 'Medium') $class = 'info';
                    elseif ($row->priority == 'High') $class = 'warning';
                    elseif ($row->priority == 'Urgent') $class = 'danger';
                    return '<span class="label label-' . $class . '">' . __('medical_cases.priority_' . strtolower($row->priority)) . '</span>';
                })
                ->rawColumns(['viewBtn', 'editBtn', 'deleteBtn', 'statusBadge', 'priorityBadge'])
                ->make(true);
        }

        return view('treatment_plans.index');
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
            $data = DB::table('treatment_plans')
                ->leftJoin('medical_cases', 'medical_cases.id', 'treatment_plans.medical_case_id')
                ->leftJoin('users', 'users.id', 'treatment_plans._who_added')
                ->whereNull('treatment_plans.deleted_at')
                ->where('treatment_plans.patient_id', $patient_id)
                ->orderBy('treatment_plans.created_at', 'desc')
                ->select(
                    'treatment_plans.*',
                    'medical_cases.case_no',
                    'medical_cases.title as case_title',
                    DB::raw("CONCAT(users.surname, ' ', users.othername) as added_by")
                )
                ->get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('viewBtn', function ($row) {
                    return '<a href="#" onclick="viewTreatmentPlan(' . $row->id . ')" class="btn btn-info btn-sm">' . __('common.view') . '</a>';
                })
                ->addColumn('editBtn', function ($row) {
                    return '<a href="#" onclick="editTreatmentPlan(' . $row->id . ')" class="btn btn-primary btn-sm">' . __('common.edit') . '</a>';
                })
                ->addColumn('deleteBtn', function ($row) {
                    return '<a href="#" onclick="deleteTreatmentPlan(' . $row->id . ')" class="btn btn-danger btn-sm">' . __('common.delete') . '</a>';
                })
                ->addColumn('statusBadge', function ($row) {
                    $class = 'default';
                    if ($row->status == 'Planned') $class = 'info';
                    elseif ($row->status == 'In Progress') $class = 'warning';
                    elseif ($row->status == 'Completed') $class = 'success';
                    elseif ($row->status == 'Cancelled') $class = 'danger';
                    return '<span class="label label-' . $class . '">' . __('medical_cases.plan_status_' . strtolower(str_replace(' ', '_', $row->status))) . '</span>';
                })
                ->addColumn('priorityBadge', function ($row) {
                    $class = 'default';
                    if ($row->priority == 'Low') $class = 'success';
                    elseif ($row->priority == 'Medium') $class = 'info';
                    elseif ($row->priority == 'High') $class = 'warning';
                    elseif ($row->priority == 'Urgent') $class = 'danger';
                    return '<span class="label label-' . $class . '">' . __('medical_cases.priority_' . strtolower($row->priority)) . '</span>';
                })
                ->rawColumns(['viewBtn', 'editBtn', 'deleteBtn', 'statusBadge', 'priorityBadge'])
                ->make(true);
        }
    }

    /**
     * Display treatment plans for a specific medical case.
     *
     * @param Request $request
     * @param int $case_id
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function caseIndex(Request $request, $case_id)
    {
        if ($request->ajax()) {
            $data = DB::table('treatment_plans')
                ->leftJoin('users', 'users.id', 'treatment_plans._who_added')
                ->whereNull('treatment_plans.deleted_at')
                ->where('treatment_plans.medical_case_id', $case_id)
                ->orderBy('treatment_plans.created_at', 'desc')
                ->select(
                    'treatment_plans.*',
                    DB::raw("CONCAT(users.surname, ' ', users.othername) as added_by")
                )
                ->get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('viewBtn', function ($row) {
                    return '<a href="#" onclick="viewTreatmentPlan(' . $row->id . ')" class="btn btn-info btn-sm">' . __('common.view') . '</a>';
                })
                ->addColumn('editBtn', function ($row) {
                    return '<a href="#" onclick="editTreatmentPlan(' . $row->id . ')" class="btn btn-primary btn-sm">' . __('common.edit') . '</a>';
                })
                ->addColumn('deleteBtn', function ($row) {
                    return '<a href="#" onclick="deleteTreatmentPlan(' . $row->id . ')" class="btn btn-danger btn-sm">' . __('common.delete') . '</a>';
                })
                ->addColumn('statusBadge', function ($row) {
                    $class = 'default';
                    if ($row->status == 'Planned') $class = 'info';
                    elseif ($row->status == 'In Progress') $class = 'warning';
                    elseif ($row->status == 'Completed') $class = 'success';
                    elseif ($row->status == 'Cancelled') $class = 'danger';
                    return '<span class="label label-' . $class . '">' . __('medical_cases.plan_status_' . strtolower(str_replace(' ', '_', $row->status))) . '</span>';
                })
                ->addColumn('priorityBadge', function ($row) {
                    $class = 'default';
                    if ($row->priority == 'Low') $class = 'success';
                    elseif ($row->priority == 'Medium') $class = 'info';
                    elseif ($row->priority == 'High') $class = 'warning';
                    elseif ($row->priority == 'Urgent') $class = 'danger';
                    return '<span class="label label-' . $class . '">' . __('medical_cases.priority_' . strtolower($row->priority)) . '</span>';
                })
                ->rawColumns(['viewBtn', 'editBtn', 'deleteBtn', 'statusBadge', 'priorityBadge'])
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
            'plan_name' => 'required|string|max:255',
            'patient_id' => 'required|exists:patients,id',
        ], [
            'plan_name.required' => __('validation.custom.plan_name.required'),
            'patient_id.required' => __('validation.custom.patient_id.required'),
        ])->validate();

        $status = TreatmentPlan::create([
            'plan_name' => $request->plan_name,
            'description' => $request->description,
            'planned_procedures' => $request->planned_procedures,
            'estimated_cost' => $request->estimated_cost,
            'status' => $request->status ?? 'Planned',
            'priority' => $request->priority ?? 'Medium',
            'start_date' => $request->start_date,
            'target_completion_date' => $request->target_completion_date,
            'medical_case_id' => $request->medical_case_id,
            'patient_id' => $request->patient_id,
            '_who_added' => Auth::User()->id
        ]);

        if ($status) {
            return response()->json(['message' => __('medical_cases.treatment_plan_added_successfully'), 'status' => true]);
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
        $plan = TreatmentPlan::with(['patient', 'medicalCase', 'addedBy'])->findOrFail($id);
        return response()->json($plan);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $plan = TreatmentPlan::where('id', $id)->first();
        return response()->json($plan);
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
            'plan_name' => 'required|string|max:255',
        ], [
            'plan_name.required' => __('validation.custom.plan_name.required'),
        ])->validate();

        $updateData = [
            'plan_name' => $request->plan_name,
            'description' => $request->description,
            'planned_procedures' => $request->planned_procedures,
            'estimated_cost' => $request->estimated_cost,
            'actual_cost' => $request->actual_cost,
            'status' => $request->status,
            'priority' => $request->priority,
            'start_date' => $request->start_date,
            'target_completion_date' => $request->target_completion_date,
        ];

        if ($request->status == 'Completed') {
            $updateData['actual_completion_date'] = $request->actual_completion_date ?? now();
            $updateData['completion_notes'] = $request->completion_notes;
        }

        $status = TreatmentPlan::where('id', $id)->update($updateData);

        if ($status) {
            return response()->json(['message' => __('medical_cases.treatment_plan_updated_successfully'), 'status' => true]);
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
        $status = TreatmentPlan::where('id', $id)->delete();
        if ($status) {
            return response()->json(['message' => __('medical_cases.treatment_plan_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }
}
