<?php

namespace App\Http\Controllers;

use App\Services\ClinicAffairsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class ClinicAffairsController extends Controller
{
    public function __construct(private ClinicAffairsService $service)
    {
        $this->middleware('can:view-clinic-affairs');
        $this->middleware('can:manage-clinic-affairs')->except([
            'disinfection', 'equipment', 'waste',
            'disinfectionData', 'equipmentData', 'wasteData',
            'exportDisinfection', 'exportEquipment', 'exportWaste',
        ]);
    }

    public function disinfection()
    {
        return $this->page('disinfection');
    }

    public function equipment()
    {
        return $this->page('equipment');
    }

    public function waste()
    {
        return $this->page('waste');
    }

    public function disinfectionData(Request $request)
    {
        return DataTables::of($this->service->disinfectionQuery($request->only(['result', 'check_type'])))
            ->addIndexColumn()
            ->addColumn('check_type_label', fn($row) => __('clinic_affairs.check_type_' . $row->check_type))
            ->addColumn('result_badge', fn($row) => $this->badge(
                __('clinic_affairs.result_' . $row->result),
                $row->result === 'pass' ? 'success' : 'danger'
            ))
            ->addColumn('operator_name', fn($row) => optional($row->operator)->full_name ?: '-')
            ->addColumn('review_status', fn($row) => $this->reviewBadge($row))
            ->addColumn('action', fn($row) => $this->actions('disinfection', $row->id, !$row->reviewed_at, !$row->reviewed_at))
            ->rawColumns(['result_badge', 'review_status', 'action'])
            ->make(true);
    }

    public function equipmentData(Request $request)
    {
        return DataTables::of($this->service->equipmentQuery($request->only(['result', 'category', 'due'])))
            ->addIndexColumn()
            ->addColumn('category_label', fn($row) => __('clinic_affairs.equipment_category_' . $row->category))
            ->addColumn('maintenance_type_label', fn($row) => __('clinic_affairs.maintenance_type_' . $row->maintenance_type))
            ->addColumn('result_badge', fn($row) => $this->badge(
                __('clinic_affairs.equipment_result_' . $row->result),
                ['normal' => 'success', 'follow_up' => 'warning', 'out_of_service' => 'danger'][$row->result] ?? 'default'
            ))
            ->addColumn('due_badge', function ($row) {
                if (!$row->next_due_at) {
                    return '-';
                }
                $class = $row->next_due_at->isPast() ? 'danger' : ($row->next_due_at->lte(today()->addDays(30)) ? 'warning' : 'success');
                return $this->badge($row->next_due_at->format('Y-m-d'), $class);
            })
            ->addColumn('operator_name', fn($row) => optional($row->operator)->full_name ?: '-')
            ->addColumn('action', fn($row) => $this->actions('equipment', $row->id))
            ->rawColumns(['result_badge', 'due_badge', 'action'])
            ->make(true);
    }

    public function wasteData(Request $request)
    {
        return DataTables::of($this->service->wasteQuery($request->only(['waste_type'])))
            ->addIndexColumn()
            ->addColumn('waste_type_label', fn($row) => __('clinic_affairs.waste_type_' . $row->waste_type))
            ->addColumn('handler_name', fn($row) => optional($row->handler)->full_name ?: '-')
            ->addColumn('action', fn($row) => $this->actions('waste', $row->id))
            ->rawColumns(['action'])
            ->make(true);
    }

    public function storeDisinfection(Request $request): JsonResponse
    {
        $data = $this->validateDisinfection($request);
        $this->service->createDisinfection($data);
        return $this->success(__('clinic_affairs.record_created'));
    }

    public function updateDisinfection(Request $request, int $id): JsonResponse
    {
        try {
            $this->service->updateDisinfection($id, $this->validateDisinfection($request));
            return $this->success(__('clinic_affairs.record_updated'));
        } catch (\RuntimeException $e) {
            return response()->json(['status' => 0, 'message' => $e->getMessage()], 409);
        }
    }

    public function reviewDisinfection(int $id): JsonResponse
    {
        try {
            $this->service->reviewDisinfection($id);
            return $this->success(__('clinic_affairs.review_completed'));
        } catch (\RuntimeException $e) {
            // 重复复核（多半是两个人同时点，或按钮没随列表刷新）走 409，
            // 与 updateDisinfection 的「已复核不可改」同一档
            return response()->json(['status' => 0, 'message' => $e->getMessage()], 409);
        }
    }

    public function storeEquipment(Request $request): JsonResponse
    {
        $this->service->createEquipment($this->validateEquipment($request));
        return $this->success(__('clinic_affairs.record_created'));
    }

    public function updateEquipment(Request $request, int $id): JsonResponse
    {
        $this->service->updateEquipment($id, $this->validateEquipment($request));
        return $this->success(__('clinic_affairs.record_updated'));
    }

    public function storeWaste(Request $request): JsonResponse
    {
        $this->service->createWaste($this->validateWaste($request));
        return $this->success(__('clinic_affairs.record_created'));
    }

    public function updateWaste(Request $request, int $id): JsonResponse
    {
        $this->service->updateWaste($id, $this->validateWaste($request));
        return $this->success(__('clinic_affairs.record_updated'));
    }

    public function destroy(string $type, int $id): JsonResponse
    {
        try {
            $this->service->delete($type, $id);
            return $this->success(__('clinic_affairs.record_deleted'));
        } catch (\RuntimeException $e) {
            return response()->json(['status' => 0, 'message' => $e->getMessage()], 409);
        }
    }

    /*
     * 三个导出都遵守两条：
     *
     * 1. 跟随页面上的筛选。原来一律 ->get() 全量导出，页面筛了「合格」导出来还是
     *    全部 —— 用户拿到的表和屏幕上看到的对不上，是最容易酿成误判的那种错。
     * 2. 用 lazy() 而不是 get()。医疗废物交接是天天记的，两三年后一次全量导出会把
     *    整张表读进内存。lazy() 分批取还能保住 with() 的预加载（cursor() 不行）。
     *
     * 列也补齐了：异常记录不带「整改措施」、复核过的不带「复核人/复核时间」，
     * 这份表拿去应付检查等于白导。
     */
    public function exportDisinfection(Request $request)
    {
        return $this->csv('disinfection_records', [
            __('clinic_affairs.performed_at'), __('clinic_affairs.area'), __('clinic_affairs.check_type'),
            __('clinic_affairs.result'), __('clinic_affairs.disinfectant'), __('clinic_affairs.concentration'),
            __('clinic_affairs.corrective_action'), __('clinic_affairs.operator'),
            __('clinic_affairs.reviewer'), __('clinic_affairs.reviewed_at'), __('clinic_affairs.notes'),
        ], $this->service->disinfectionQuery($request->only(['result', 'check_type']))->lazy(500)->map(fn($row) => [
            $row->performed_at, $row->area, __('clinic_affairs.check_type_' . $row->check_type),
            __('clinic_affairs.result_' . $row->result), $row->disinfectant, $row->concentration,
            $row->corrective_action, optional($row->operator)->full_name,
            optional($row->reviewer)->full_name, $row->reviewed_at, $row->notes,
        ]));
    }

    public function exportEquipment(Request $request)
    {
        return $this->csv('equipment_maintenance', [
            __('clinic_affairs.performed_at'), __('clinic_affairs.equipment_code'), __('clinic_affairs.equipment_name'),
            __('clinic_affairs.equipment_category'), __('clinic_affairs.location'),
            __('clinic_affairs.maintenance_type'), __('clinic_affairs.result'), __('clinic_affairs.next_due_at'),
            __('clinic_affairs.vendor'), __('clinic_affairs.cost'), __('clinic_affairs.operator'),
            __('clinic_affairs.notes'),
        ], $this->service->equipmentQuery($request->only(['result', 'category', 'due']))->lazy(500)->map(fn($row) => [
            $row->performed_at, $row->equipment_code, $row->equipment_name,
            __('clinic_affairs.equipment_category_' . $row->category), $row->location,
            __('clinic_affairs.maintenance_type_' . $row->maintenance_type),
            __('clinic_affairs.equipment_result_' . $row->result), optional($row->next_due_at)->format('Y-m-d'),
            $row->vendor, $row->cost, optional($row->operator)->full_name, $row->notes,
        ]));
    }

    public function exportWaste(Request $request)
    {
        return $this->csv('medical_waste', [
            __('clinic_affairs.handed_over_at'), __('clinic_affairs.waste_type'), __('clinic_affairs.weight_kg'),
            __('clinic_affairs.package_count'), __('clinic_affairs.handler'), __('clinic_affairs.receiver_name'),
            __('clinic_affairs.carrier'), __('clinic_affairs.manifest_no'), __('clinic_affairs.destination'),
            __('clinic_affairs.notes'),
        ], $this->service->wasteQuery($request->only(['waste_type']))->lazy(500)->map(fn($row) => [
            $row->handed_over_at, __('clinic_affairs.waste_type_' . $row->waste_type), $row->weight_kg,
            $row->package_count, optional($row->handler)->full_name, $row->receiver_name,
            $row->carrier, $row->manifest_no, $row->destination, $row->notes,
        ]));
    }

    private function page(string $activeTab)
    {
        return view('clinic_affairs.index', [
            'activeTab' => $activeTab,
            'stats' => $this->service->getStats(),
            'canManage' => Auth::user()->can('manage-clinic-affairs'),
        ]);
    }

    private function validateDisinfection(Request $request): array
    {
        return Validator::make($request->all(), [
            'area' => 'required|string|max:100',
            'check_type' => ['required', Rule::in(['clinical_surface', 'housekeeping', 'waterline', 'air_quality', 'other'])],
            'disinfectant' => 'nullable|string|max:100',
            'concentration' => 'nullable|string|max:50',
            'performed_at' => 'required|date',
            'result' => ['required', Rule::in(['pass', 'issue'])],
            'corrective_action' => 'nullable|string|max:1000|required_if:result,issue',
            'notes' => 'nullable|string|max:1000',
        ])->validate();
    }

    private function validateEquipment(Request $request): array
    {
        return Validator::make($request->all(), [
            'equipment_code' => 'required|string|max:50',
            'equipment_name' => 'required|string|max:100',
            'category' => ['required', Rule::in(['xray', 'sterilizer', 'dental_unit', 'emergency', 'monitoring', 'other'])],
            'location' => 'nullable|string|max:100',
            'maintenance_type' => ['required', Rule::in(['inspection', 'preventive', 'repair', 'calibration'])],
            'performed_at' => 'required|date',
            'next_due_at' => 'nullable|date|after_or_equal:performed_at',
            'result' => ['required', Rule::in(['normal', 'follow_up', 'out_of_service'])],
            'vendor' => 'nullable|string|max:100',
            'cost' => 'nullable|numeric|min:0|max:9999999999',
            'notes' => 'nullable|string|max:1000',
        ])->validate();
    }

    private function validateWaste(Request $request): array
    {
        return Validator::make($request->all(), [
            'waste_type' => ['required', Rule::in(['infectious', 'sharps', 'pharmaceutical', 'chemical', 'other'])],
            'weight_kg' => 'required|numeric|min:0.01|max:99999999',
            'package_count' => 'required|integer|min:1|max:99999',
            'handed_over_at' => 'required|date',
            'receiver_name' => 'required|string|max:100',
            'carrier' => 'nullable|string|max:150',
            'manifest_no' => 'nullable|string|max:100',
            'destination' => 'nullable|string|max:200',
            'notes' => 'nullable|string|max:1000',
        ])->validate();
    }

    private function actions(string $type, int $id, bool $canEdit = true, bool $canReview = false): string
    {
        if (!Auth::user()->can('manage-clinic-affairs')) {
            return '';
        }
        if (!$canEdit && !$canReview) {
            return '';
        }
        $buttons = $canEdit
            ? "<button class='btn btn-xs btn-primary' onclick=\"ClinicAffairs.edit('{$type}',{$id})\">" . __('common.edit') . '</button> '
            : '';
        if ($canReview) {
            $buttons .= "<button class='btn btn-xs btn-success' onclick=\"ClinicAffairs.review({$id})\">" . __('clinic_affairs.review') . '</button> ';
        }
        if ($canEdit) {
            $buttons .= "<button class='btn btn-xs btn-danger' onclick=\"ClinicAffairs.remove('{$type}',{$id})\">" . __('common.delete') . '</button>';
        }
        return $buttons;
    }

    /**
     * 复核状态徽章。
     *
     * 登记人复核自己填的记录不拦 —— 单护士的诊所会被卡死，逼出「共用账号」这种
     * 更糟的绕法。改成标出来：自审走 warning 色并注明「本人」，检查的人一眼看得见。
     */
    private function reviewBadge($row): string
    {
        if (!$row->reviewed_at) {
            return $this->badge(__('clinic_affairs.pending_review'), 'warning');
        }

        $selfReviewed = $row->reviewer_id && (int) $row->reviewer_id === (int) $row->operator_id;

        return $this->badge(
            __($selfReviewed ? 'clinic_affairs.reviewed_by_self' : 'clinic_affairs.reviewed_by', [
                'name' => optional($row->reviewer)->full_name ?: '-',
                'time' => $row->reviewed_at->format('m-d H:i'),
            ]),
            // 三态要一眼分得开：待复核 warning（要干活）、他人复核 success、
            // 自审 info。自审若也用 warning 就和「待复核」撞色，扫一眼分不出来
            $selfReviewed ? 'info' : 'success'
        );
    }

    private function badge(string $label, string $class): string
    {
        return "<span class='label label-{$class}'>" . e($label) . '</span>';
    }

    private function success(string $message): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'message' => $message,
            'stats' => $this->service->getStats(),
        ]);
    }

    private function csv(string $prefix, array $headers, $rows)
    {
        return response()->stream(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $prefix . '_' . now()->format('Ymd') . '.csv"',
        ]);
    }
}
