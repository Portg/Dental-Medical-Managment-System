<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Services\BranchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class BranchController extends Controller
{
    private BranchService $branchService;

    public function __construct(BranchService $branchService)
    {
        $this->branchService = $branchService;

        $this->middleware('can:view-branches')->only(['index', 'show', 'filterBranches']);
        $this->middleware('can:create-branches')->only(['create', 'store']);
        $this->middleware('can:edit-branches')->only(['edit', 'update']);
        $this->middleware('can:delete-branches')->only(['destroy']);
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

            $data = $this->branchService->getBranchList();

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('created_at', function ($row) {
                    return $row->created_at ? date('Y-m-d', strtotime($row->created_at)) : '-';
                })
                ->addColumn('addedBy', function ($row) {
                    return $row->surname;
                })
                ->addColumn('status', function ($row) {
                    if ($row->deleted_at != null) {
                        return '<span class="text-danger">' . __('branches.inactive') . '</span>';
                    } else {
                        return '<span class="text-primary">' . __('branches.active') . '</span>';
                    }
                })
                ->addColumn('editBtn', function ($row) {
                    if ($row->deleted_at == null) {
                        return '<a href="#" onclick="editRecord(' . $row->id . ')" class="btn btn-primary">' . __('common.edit') . '</a>';
                    }
                })
                ->addColumn('deleteBtn', function ($row) {
                    return '<a href="#" onclick="deleteRecord(' . $row->id . ')" class="btn btn-danger">' . __('common.delete') . '</a>';
                })
                ->rawColumns(['status', 'editBtn', 'deleteBtn'])
                ->make(true);
        }
        return view('branches.index');
    }


    public function filterBranches(Request $request)
    {
        $name = $request->q;

        if ($name) {
            return \Response::json($this->branchService->searchBranches($name));
        }
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
        Validator::make($request->all(), ['name' => 'required'])->validate();
        $success = $this->branchService->createBranch($request->name, Auth::User()->id);
        return FunctionsHelper::messageResponse(__('branches.branch_added_successfully'), $success);
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
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        return response()->json($this->branchService->findBranch((int) $id));
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
            'name' => 'required'
        ])->validate();
        $success = $this->branchService->updateBranch((int) $id, $request->name, Auth::User()->id);
        return FunctionsHelper::messageResponse(__('branches.branch_updated_successfully'), $success);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $success = $this->branchService->deleteBranch((int) $id);
        return FunctionsHelper::messageResponse(__('branches.branch_deleted_successfully'), $success);
    }
}
