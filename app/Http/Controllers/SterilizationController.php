<?php

namespace App\Http\Controllers;

use App\Services\SterilizationService;
use App\SterilizationKit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class SterilizationController extends Controller
{
    public function __construct(private SterilizationService $service)
    {
        $this->middleware('can:view-sterilization');
        $this->middleware('can:manage-sterilization')->only(['store', 'update', 'destroy', 'export']);
    }

    public function index(Request $request): mixed
    {
        if ($request->ajax()) {
            $filters = $request->only(['kit_id', 'status', 'date_from', 'date_to']);
            $data = $this->service->getRecordList($filters);

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('method_label', fn($row) => __('sterilization.method_' . $row->method))
                ->addColumn('status_badge', function ($row) {
                    $map = [
                        'used'     => 'badge-secondary',
                        'expired'  => 'badge-danger',
                        'expiring' => 'badge-warning',
                        'valid'    => 'badge-success',
                    ];
                    $label = __('sterilization.status_' . $row->display_status);
                    $cls   = $map[$row->display_status] ?? 'badge-light';
                    return "<span class='badge {$cls}'>{$label}</span>";
                })
                ->addColumn('action', fn($row) => $this->actionButtons($row))
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        $kits = SterilizationKit::where('is_active', true)->whereNull('deleted_at')
            ->orderBy('kit_no')->get(['id', 'kit_no', 'name']);
        return view('sterilization.index', compact('kits'));
    }

    public function store(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'kit_id'           => 'required|integer|exists:sterilization_kits,id',
            'method'           => 'required|in:autoclave,chemical,dry_heat',
            'temperature'      => 'nullable|numeric|between:0,300',
            'duration_minutes' => 'nullable|integer|min:1',
            'sterilized_at'    => 'required|date',
            'notes'            => 'nullable|string|max:500',
        ]);
        if ($v->fails()) {
            return response()->json(['status' => 0, 'message' => $v->errors()->first()]);
        }

        $data = $request->only(['kit_id', 'method', 'temperature', 'duration_minutes', 'sterilized_at', 'notes']);
        $data['operator_id'] = Auth::id();
        $this->service->createRecord($data);

        return response()->json(['status' => 1, 'message' => __('sterilization.record_created_successfully')]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'kit_id'           => 'required|integer|exists:sterilization_kits,id',
            'method'           => 'required|in:autoclave,chemical,dry_heat',
            'temperature'      => 'nullable|numeric',
            'duration_minutes' => 'nullable|integer|min:1',
            'sterilized_at'    => 'required|date',
        ]);
        if ($v->fails()) {
            return response()->json(['status' => 0, 'message' => $v->errors()->first()]);
        }
        try {
            $this->service->updateRecord($id, $request->all());
            return response()->json(['status' => 1, 'message' => __('sterilization.record_updated_successfully')]);
        } catch (\RuntimeException $e) {
            return response()->json(['status' => 0, 'message' => $e->getMessage()]);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        \App\SterilizationRecord::where('id', $id)->delete();
        return response()->json(['status' => 1, 'message' => __('sterilization.record_deleted_successfully')]);
    }

    public function edit(int $id): JsonResponse
    {
        $record = \App\SterilizationRecord::with('kit')->findOrFail($id);
        return response()->json(array_merge($record->toArray(), [
            'kit_name' => $record->kit->name ?? null,
        ]));
    }

    /** 登记使用 */
    public function use(Request $request, int $id): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'used_at'        => 'required|date',
            'patient_id'     => 'nullable|integer|exists:patients,id',
            'appointment_id' => 'nullable|integer|exists:appointments,id',
            'notes'          => 'nullable|string|max:500',
        ]);
        if ($v->fails()) {
            return response()->json(['status' => 0, 'message' => $v->errors()->first()]);
        }
        try {
            $data = $request->only(['used_at', 'patient_id', 'appointment_id', 'notes']);
            $data['used_by'] = Auth::id();
            $this->service->recordUsage($id, $data);
            return response()->json(['status' => 1, 'message' => __('sterilization.usage_recorded_successfully')]);
        } catch (\RuntimeException $e) {
            return response()->json(['status' => 0, 'message' => $e->getMessage()]);
        }
    }

    /** 撤销使用 */
    public function revokeUse(Request $request, int $usageId): JsonResponse
    {
        try {
            $this->service->revokeUsage($usageId);
            return response()->json(['status' => 1, 'message' => __('sterilization.usage_revoked_successfully')]);
        } catch (\Exception $e) {
            return response()->json(['status' => 0, 'message' => $e->getMessage()]);
        }
    }

    /** 导出 Excel */
    public function export(Request $request)
    {
        $filters = $request->only(['kit_id', 'status', 'date_from', 'date_to']);
        $records = $this->service->getRecordList($filters);

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="sterilization_records_' . now()->format('Ymd') . '.csv"',
        ];

        $callback = function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM for Excel
            fputcsv($handle, [
                __('sterilization.batch_no'),
                __('sterilization.kit_name'),
                __('sterilization.method'),
                __('sterilization.sterilized_at'),
                __('sterilization.expires_at'),
                __('sterilization.operator'),
                '状态',
            ]);
            foreach ($records as $row) {
                fputcsv($handle, [
                    $row->batch_no,
                    $row->kit_name,
                    __('sterilization.method_' . $row->method),
                    $row->sterilized_at,
                    $row->expires_at,
                    $row->operator_name,
                    __('sterilization.status_' . $row->display_status),
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function actionButtons(object $row): string
    {
        $useBtn = $row->display_status === 'valid'
            ? "<button class='btn btn-xs btn-info ml-1' onclick='logUse({$row->id})'>登记使用</button>"
            : '';
        $editBtn = $row->display_status === 'valid'
            ? "<button class='btn btn-xs btn-primary' onclick='editRecord({$row->id})'>编辑</button>"
            : '';
        $delBtn = "<button class='btn btn-xs btn-danger ml-1' onclick='deleteRecord({$row->id})'>删除</button>";
        return "<div class='btn-group sterilization-action-group'>{$editBtn}{$useBtn}{$delBtn}</div>";
    }
}
