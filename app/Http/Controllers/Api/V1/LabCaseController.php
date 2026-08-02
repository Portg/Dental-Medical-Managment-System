<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\LabCaseResource;
use App\LabCase;
use App\LabCaseItem;
use App\Services\LabCaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @group Lab Cases
 */
class LabCaseController extends ApiController
{
    public function __construct(
        protected LabCaseService $service
    ) {
        $this->middleware('can:manage-labs');
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'lab_id', 'doctor_id', 'search']);
        $cases = $this->service->getLabCaseList($filters);

        return $this->success($cases);
    }

    public function show(int $id): JsonResponse
    {
        $case = $this->service->getLabCase($id);

        if (!$case) {
            return $this->error(__('lab_cases.case_not_found'), 404);
        }

        return $this->success(new LabCaseResource($case));
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'patient_id'           => 'required|exists:patients,id',
            'doctor_id'            => 'required|exists:users,id',
            'lab_id'               => 'required|exists:labs,id',
            'processing_days'      => 'nullable|integer|min:1|max:365',
            'special_requirements' => 'nullable|string|max:2000',
            'expected_return_date' => 'nullable|date|after_or_equal:today',
            'lab_fee'              => 'nullable|numeric|min:0',
            'patient_charge'       => 'nullable|numeric|min:0',
            'appointment_id'       => 'nullable|exists:appointments,id',
            'medical_case_id'      => 'nullable|exists:medical_cases,id',
            'notes'                => 'nullable|string|max:2000',
        ] + $this->itemRules('required_without:items|string|max:100'));

        if ($validator->fails()) {
            return $this->error(__('common.validation_failed'), 422, $validator->errors());
        }

        $case = $this->service->createLabCase(
            $request->only([
                'patient_id', 'doctor_id', 'lab_id', 'processing_days',
                'special_requirements', 'expected_return_date',
                'lab_fee', 'patient_charge',
                'appointment_id', 'medical_case_id', 'notes',
            ]),
            $this->extractItems($request) ?? []
        );

        $case->load(['patient', 'doctor', 'lab', 'items']);

        return $this->success(new LabCaseResource($case), __('lab_cases.case_created'), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'processing_days'      => 'nullable|integer|min:1|max:365',
            'special_requirements' => 'nullable|string|max:2000',
            'expected_return_date' => 'nullable|date',
            'lab_fee'              => 'nullable|numeric|min:0',
            'patient_charge'       => 'nullable|numeric|min:0',
            'quality_rating'       => 'nullable|integer|min:1|max:5',
            'notes'                => 'nullable|string|max:2000',
        ] + $this->itemRules('nullable|string|max:100'));

        if ($validator->fails()) {
            return $this->error(__('common.validation_failed'), 422, $validator->errors());
        }

        $status = $this->service->updateLabCase(
            $id,
            $request->only([
                'processing_days', 'special_requirements', 'expected_return_date',
                'lab_fee', 'patient_charge', 'quality_rating', 'notes',
            ]),
            $this->extractItems($request, $id)
        );

        if (!$status) {
            return $this->error(__('lab_cases.error_updating_case'), 500);
        }

        $case = $this->service->getLabCase($id);

        return $this->success(new LabCaseResource($case), __('lab_cases.case_updated'));
    }

    public function destroy(int $id): JsonResponse
    {
        $status = $this->service->deleteLabCase($id);

        if (!$status) {
            return $this->error(__('lab_cases.error_deleting_case'), 500);
        }

        return $this->success(null, __('lab_cases.case_deleted'));
    }

    /**
     * Update lab case status.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validStatuses = \App\DictItem::listByType('lab_case_status')->pluck('code')->implode(',');

        $validator = Validator::make($request->all(), [
            'status'        => "required|in:{$validStatuses}",
            'rework_reason' => 'nullable|required_if:status,rework|string|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->error(__('common.validation_failed'), 422, $validator->errors());
        }

        $status = $this->service->updateStatus(
            $id,
            $request->input('status'),
            $request->only(['rework_reason', 'sent_date', 'actual_return_date'])
        );

        if (!$status) {
            return $this->error(__('lab_cases.error_updating_status'), 500);
        }

        $case = $this->service->getLabCase($id);

        return $this->success(new LabCaseResource($case), __('lab_cases.status_updated'));
    }

    /**
     * Get overdue lab cases.
     */
    public function overdue(): JsonResponse
    {
        $cases = $this->service->getOverdueCases();

        return $this->success($cases);
    }

    /**
     * Get lab cases for a patient.
     */
    public function patientCases(int $patientId): JsonResponse
    {
        $cases = $this->service->getPatientCases($patientId);

        return $this->success(LabCaseResource::collection($cases));
    }

    /**
     * Get lab case statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->service->getStatistics();

        return $this->success($stats);
    }

    /**
     * 技工单明细的校验规则，store / update 共用。
     *
     * $prosthesisTypeRule 是**平铺** prosthesis_type 的规则：建单时必填（除非走 items[]），
     * 更新时可选。其余规则两边完全一致。
     */
    private function itemRules(string $prosthesisTypeRule): array
    {
        return [
            // 明细：多件走 items[]，单件可继续用平铺字段（见 extractItems）
            'items'                   => 'nullable|array|min:1|max:4',
            'items.*.prosthesis_type' => 'required_with:items|string|max:100',
            'items.*.material'        => 'nullable|string|max:100',
            'items.*.color_shade'     => 'nullable|string|max:50',
            'items.*.teeth_positions' => 'nullable',
            'items.*.qty'             => 'nullable|integer|min:1|max:99',
            'prosthesis_type'         => $prosthesisTypeRule,
            'material'                => 'nullable|string|max:100',
            'color_shade'             => 'nullable|string|max:50',
            'teeth_positions'         => 'nullable',
        ];
    }

    /**
     * 归一化请求里的技工单明细。
     *
     * 2026_03_06 的迁移把 prosthesis_type / material / color_shade / teeth_positions
     * 从 lab_cases 移到了 lab_case_items，一张单可挂最多 4 件。接口同时接受两种写法：
     *
     *   - items[]：多件，与 Web 端一致，是推荐写法；
     *   - 平铺字段：旧版单件写法，映射成一条明细。
     *
     * 返回 null 表示请求没带明细信息（更新时即"保持原样"）。
     */
    private function extractItems(Request $request, ?int $labCaseId = null): ?array
    {
        if ($request->has('items')) {
            return array_map(fn ($item) => [
                'prosthesis_type' => $item['prosthesis_type'],
                'material'        => $item['material'] ?? null,
                'color_shade'     => $item['color_shade'] ?? null,
                'teeth_positions' => $this->normalizeTeethPositions($item['teeth_positions'] ?? null),
                'qty'             => $item['qty'] ?? 1,
            ], $request->input('items', []));
        }

        $legacyKeys = ['prosthesis_type', 'material', 'color_shade', 'teeth_positions'];

        if (!$request->hasAny($legacyKeys)) {
            return null;
        }

        // 平铺字段只描述**一件**，但单子上可能挂着最多 4 件，而 updateLabCase()
        // 的语义是"按传入列表对齐明细"——只回传第一件会把第 2~4 件直接删掉。
        // 所以这里把其余明细原样带回，平铺字段只并进第一件。
        $existing = $labCaseId
            ? LabCaseItem::where('lab_case_id', $labCaseId)->orderBy('sort_order')->get()
            : collect();

        $first = $existing->first();

        $items = [[
            'prosthesis_type' => $request->input('prosthesis_type', $first->prosthesis_type ?? null),
            'material'        => $request->input('material', $first->material ?? null),
            'color_shade'     => $request->input('color_shade', $first->color_shade ?? null),
            'teeth_positions' => $request->has('teeth_positions')
                ? $this->normalizeTeethPositions($request->input('teeth_positions'))
                : ($first->teeth_positions ?? null),
            'qty'             => $first->qty ?? 1,
        ]];

        foreach ($existing->slice(1) as $item) {
            $items[] = [
                'prosthesis_type' => $item->prosthesis_type,
                'material'        => $item->material,
                'color_shade'     => $item->color_shade,
                'teeth_positions' => $item->teeth_positions,
                'qty'             => $item->qty,
            ];
        }

        return $items;
    }

    /**
     * 牙位既可能是数组，也可能是 Web 表单传来的逗号分隔字符串。
     */
    private function normalizeTeethPositions($value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            // 只收标量牙位号：teeth_positions 是 json 列，嵌套数组/对象原样落库
            // 会让读取端（Blade 与前端 JS 都按扁平字符串数组渲染）拿到无法处理的结构。
            $positions = array_values(array_filter($value, 'is_scalar'));

            return $positions ?: null;
        }

        return array_map('trim', explode(',', (string) $value));
    }

    /**
     * Get enum options (prosthesis types, materials, statuses).
     */
    public function options(): JsonResponse
    {
        return $this->success([
            'prosthesis_types' => \App\DictItem::listByType('lab_case_prosthesis_type')->pluck('name', 'code'),
            'materials'        => \App\DictItem::listByType('lab_case_material')->pluck('name', 'code'),
            'statuses'         => \App\DictItem::listByType('lab_case_status')->pluck('name', 'code'),
        ]);
    }
}
