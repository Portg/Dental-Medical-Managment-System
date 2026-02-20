<?php

namespace App\Http\Controllers;

use App\Services\PatientFollowupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PatientFollowupController extends Controller
{
    private PatientFollowupService $followupService;

    public function __construct(PatientFollowupService $followupService)
    {
        $this->followupService = $followupService;
        $this->middleware('can:edit-patients');
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
            $data = $this->followupService->getAllFollowups();

            return $this->followupService->buildIndexDataTable($data);
        }

        $patients = $this->followupService->getAllPatients();
        return view('patient_followups.index', compact('patients'));
    }

    /**
     * Display followups for a specific patient.
     *
     * @param Request $request
     * @param int $patient_id
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function patientFollowups(Request $request, $patient_id)
    {
        if ($request->ajax()) {
            $data = $this->followupService->getPatientFollowups((int) $patient_id);

            return $this->followupService->buildPatientFollowupsDataTable($data);
        }
    }

    /**
     * Get pending followups for dashboard.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function pendingFollowups(Request $request)
    {
        $days = $request->get('days', 7);
        return response()->json($this->followupService->getPendingFollowups($days));
    }

    /**
     * Get overdue followups.
     *
     * @return \Illuminate\Http\Response
     */
    public function overdueFollowups()
    {
        return response()->json($this->followupService->getOverdueFollowups());
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
            'patient_id' => 'required|exists:patients,id',
            'followup_type' => 'required|in:Phone,SMS,Email,Visit,Other',
            'scheduled_date' => 'required|date',
            'purpose' => 'required|string|max:255',
        ], [
            'patient_id.required' => __('validation.custom.patient_id.required'),
            'followup_type.required' => __('validation.custom.followup_type.required'),
            'scheduled_date.required' => __('validation.custom.scheduled_date.required'),
            'purpose.required' => __('validation.custom.purpose.required'),
        ])->validate();

        $status = $this->followupService->createFollowup($request->only(['patient_id', 'followup_type', 'scheduled_date', 'purpose']));

        if ($status) {
            return response()->json(['message' => __('patient_followups.followup_created_successfully'), 'status' => true]);
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
        return response()->json($this->followupService->getFollowupDetail((int) $id));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        return response()->json($this->followupService->getFollowupForEdit((int) $id));
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
            'followup_type' => 'required|in:Phone,SMS,Email,Visit,Other',
            'scheduled_date' => 'required|date',
            'purpose' => 'required|string|max:255',
            'status' => 'required|in:Pending,Completed,Cancelled,No Response',
        ], [
            'followup_type.required' => __('validation.custom.followup_type.required'),
            'scheduled_date.required' => __('validation.custom.scheduled_date.required'),
            'purpose.required' => __('validation.custom.purpose.required'),
            'status.required' => __('validation.custom.status.required'),
        ])->validate();

        $status = $this->followupService->updateFollowup((int) $id, $request->only(['followup_type', 'scheduled_date', 'purpose', 'status']));

        if ($status) {
            return response()->json(['message' => __('patient_followups.followup_updated_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }

    /**
     * Mark followup as complete.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function complete(Request $request, $id)
    {
        $status = $this->followupService->completeFollowup((int) $id, $request->outcome);

        if ($status) {
            return response()->json(['message' => __('patient_followups.followup_completed_successfully'), 'status' => true]);
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
        $status = $this->followupService->deleteFollowup((int) $id);
        if ($status) {
            return response()->json(['message' => __('patient_followups.followup_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }
}
