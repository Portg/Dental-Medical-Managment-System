<?php

namespace App\Http\Controllers;

use App\Services\TreatmentPlanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class TreatmentPlanController extends Controller
{
    private TreatmentPlanService $treatmentPlanService;

    public function __construct(TreatmentPlanService $treatmentPlanService)
    {
        $this->treatmentPlanService = $treatmentPlanService;
    }

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
            $data = $this->treatmentPlanService->getAllPlans();

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
            $data = $this->treatmentPlanService->getPatientPlans($patient_id);

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
            $data = $this->treatmentPlanService->getCasePlans($case_id);

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

        $status = $this->treatmentPlanService->createPlan($request->all());

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
        return response()->json($this->treatmentPlanService->getPlanDetail($id));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        return response()->json($this->treatmentPlanService->getPlanForEdit($id));
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

        $status = $this->treatmentPlanService->updatePlan($id, $request->all());

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
        $status = $this->treatmentPlanService->deletePlan($id);
        if ($status) {
            return response()->json(['message' => __('medical_cases.treatment_plan_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }
}
