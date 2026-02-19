<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Services\LeaveTypeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class LeaveTypeController extends Controller
{
    private LeaveTypeService $leaveTypeService;

    public function __construct(LeaveTypeService $leaveTypeService)
    {
        $this->leaveTypeService = $leaveTypeService;
        $this->middleware('can:manage-leave');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $data = $this->leaveTypeService->getLeaveTypeList();
            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('created_at', function ($row) {
                    return $row->created_at ? date('Y-m-d', strtotime($row->created_at)) : '-';
                })
                ->addColumn('addedBy', function ($row) {
                    return $row->surname ?? '-';
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
        return view('leave_types.index');
    }

    public function filter(Request $request)
    {
        $name = $request->q;

        if ($name) {
            return \Response::json($this->leaveTypeService->filterLeaveTypes($name));
        }
    }

    /**
     * Get all leave types for dropdown.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAll()
    {
        return \Response::json($this->leaveTypeService->getAllLeaveTypes());
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
        Validator::make($request->all(),
            [
                'name' => 'required',
                'max_days' => 'required'
            ], [
                'name.required' => __('validation.custom.name.required'),
                'max_days.required' => __('validation.custom.max_days.required')
            ])->validate();

        $success = $this->leaveTypeService->createLeaveType($request->only(['name', 'max_days']));
        return FunctionsHelper::messageResponse(__('messages.leave_type_added_successfully'), $success);
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
     * @param $id
     * @return void
     */
    public function edit($id)
    {
        return response()->json($this->leaveTypeService->getLeaveTypeForEdit($id));
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
        Validator::make($request->all(),
            [
                'name' => 'required',
                'max_days' => 'required'
            ], [
                'name.required' => __('validation.custom.name.required'),
                'max_days.required' => __('validation.custom.max_days.required')
            ])->validate();

        $success = $this->leaveTypeService->updateLeaveType($id, $request->only(['name', 'max_days']));
        return FunctionsHelper::messageResponse(__('messages.leave_type_updated_successfully'), $success);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $success = $this->leaveTypeService->deleteLeaveType($id);
        return FunctionsHelper::messageResponse(__('messages.leave_type_deleted_successfully'), $success);
    }
}
