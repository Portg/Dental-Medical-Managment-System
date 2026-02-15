<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Http\Helper\NameHelper;
use App\Services\LeaveRequestApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class LeaveRequestApprovalController extends Controller
{
    private LeaveRequestApprovalService $service;

    public function __construct(LeaveRequestApprovalService $service)
    {
        $this->service = $service;
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
            $data = $this->service->getAllLeaveRequests();

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('addedBy', function ($row) {
                    return NameHelper::join($row->surname, $row->othername);
                })
                ->addColumn('action', function ($row) {
                    $btn = '
                      <div class="btn-group">
                        <button class="btn blue dropdown-toggle" type="button" data-toggle="dropdown"
                                aria-expanded="false"> Action
                            <i class="fa fa-angle-down"></i>
                        </button>
                        <ul class="dropdown-menu" role="menu">
                            <li>
                                <a href="#" onclick="approveRequest(' . $row->id . ')"> ' . __('leaves.leave_requests_approval.approve_leave') . ' </a>
                            </li>
                             <li>
                                <a href="#" onclick="rejectRequest(' . $row->id . ')" > ' . __('leaves.leave_requests_approval.reject_leave') . ' </a>
                            </li>
                        </ul>
                    </div>
                    ';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('leave_requests_approval.index');
    }


    public function approveRequest($id)
    {
        $success = $this->service->approveRequest($id, Auth::User()->id);
        return FunctionsHelper::messageResponse(__('leaves.leave_requests_approval.approved_successfully'), $success);
    }

    public function rejectRequest($id)
    {
        $success = $this->service->rejectRequest($id, Auth::User()->id);
        return FunctionsHelper::messageResponse(__('leaves.leave_requests_approval.rejected_successfully'), $success);
    }
}
