<?php

namespace App\Http\Controllers;

use App\Services\LabService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class LabController extends Controller
{
    public function __construct(
        private LabService $labService,
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->labService->getLabList();

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('status_label', function ($row) {
                    if ($row->is_active) {
                        return '<span class="label label-success">' . __('lab_cases.is_active') . '</span>';
                    }
                    return '<span class="label label-default">' . __('common.inactive') . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return '
                    <div class="btn-group">
                        <button class="btn blue dropdown-toggle btn-sm" type="button" data-toggle="dropdown" aria-expanded="false">
                            ' . __('common.action') . ' <i class="fa fa-angle-down"></i>
                        </button>
                        <ul class="dropdown-menu" role="menu">
                            <li><a href="#" onclick="editLab(' . $row->id . ')">' . __('lab_cases.edit_lab') . '</a></li>
                            <li class="divider"></li>
                            <li><a href="#" onclick="deleteLab(' . $row->id . ')" class="text-danger">' . __('lab_cases.delete') . '</a></li>
                        </ul>
                    </div>';
                })
                ->rawColumns(['status_label', 'action'])
                ->make(true);
        }

        return view('labs.index');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'                => 'required|string|max:255',
            'contact'             => 'nullable|string|max:255',
            'phone'               => 'nullable|string|max:50',
            'address'             => 'nullable|string|max:500',
            'specialties'         => 'nullable|string|max:500',
            'avg_turnaround_days' => 'nullable|integer|min:1|max:365',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'status' => false]);
        }

        $this->labService->createLab($request->only([
            'name', 'contact', 'phone', 'address', 'specialties', 'avg_turnaround_days',
        ]));

        return response()->json(['message' => __('lab_cases.lab_created'), 'status' => true]);
    }

    public function show($id)
    {
        $lab = $this->labService->getLab($id);

        if (!$lab) {
            return response()->json(['message' => __('lab_cases.lab_not_found'), 'status' => false]);
        }

        return response()->json($lab);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name'                => 'required|string|max:255',
            'contact'             => 'nullable|string|max:255',
            'phone'               => 'nullable|string|max:50',
            'address'             => 'nullable|string|max:500',
            'specialties'         => 'nullable|string|max:500',
            'avg_turnaround_days' => 'nullable|integer|min:1|max:365',
            'is_active'           => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'status' => false]);
        }

        $result = $this->labService->updateLab($id, $request->only([
            'name', 'contact', 'phone', 'address', 'specialties', 'avg_turnaround_days', 'is_active',
        ]));

        if (!$result) {
            return response()->json(['message' => __('lab_cases.error_updating_lab'), 'status' => false]);
        }

        return response()->json(['message' => __('lab_cases.lab_updated'), 'status' => true]);
    }

    public function destroy($id)
    {
        $result = $this->labService->deleteLab($id);

        if (!$result) {
            return response()->json(['message' => __('lab_cases.lab_has_active_cases'), 'status' => false]);
        }

        return response()->json(['message' => __('lab_cases.lab_deleted'), 'status' => true]);
    }
}
