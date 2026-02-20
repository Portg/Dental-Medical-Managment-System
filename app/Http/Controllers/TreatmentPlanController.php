<?php

namespace App\Http\Controllers;

use App\Services\TreatmentPlanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TreatmentPlanController extends Controller
{
    private TreatmentPlanService $treatmentPlanService;

    public function __construct(TreatmentPlanService $treatmentPlanService)
    {
        $this->treatmentPlanService = $treatmentPlanService;
        $this->middleware('can:manage-treatments');
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

            return $this->treatmentPlanService->buildDataTable($data);
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
            $data = $this->treatmentPlanService->getPatientPlans((int) $patient_id);

            return $this->treatmentPlanService->buildDataTable($data);
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
            $data = $this->treatmentPlanService->getCasePlans((int) $case_id);

            return $this->treatmentPlanService->buildDataTable($data);
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

        $status = $this->treatmentPlanService->createPlan($request->only(['plan_name', 'patient_id']));

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
        return response()->json($this->treatmentPlanService->getPlanDetail((int) $id));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        return response()->json($this->treatmentPlanService->getPlanForEdit((int) $id));
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

        $status = $this->treatmentPlanService->updatePlan((int) $id, $request->only(['plan_name']));

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
        $status = $this->treatmentPlanService->deletePlan((int) $id);
        if ($status) {
            return response()->json(['message' => __('medical_cases.treatment_plan_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }
}
