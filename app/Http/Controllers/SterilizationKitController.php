<?php

namespace App\Http\Controllers;

use App\Services\SterilizationKitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class SterilizationKitController extends Controller
{
    public function __construct(private SterilizationKitService $service)
    {
        $this->middleware('can:view-sterilization');
        $this->middleware('can:manage-sterilization')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request): mixed
    {
        if ($request->ajax()) {
            $data = $this->service->getAll();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('instruments_count', fn($row) => $row->instruments->count())
                ->addColumn('action', fn($row) => $this->actionButtons($row->id))
                ->rawColumns(['action'])
                ->make(true);
        }
        return response()->json(['status' => 1, 'data' => $this->service->getAll()]);
    }

    public function store(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'kit_no'                        => 'required|string|max:50|unique:sterilization_kits,kit_no',
            'name'                          => 'required|string|max:100',
            'instruments'                   => 'array',
            'instruments.*.instrument_name' => 'required|string|max:100',
            'instruments.*.quantity'        => 'integer|min:1',
        ]);
        if ($v->fails()) {
            return response()->json(['status' => 0, 'message' => $v->errors()->first()]);
        }
        $this->service->create($request->all());
        return response()->json(['status' => 1, 'message' => __('sterilization.kit_created_successfully')]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'kit_no'                        => "required|string|max:50|unique:sterilization_kits,kit_no,{$id}",
            'name'                          => 'required|string|max:100',
            'instruments'                   => 'array',
            'instruments.*.instrument_name' => 'required|string|max:100',
        ]);
        if ($v->fails()) {
            return response()->json(['status' => 0, 'message' => $v->errors()->first()]);
        }
        $this->service->update($id, $request->all());
        return response()->json(['status' => 1, 'message' => __('sterilization.kit_updated_successfully')]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json(['status' => 1, 'message' => __('sterilization.kit_deleted_successfully')]);
    }

    public function edit(int $id): JsonResponse
    {
        $kit = \App\SterilizationKit::with('instruments')->findOrFail($id);
        return response()->json($kit);
    }

    private function actionButtons(int $id): string
    {
        return <<<HTML
        <div class="btn-group">
            <button class="btn btn-xs btn-primary" onclick="editKit({$id})">编辑</button>
            <button class="btn btn-xs btn-danger ml-1" onclick="deleteKit({$id})">删除</button>
        </div>
        HTML;
    }
}
