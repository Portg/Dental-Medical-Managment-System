<?php

namespace App\Http\Controllers;

use App\Services\PatientFollowupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class PatientFollowupController extends Controller
{
    private PatientFollowupService $followupService;

    public function __construct(PatientFollowupService $followupService)
    {
        $this->followupService = $followupService;
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

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('viewBtn', function ($row) {
                    return '<a href="#" onclick="viewFollowup(' . $row->id . ')" class="btn btn-info btn-sm">' . __('common.view') . '</a>';
                })
                ->addColumn('editBtn', function ($row) {
                    return '<a href="#" onclick="editFollowup(' . $row->id . ')" class="btn btn-primary btn-sm">' . __('common.edit') . '</a>';
                })
                ->addColumn('deleteBtn', function ($row) {
                    return '<a href="#" onclick="deleteFollowup(' . $row->id . ')" class="btn btn-danger btn-sm">' . __('common.delete') . '</a>';
                })
                ->addColumn('completeBtn', function ($row) {
                    if ($row->status == 'Pending') {
                        return '<a href="#" onclick="completeFollowup(' . $row->id . ')" class="btn btn-success btn-sm">' . __('patient_followups.mark_complete') . '</a>';
                    }
                    return '';
                })
                ->addColumn('statusBadge', function ($row) {
                    $class = 'default';
                    if ($row->status == 'Pending') $class = 'warning';
                    elseif ($row->status == 'Completed') $class = 'success';
                    elseif ($row->status == 'Cancelled') $class = 'danger';
                    elseif ($row->status == 'No Response') $class = 'info';
                    return '<span class="label label-' . $class . '">' . __('patient_followups.status_' . strtolower(str_replace(' ', '_', $row->status))) . '</span>';
                })
                ->addColumn('typeBadge', function ($row) {
                    return '<span class="label label-default">' . __('patient_followups.type_' . strtolower($row->followup_type)) . '</span>';
                })
                ->addColumn('overdueFlag', function ($row) {
                    if ($row->status == 'Pending' && $row->scheduled_date < date('Y-m-d')) {
                        return '<span class="label label-danger">' . __('patient_followups.overdue') . '</span>';
                    }
                    return '';
                })
                ->rawColumns(['viewBtn', 'editBtn', 'deleteBtn', 'completeBtn', 'statusBadge', 'typeBadge', 'overdueFlag'])
                ->make(true);
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
            $data = $this->followupService->getPatientFollowups($patient_id);

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('viewBtn', function ($row) {
                    return '<a href="#" onclick="viewFollowup(' . $row->id . ')" class="btn btn-info btn-sm">' . __('common.view') . '</a>';
                })
                ->addColumn('editBtn', function ($row) {
                    return '<a href="#" onclick="editFollowup(' . $row->id . ')" class="btn btn-primary btn-sm">' . __('common.edit') . '</a>';
                })
                ->addColumn('deleteBtn', function ($row) {
                    return '<a href="#" onclick="deleteFollowup(' . $row->id . ')" class="btn btn-danger btn-sm">' . __('common.delete') . '</a>';
                })
                ->addColumn('completeBtn', function ($row) {
                    if ($row->status == 'Pending') {
                        return '<a href="#" onclick="completeFollowup(' . $row->id . ')" class="btn btn-success btn-sm">' . __('patient_followups.mark_complete') . '</a>';
                    }
                    return '';
                })
                ->addColumn('typeBadge', function ($row) {
                    $class = 'default';
                    if ($row->followup_type == 'Phone') $class = 'primary';
                    elseif ($row->followup_type == 'SMS') $class = 'info';
                    elseif ($row->followup_type == 'Email') $class = 'warning';
                    elseif ($row->followup_type == 'Visit') $class = 'success';
                    return '<span class="label label-' . $class . '">' . __('patient_followups.type_' . strtolower($row->followup_type)) . '</span>';
                })
                ->addColumn('statusBadge', function ($row) {
                    $class = 'default';
                    if ($row->status == 'Pending') $class = 'warning';
                    elseif ($row->status == 'Completed') $class = 'success';
                    elseif ($row->status == 'Cancelled') $class = 'danger';
                    elseif ($row->status == 'No Response') $class = 'info';
                    return '<span class="label label-' . $class . '">' . __('patient_followups.status_' . strtolower(str_replace(' ', '_', $row->status))) . '</span>';
                })
                ->rawColumns(['viewBtn', 'editBtn', 'deleteBtn', 'completeBtn', 'typeBadge', 'statusBadge'])
                ->make(true);
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

        $status = $this->followupService->createFollowup($request->all());

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
        return response()->json($this->followupService->getFollowupDetail($id));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        return response()->json($this->followupService->getFollowupForEdit($id));
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

        $status = $this->followupService->updateFollowup($id, $request->all());

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
        $status = $this->followupService->completeFollowup($id, $request->outcome);

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
        $status = $this->followupService->deleteFollowup($id);
        if ($status) {
            return response()->json(['message' => __('patient_followups.followup_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }
}
