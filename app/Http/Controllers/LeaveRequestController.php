<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Services\LeaveRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class LeaveRequestController extends Controller
{
    private LeaveRequestService $service;

    public function __construct(LeaveRequestService $service)
    {
        $this->service = $service;
        $this->middleware('can:manage-leave');
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
            $data = $this->service->getLeaveRequestsForUser(Auth::User()->id);

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('created_at', function ($row) {
                    return $row->created_at ? date('Y-m-d', strtotime($row->created_at)) : '-';
                })
                ->addColumn('editBtn', function ($row) {
                    if ($row->deleted_at == null) {
                        return '<a href="#" onclick="editRecord(' . $row->id . ')" class="btn btn-primary">' . __('common.edit') . '</a>';
                    }
                })
                ->addColumn('deleteBtn', function ($row) {
                    return '<a href="#" onclick="deleteRecord(' . $row->id . ')" class="btn btn-danger">' . __('common.delete') . '</a>';
                })
                ->rawColumns(['editBtn', 'deleteBtn'])
                ->make(true);
        }
        return view('leave_requests.index');
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
            'leave_type' => 'required',
            'start_date' => 'required',
            'duration' => 'required'
        ], [
            'leave_type.required' => __('validation.attributes.leaves.leave_type') . ' '. __('validation.required'),
            'start_date.required' => __('validation.attributes.leaves.start_date') . ' '. __('validation.required'),
            'duration.required' => __('validation.attributes.leaves.duration') . ' '.__('validation.required'),
        ])->validate();

        $success = $this->service->createLeaveRequest($request->only(['leave_type', 'start_date', 'duration']), Auth::User()->id);
        return FunctionsHelper::messageResponse(__('leaves.leave_request.sent_successfully'), $success);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param $id
     * @return void
     */
    public function edit($id)
    {
        return response()->json($this->service->getLeaveRequestForEdit((int) $id));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'leave_type' => 'required',
            'start_date' => 'required',
            'duration' => 'required'
        ], [
            'leave_type.required' => __('validation.attributes.leaves.leave_type') . ' '. __('validation.required'),
            'start_date.required' => __('validation.attributes.leaves.start_date') . ' '. __('validation.required'),
            'duration.required' => __('validation.attributes.leaves.duration') . ' '.__('validation.required'),
        ])->validate();

        $success = $this->service->updateLeaveRequest((int) $id, $request->only(['leave_type', 'start_date', 'duration']), Auth::User()->id);
        return FunctionsHelper::messageResponse(__('leaves.leave_request.updated_successfully'), $success);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return void
     */
    public function destroy($id)
    {
        $success = $this->service->deleteLeaveRequest((int) $id);
        return FunctionsHelper::messageResponse(__('leaves.leave_request.deleted_successfully'), $success);
    }
}
