<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Services\ChairService;
use App\Services\BranchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class ChairController extends Controller
{
    private ChairService $chairService;
    private BranchService $branchService;

    public function __construct(ChairService $chairService, BranchService $branchService)
    {
        $this->chairService = $chairService;
        $this->branchService = $branchService;

        $this->middleware('can:view-chairs')->only(['index']);
        $this->middleware('can:create-chairs')->only(['store']);
        $this->middleware('can:edit-chairs')->only(['edit', 'update']);
        $this->middleware('can:delete-chairs')->only(['destroy']);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->chairService->getChairList();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('branch', function ($row) {
                    return $row->branch_name ?? '-';
                })
                ->addColumn('addedBy', function ($row) {
                    return $row->surname ?? '-';
                })
                ->addColumn('statusLabel', function ($row) {
                    $map = [
                        'active' => ['text' => __('chairs.status_active'), 'class' => 'text-primary'],
                        'maintenance' => ['text' => __('chairs.status_maintenance'), 'class' => 'text-warning'],
                        'offline' => ['text' => __('chairs.status_offline'), 'class' => 'text-danger'],
                    ];
                    $info = $map[$row->status] ?? ['text' => $row->status, 'class' => ''];
                    return '<span class="' . $info['class'] . '">' . $info['text'] . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $btn = '<a href="#" onclick="editRecord(' . $row->id . ')" class="btn btn-primary btn-sm">' . __('common.edit') . '</a> ';
                    $btn .= '<a href="#" onclick="deleteRecord(' . $row->id . ')" class="btn btn-danger btn-sm">' . __('common.delete') . '</a>';
                    return $btn;
                })
                ->rawColumns(['statusLabel', 'action'])
                ->make(true);
        }

        $branches = $this->branchService->getAllBranches();
        return view('chairs.index', compact('branches'));
    }

    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'chair_code' => 'required|string|max:50|unique:chairs,chair_code,NULL,id,deleted_at,NULL',
            'chair_name' => 'required|string|max:100',
            'status' => 'required|in:active,maintenance,offline',
            'branch_id' => 'nullable|exists:branches,id',
        ])->validate();

        $data = $request->only(['chair_code', 'chair_name', 'status', 'branch_id', 'notes']);
        $success = $this->chairService->createChair($data, Auth::user()->id);

        return FunctionsHelper::messageResponse(__('chairs.chair_added_successfully'), $success);
    }

    public function edit($id)
    {
        return response()->json($this->chairService->findChair($id));
    }

    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'chair_code' => 'required|string|max:50|unique:chairs,chair_code,' . $id . ',id,deleted_at,NULL',
            'chair_name' => 'required|string|max:100',
            'status' => 'required|in:active,maintenance,offline',
            'branch_id' => 'nullable|exists:branches,id',
        ])->validate();

        $data = $request->only(['chair_code', 'chair_name', 'status', 'branch_id', 'notes']);
        $success = $this->chairService->updateChair($id, $data);

        return FunctionsHelper::messageResponse(__('chairs.chair_updated_successfully'), $success);
    }

    public function destroy($id)
    {
        $success = $this->chairService->deleteChair($id);

        if (!$success) {
            return response()->json([
                'message' => __('chairs.chair_has_active_appointments'),
                'status' => 0,
            ]);
        }

        return FunctionsHelper::messageResponse(__('chairs.chair_deleted_successfully'), $success);
    }
}
