<?php

namespace App\Services;

use App\Diagnosis;
use App\Http\Helper\ActionColumnHelper;
use App\MedicalCase;
use App\MedicalCaseAmendment;
use App\MedicalCaseItem;
use App\OperationLog;
use App\Patient;
use App\PatientFollowup;
use App\TreatmentPlan;
use App\User;
use App\VitalSign;
use App\DictItem;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class MedicalCaseService
{
    private PatientFollowupService $followupService;

    public function __construct(PatientFollowupService $followupService)
    {
        $this->followupService = $followupService;
    }

    /**
     * Get all medical cases for DataTables (query builder, server-side pagination).
     * Filters are pushed to SQL — avoids loading all records into PHP memory.
     */
    public function getAllCases(array $filters): Builder
    {
        $locale = app()->getLocale();
        $isCn   = $locale === 'zh-CN';

        // 搜索词兼容 'search' 和 'search_term' 两个 key（历史遗留）
        $search = $filters['search'] ?? $filters['search_term'] ?? null;

        return DB::table('medical_cases')
            ->leftJoin('patients', 'patients.id', '=', 'medical_cases.patient_id')
            ->leftJoin('users as doctors', 'doctors.id', '=', 'medical_cases.doctor_id')
            ->leftJoin('users as added_by', 'added_by.id', '=', 'medical_cases._who_added')
            ->whereNull('medical_cases.deleted_at')
            ->when($search, function ($q, $term) use ($isCn) {
                $like = '%' . $term . '%';
                $nameExpr = $isCn
                    ? DB::raw("CONCAT(IFNULL(patients.surname,''), IFNULL(patients.othername,''))")
                    : DB::raw("CONCAT(IFNULL(patients.surname,''), ' ', IFNULL(patients.othername,''))");
                $q->where(function ($inner) use ($like, $nameExpr) {
                    $inner->where('medical_cases.case_no', 'like', $like)
                          ->orWhere('medical_cases.title', 'like', $like)
                          ->orWhere($nameExpr, 'like', $like);
                });
            })
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('medical_cases.status', $v))
            ->when($filters['doctor_id'] ?? null, fn ($q, $v) => $q->where('medical_cases.doctor_id', $v))
            ->when($filters['patient_id'] ?? null, fn ($q, $v) => $q->where('medical_cases.patient_id', $v))
            ->when($filters['start_date'] ?? null, fn ($q, $v) => $q->where('medical_cases.case_date', '>=', $v))
            ->when($filters['end_date'] ?? null, fn ($q, $v) => $q->where('medical_cases.case_date', '<=', $v))
            ->select(
                'medical_cases.*',
                DB::raw($isCn
                    ? "CONCAT(IFNULL(patients.surname,''), IFNULL(patients.othername,'')) as patient_name"
                    : "CONCAT(IFNULL(patients.surname,''), ' ', IFNULL(patients.othername,'')) as patient_name"),
                'patients.patient_no',
                DB::raw($isCn
                    ? "CONCAT(IFNULL(doctors.surname,''), IFNULL(doctors.othername,'')) as doctor_name"
                    : "CONCAT(IFNULL(doctors.surname,''), ' ', IFNULL(doctors.othername,'')) as doctor_name"),
                DB::raw($isCn
                    ? "CONCAT(IFNULL(added_by.surname,''), IFNULL(added_by.othername,'')) as added_by_name"
                    : "CONCAT(IFNULL(added_by.surname,''), ' ', IFNULL(added_by.othername,'')) as added_by_name"),
                DB::raw("(SELECT COUNT(*) FROM medical_case_amendments
                          WHERE medical_case_amendments.medical_case_id = medical_cases.id
                          AND medical_case_amendments.status = 'pending') as pending_amendments_count")
            )
            ->orderBy('medical_cases.created_at', 'desc');
    }

    /**
     * Get cases for a specific patient.
     */
    public function getPatientCases(int $patientId): Collection
    {
        return DB::table('medical_cases')
            ->leftJoin('users as doctors', 'doctors.id', 'medical_cases.doctor_id')
            ->whereNull('medical_cases.deleted_at')
            ->where('medical_cases.patient_id', $patientId)
            ->orderBy('medical_cases.created_at', 'desc')
            ->select(
                'medical_cases.*',
                DB::raw(app()->getLocale() === 'zh-CN' ? "CONCAT(doctors.surname, doctors.othername) as doctor_name" : "CONCAT(doctors.surname, ' ', doctors.othername) as doctor_name")
            )
            ->get();
    }

    /**
     * Get case with relationships for show view.
     */
    public function getCaseDetail(int $id): array
    {
        $case = MedicalCase::with(['patient', 'doctor', 'addedBy'])->findOrFail($id);
        $doctors = $this->getDoctors();

        return compact('case', 'doctors');
    }

    /**
     * Get case with relationships for edit view.
     */
    public function getCaseForEdit(int $id): array
    {
        $case = MedicalCase::with(['patient', 'doctor'])->findOrFail($id);
        $doctors = $this->getDoctors();

        $historyRecords = $this->getPatientHistory((int) $case->patient_id, $id);

        return compact('case', 'doctors', 'historyRecords');
    }

    /**
     * Get case as JSON.
     */
    public function getCase(int $id): ?MedicalCase
    {
        return MedicalCase::where('id', $id)->first();
    }

    /**
     * Get data for create form.
     */
    public function getCreateData(): array
    {
        $doctors = $this->getDoctors();
        $patients = Patient::whereNull('deleted_at')->orderBy('surname')->get();
        $pendingAppointments = collect([]);

        return compact('doctors', 'patients', 'pendingAppointments');
    }

    /**
     * 某患者最近的病历，供侧栏「历史记录」使用。
     *
     * 三条路径共用：编辑已有病历（排除自己）、从患者进来新建、以及走通用入口
     * 选完患者之后的异步加载。原先每处各写一遍同样的查询，通用入口那条干脆漏了。
     */
    public function getPatientHistory(int $patientId, ?int $excludeCaseId = null): Collection
    {
        return MedicalCase::where('patient_id', $patientId)
            ->when($excludeCaseId, fn ($q) => $q->where('id', '!=', $excludeCaseId))
            ->whereNull('deleted_at')
            ->orderBy('case_date', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Get data for creating a case for a specific patient.
     */
    public function getCreateForPatientData(int $patientId): array
    {
        $patient = Patient::findOrFail($patientId);
        $doctors = $this->getDoctors();

        $historyRecords = $this->getPatientHistory($patientId);

        $hasExistingCase = MedicalCase::where('patient_id', $patientId)->exists();

        // 查询已完成但未写病历的预约（补充病历候选）
        $pendingAppointments = \App\Appointment::where('patient_id', $patientId)
            ->whereNull('medical_case_id')
            ->whereNull('deleted_at')
            ->whereIn('status', [\App\Appointment::STATUS_COMPLETED, \App\Appointment::STATUS_TREATMENT_COMPLETE])
            ->orderBy('start_date', 'desc')
            ->limit(20)
            ->select('id', 'start_date', 'start_time', 'doctor_id', 'visit_information')
            ->with('doctor:id,surname,othername')
            ->get();

        return compact('patient', 'doctors', 'historyRecords', 'hasExistingCase', 'pendingAppointments');
    }

    /**
     * 把分行录入的明细归一化，并由它派生 medical_cases 上的文本列与牙位列。
     *
     * 派生方向是单向的：行是唯一可编辑来源，文本列是行的渲染结果。编辑器只读行、
     * 不读文本列 —— 否则同一段临床文字有两个来源，改一处漏一处。
     *
     * 保留文本列的原因见 2026_08_19_200000 迁移注释：打印、病历详情、API Resource、
     * OCR 写入、工作日志五处都在消费它，而它们要的就是一段可读文字，不该为此改动。
     *
     * @param  array $rawItems  前端提交的 [{section, tooth_no, content}]
     * @return array{items: array, columns: array}
     */
    public function normalizeCaseItems($rawItems): array
    {
        $rawItems = is_string($rawItems) ? (json_decode($rawItems, true) ?: []) : (array) ($rawItems ?: []);

        $items   = [];
        $bySection = [];

        foreach ($rawItems as $row) {
            $section = (string) ($row['section'] ?? '');
            if (!in_array($section, MedicalCaseItem::SECTIONS, true)) {
                continue;
            }

            $content = trim((string) ($row['content'] ?? ''));
            $tooth   = trim((string) ($row['tooth_no'] ?? ''));

            // 牙位和文字都空的行是用户点了「添加」又没填，直接丢掉，
            // 不然每次保存都会攒下一堆空行
            if ($content === '' && $tooth === '') {
                continue;
            }

            // 一行可以写多颗牙（「16,17 缺失」）—— 那是**写法**上的合并。
            // 落库仍然一牙一行：MedicalCaseItem::forTooth('16') 得查得到，
            // 那是这张表存在的理由；存成 '16,17' 就得靠 LIKE 去猜，索引也废了。
            // 读回来时再按「同段落 + 同内容」合并（见 getCaseItemsForEdit）。
            $teeth = $tooth === ''
                ? [null]
                : array_values(array_filter(array_map('trim', preg_split('/[,，\s]+/u', $tooth))));

            foreach ($teeth === [] ? [null] : $teeth as $one) {
                $items[] = [
                    'section'    => $section,
                    'tooth_no'   => $one === '' ? null : $one,
                    'content'    => $content === '' ? null : $content,
                    'sort_order' => count($bySection[$section] ?? []),
                ];
                $bySection[$section][] = end($items);
            }
        }

        return ['items' => $items, 'columns' => $this->deriveColumnsFromItems($bySection)];
    }

    /**
     * 该患者的第几次就诊。
     *
     * 参考视频顶部的「就诊次数 2」—— 一个疗程要来好几次（根管：开髓 → 预备 → 充填），
     * 医生一眼要知道这是第几次。市场上的做法是「一次就诊一份病历」，用就诊次数把
     * 同一患者的历次串起来，而不是一份病历挂多条记录。
     *
     * 算出来而不是存字段：存了就要维护，删一份病历还得回填后面所有的序号。
     * 新建时给「已有份数 + 1」，编辑时给这份病历自己的序号（按就诊日期、同日按 id）。
     */
    public function visitSequence(?MedicalCase $case, ?int $patientId = null): ?int
    {
        $patientId = $case->patient_id ?? $patientId;

        if (!$patientId) {
            return null;
        }

        $query = MedicalCase::where('patient_id', $patientId);

        if (!$case || !$case->exists) {
            return $query->count() + 1;
        }

        // 排在这份病历之前的份数 + 1 —— 同日多份用 id 兜底，保证序号稳定
        return $query->where(function ($q) use ($case) {
            $q->where('case_date', '<', $case->case_date)
              ->orWhere(function ($q2) use ($case) {
                  $q2->where('case_date', $case->case_date)->where('id', '<=', $case->id);
              });
        })->count();
    }

    /**
     * 诊断行 —— 落在 diagnoses 表，不在 medical_case_items。
     *
     * diagnoses 本来就是一行一条诊断，还带 ICD 编码、严重程度、转归状态，
     * 是 medical_case_items 存不了的（ICD 是医保与病案质控要的）。
     * 同一条诊断不在两张表里各存一份。
     *
     * 此前这张表一直是 0 条：它只能从病历详情页的「诊断记录」Tab 单独添加，
     * 而医生的动作是「新建病历 → 一屏填完 → 提交」，不会填完再拐过去点一次。
     * 现在编辑页的诊断段直接写它，主流程终于能产生数据。
     *
     * @param  array|string $rawRows 前端提交的 [{tooth_no, content, icd_code, severity}]
     * @return array{rows: array, columns: array}
     */
    public function normalizeDiagnoses($rawRows): array
    {
        $rawRows = is_string($rawRows) ? (json_decode($rawRows, true) ?: []) : (array) ($rawRows ?: []);

        $rows  = [];
        $teeth = [];

        foreach ($rawRows as $row) {
            $name = trim((string) ($row['content'] ?? ''));
            $toothRaw = trim((string) ($row['tooth_no'] ?? ''));

            // 诊断名是必须的：只选了牙位没写诊断，这一行没有意义
            if ($name === '') {
                continue;
            }

            // 一行可以写多颗牙（「16,17 中龋」）。与 medical_case_items 同样的处理：
            // 落库一牙一条，forTooth('16') 才查得到；合并只是写法。
            $list = $toothRaw === ''
                ? [null]
                : array_values(array_filter(array_map('trim', preg_split('/[,，\s]+/u', $toothRaw))));

            foreach ($list === [] ? [null] : $list as $one) {
                $rows[] = [
                    'diagnosis_name' => $name,
                    'tooth_no'       => ($one === '' || $one === null) ? null : $one,
                    'icd_code'       => trim((string) ($row['icd_code'] ?? '')) ?: null,
                    'severity'       => in_array($row['severity'] ?? null, ['Mild', 'Moderate', 'Severe'], true)
                        ? $row['severity'] : null,
                    'sort_order'     => count($rows),
                ];
                if ($one !== null && $one !== '') {
                    $teeth[] = $one;
                }
            }
        }

        // medical_cases 上的 diagnosis 文本列与 related_teeth 仍由这里派生 ——
        // 打印、病历详情、API、OCR、工作日志都在读它们，不该为此改动。
        $text = $rows === [] ? null : implode("\n", array_map(function ($r) {
            $line = $r['tooth_no'] !== null ? $r['tooth_no'] . ' ' . $r['diagnosis_name'] : $r['diagnosis_name'];
            return $r['icd_code'] ? $line . '（' . $r['icd_code'] . '）' : $line;
        }, $rows));

        return [
            'rows'    => $rows,
            'columns' => [
                'diagnosis'     => $text,
                'related_teeth' => $teeth === [] ? null : array_values(array_unique($teeth)),
            ],
        ];
    }

    /**
     * 落库：整段替换某份病历的诊断。
     *
     * 与 syncCaseItems 同样是整段替换 + 软删 —— 诊断没有稳定的业务标识，
     * diff 需要前端回传 id 且要防篡改，成本远高于收益；病历有审计要求，软删不硬删。
     */
    public function syncDiagnoses(MedicalCase $case, array $rows): void
    {
        DB::transaction(function () use ($case, $rows) {
            \App\Diagnosis::where('medical_case_id', $case->id)->delete();

            foreach ($rows as $row) {
                \App\Diagnosis::create($row + [
                    'medical_case_id' => $case->id,
                    'patient_id'      => $case->patient_id,
                    'diagnosis_date'  => $case->case_date ?? now()->toDateString(),
                    'status'          => 'Active',
                    '_who_added'      => Auth::id(),
                ]);
            }
        });
    }

    /**
     * 给编辑器用的诊断行。老病历没有 diagnoses 记录时，按 medical_cases.diagnosis
     * 整段合成一行（与 getCaseItemsForEdit 同样的兜底），不做破坏性迁移。
     */
    public function getDiagnosesForEdit(?MedicalCase $case): array
    {
        if (!$case) {
            return [];
        }

        $existing = \App\Diagnosis::where('medical_case_id', $case->id)
            ->orderBy('sort_order')->orderBy('id')
            ->get(['diagnosis_name', 'tooth_no', 'icd_code', 'severity']);

        if ($existing->isEmpty()) {
            $legacy = trim((string) ($case->diagnosis ?? ''));
            return $legacy === '' ? [] : [[
                'tooth_no' => null, 'content' => $legacy, 'icd_code' => null, 'severity' => null,
            ]];
        }

        // 与 medical_case_items 一致：同「诊断名 + ICD」的多颗牙合并回一行
        $merged = [];
        foreach ($existing as $row) {
            $key = $row->diagnosis_name . "\0" . (string) $row->icd_code;
            if (!isset($merged[$key])) {
                $merged[$key] = [
                    'tooth_no' => [], 'content' => $row->diagnosis_name,
                    'icd_code' => $row->icd_code, 'severity' => $row->severity,
                ];
            }
            if ($row->tooth_no) {
                $merged[$key]['tooth_no'][] = $row->tooth_no;
            }
        }

        return array_values(array_map(function ($r) {
            $r['tooth_no'] = $r['tooth_no'] === [] ? null : implode(',', array_unique($r['tooth_no']));
            return $r;
        }, $merged));
    }

    /**
     * 由分段明细渲染出文本列与牙位列。
     *
     * 文字格式是「牙位 内容」逐行，与录入时看到的一致 —— 打印出来医生一眼能对上。
     * 没有牙位的行只出内容。
     */
    private function deriveColumnsFromItems(array $bySection): array
    {
        $columns = [];

        foreach (MedicalCaseItem::SECTIONS as $section) {
            $rows = $bySection[$section] ?? [];

            $columns[$section] = $rows === [] ? null : implode("\n", array_map(function ($r) {
                return $r['tooth_no'] !== null && $r['content'] !== null
                    ? $r['tooth_no'] . ' ' . $r['content']
                    : ($r['content'] ?? $r['tooth_no']);
            }, $rows));

            // 牙位列 = 本段所有行牙位的去重集合，顺序按录入
            if (isset(MedicalCaseItem::TEETH_COLUMNS[$section])) {
                $teeth = array_values(array_unique(array_filter(array_column($rows, 'tooth_no'))));
                $columns[MedicalCaseItem::TEETH_COLUMNS[$section]] = $teeth === [] ? null : $teeth;
            }
        }

        return $columns;
    }

    /**
     * 落库：整段替换某份病历的明细行。
     *
     * 整段替换而不是逐行 diff：行没有稳定的业务标识（同一颗牙可以有多行），
     * diff 需要前端回传 id 并保证不被篡改，成本远高于收益。软删而非硬删，
     * 病历是有审计要求的。
     */
    public function syncCaseItems(MedicalCase $case, array $items): void
    {
        DB::transaction(function () use ($case, $items) {
            MedicalCaseItem::where('medical_case_id', $case->id)->delete();

            foreach ($items as $item) {
                MedicalCaseItem::create($item + [
                    'medical_case_id' => $case->id,
                    '_who_added'      => Auth::id(),
                ]);
            }
        });
    }

    /**
     * 给编辑器用的分段明细。
     *
     * 老病历没有行：把该段的整段文字合成一行（tooth_no 为 null）返回，
     * 下次保存时落库。不做破坏性迁移 —— 一段话没法自动拆成按牙位的行，
     * 硬拆只会把病历弄乱。
     */
    public function getCaseItemsForEdit(?MedicalCase $case): array
    {
        $out = array_fill_keys(MedicalCaseItem::SECTIONS, []);

        if (!$case) {
            return $out;
        }

        $existing = MedicalCaseItem::where('medical_case_id', $case->id)
            ->orderBy('sort_order')->orderBy('id')
            ->get(['section', 'tooth_no', 'content']);

        // 按「同段落 + 同内容」把牙位合回一行：落库是一牙一行（为了按牙位可查），
        // 但医生写的时候「16、17 缺失」本来就是一条，读回来要还原成一条。
        $merged = [];
        foreach ($existing as $row) {
            if (!isset($out[$row->section])) {
                continue;
            }
            $key = $row->section . "\0" . (string) $row->content;

            if (isset($merged[$key])) {
                if ($row->tooth_no !== null && $row->tooth_no !== '') {
                    $merged[$key]['teeth'][] = $row->tooth_no;
                }
                continue;
            }

            $merged[$key] = [
                'section' => $row->section,
                'content' => $row->content,
                'teeth'   => ($row->tooth_no !== null && $row->tooth_no !== '') ? [$row->tooth_no] : [],
            ];
        }

        foreach ($merged as $row) {
            $out[$row['section']][] = [
                'tooth_no' => $row['teeth'] === [] ? null : implode(',', array_unique($row['teeth'])),
                'content'  => $row['content'],
            ];
        }

        foreach (MedicalCaseItem::SECTIONS as $section) {
            if ($out[$section] !== []) {
                continue;
            }
            $legacy = trim((string) ($case->{$section} ?? ''));
            if ($legacy !== '') {
                $out[$section][] = ['tooth_no' => null, 'content' => $legacy];
            }
        }

        return $out;
    }

    /**
     * Build case data array from input.
     */
    public function buildCaseData(array $input, bool $isUpdate = false): array
    {
        $title = !empty($input['chief_complaint'])
            ? mb_substr($input['chief_complaint'], 0, 50)
            : __('medical_cases.medical_record_edit') . ' ' . ($input['case_date'] ?? '');

        $data = [
            'title' => $title,
            'chief_complaint' => $input['chief_complaint'] ?? null,
            'history_of_present_illness' => $input['history_of_present_illness'] ?? null,
            'past_medical_history' => $input['past_medical_history'] ?? null,
            'examination' => $input['examination'] ?? null,
            'examination_teeth' => !empty($input['examination_teeth']) ? json_decode($input['examination_teeth'], true) : null,
            'auxiliary_examination' => $input['auxiliary_examination'] ?? null,
            'related_images' => !empty($input['related_images']) ? json_decode($input['related_images'], true) : null,
            'diagnosis' => $input['diagnosis'] ?? null,
            'diagnosis_code' => $input['diagnosis_code'] ?? null,
            'related_teeth' => !empty($input['related_teeth']) ? json_decode($input['related_teeth'], true) : null,
            'treatment' => $input['treatment'] ?? null,
            'treatment_services' => !empty($input['treatment_services']) ? json_decode($input['treatment_services'], true) : null,
            'medical_orders' => $input['medical_orders'] ?? null,
            'next_visit_date' => $input['next_visit_date'] ?? null,
            'next_visit_note' => $input['next_visit_note'] ?? null,
            'auto_create_followup' => array_key_exists('auto_create_followup', $input),
            'visit_type' => $input['visit_type'] ?? 'initial',
            'case_date' => $input['case_date'] ?? null,
            'patient_id' => $input['patient_id'] ?? null,
            'doctor_id' => $input['doctor_id'] ?? Auth::user()->id,
        ];

        // 分段明细（牙位 + 文字）提交上来时，检查/其他检查/诊断/治疗四段的文本列
        // 与牙位列一律由行派生并覆盖上面直接取的值 —— 行是唯一可编辑来源。
        //
        // 派生结果要落在 $data 里、走既有的 create/updateCase，锁定病历的修订链路
        // （createAmendment 只 diff getFillable() 里的键）才能捕获到内容变化。
        // 若只把行塞在一个非 fillable 的键里传下去，锁定病历的行编辑会被静默丢弃。
        if (array_key_exists('case_items', $input)) {
            $normalized = $this->normalizeCaseItems($input['case_items']);
            $data = array_merge($data, $normalized['columns']);
            $data['case_items'] = $normalized['items'];
        }

        // 诊断段单独走 diagnoses 表（带 ICD 编码），派生出的 diagnosis 文本列与
        // related_teeth 同样合进 $data，走既有的保存/修订链路 —— 与 case_items 同理，
        // createAmendment 只 diff getFillable() 里的键，锁定病历的诊断改动才捕获得到。
        if (array_key_exists('diagnosis_rows', $input)) {
            $dx = $this->normalizeDiagnoses($input['diagnosis_rows']);
            $data = array_merge($data, $dx['columns']);
            $data['diagnosis_rows'] = $dx['rows'];
        }

        if (!$isUpdate) {
            $data['case_no'] = MedicalCase::CaseNumber();
            $data['status'] = MedicalCase::STATUS_OPEN;
            $data['_who_added'] = Auth::user()->id;
        }

        // appointment_id 不存入 medical_cases 表，仅用于后续关联
        if (!empty($input['appointment_id'])) {
            $data['appointment_id'] = (int) $input['appointment_id'];
        }

        return $data;
    }

    /**
     * Create a new medical case.
     */
    public function createCase(array $data, bool $isDraft): ?MedicalCase
    {
        return DB::transaction(function () use ($data, $isDraft) {
            // 提取 appointment_id（不存入 medical_cases 表）
            $appointmentId = $data['appointment_id'] ?? null;
            unset($data['appointment_id']);

            // 分段明细存在自己的表里，不是 medical_cases 的列
            $caseItems = $data['case_items'] ?? null;
            unset($data['case_items']);

            // 诊断存 diagnoses 表，不是 medical_cases 的列
            $diagnosisRows = $data['diagnosis_rows'] ?? null;
            unset($data['diagnosis_rows']);

            $data['is_draft'] = $isDraft;
            $data['version_number'] = 1;
            $case = MedicalCase::create($data);

            if ($case && $caseItems !== null) {
                $this->syncCaseItems($case, $caseItems);
            }

            if ($case && $diagnosisRows !== null) {
                $this->syncDiagnoses($case, $diagnosisRows);
            }

            if ($case && !$isDraft) {
                $case->lock();
            }

            if ($case) {
                OperationLog::logCreate('medical', 'MedicalCase', $case->id, $case->toArray());

                // 关联预约
                if ($appointmentId) {
                    \App\Appointment::where('id', $appointmentId)
                        ->whereNull('medical_case_id')
                        ->update(['medical_case_id' => $case->id]);
                }

                // 草稿不生成复诊待办：草稿会反复保存，而且「草稿」本身就意味着还没定。
                if (!$isDraft) {
                    $this->syncFollowupFromCase($case);
                }
            }

            return $case;
        });
    }

    /**
     * Update an existing medical case.
     *
     * @return array{status: bool, require_reason?: bool, amendment_id?: int}
     */
    public function updateCase(int $id, array $data, bool $isDraft, ?string $modificationReason = null, ?string $closingStatus = null, ?string $closingNotes = null): array
    {
        $case = MedicalCase::findOrFail($id);

        // Locked cases require amendment approval (compliance)
        if ($case->is_locked && !$case->canModifyWithoutApproval()) {
            if (!$modificationReason) {
                return ['status' => false, 'require_reason' => true];
            }

            // Create amendment request instead of direct update
            //
            // 行不在这里落库：修订要等审批，审批通过后生效的是文本列（那是
            // createAmendment 记录的内容）。此时行会与文本列不一致 —— 下次打开
            // 病历时 getCaseItemsForEdit() 发现该段没有行、按整段文字合成一行，
            // 数据不会错，只是丢掉分行粒度。这比让未审批的行直接落库要安全。
            unset($data['case_items'], $data['diagnosis_rows']);
            $amendment = $this->createAmendment($case, $data, $modificationReason);
            return ['status' => true, 'amendment_id' => $amendment->id];
        }

        $data['is_draft'] = $isDraft;

        // Validate state transition
        if ($closingStatus && $closingStatus !== $case->status) {
            $allowedTransitions = [
                MedicalCase::STATUS_OPEN       => [MedicalCase::STATUS_CLOSED, MedicalCase::STATUS_FOLLOW_UP],
                MedicalCase::STATUS_FOLLOW_UP  => [MedicalCase::STATUS_CLOSED, MedicalCase::STATUS_OPEN],
                MedicalCase::STATUS_CLOSED     => [],
            ];
            if (!in_array($closingStatus, $allowedTransitions[$case->status] ?? [])) {
                return ['status' => false, 'invalid_transition' => true];
            }
            $data['status'] = $closingStatus;
            if ($closingStatus === MedicalCase::STATUS_CLOSED) {
                $data['closed_date'] = now();
                $data['closing_notes'] = $closingNotes;
            }
        }

        // 分段明细存在自己的表里，不是 medical_cases 的列
        $caseItems = $data['case_items'] ?? null;
        unset($data['case_items']);

        $diagnosisRows = $data['diagnosis_rows'] ?? null;
        unset($data['diagnosis_rows']);

        $case->increment('version_number');
        $status = MedicalCase::where('id', $id)->update($data);

        if ($status !== false && $caseItems !== null) {
            $this->syncCaseItems($case, $caseItems);
        }

        if ($status !== false && $diagnosisRows !== null) {
            $this->syncDiagnoses($case, $diagnosisRows);
        }

        // If transitioning from draft to submitted, lock the record
        if (!$isDraft && $case->is_draft) {
            $case->refresh();
            $case->lock();
        }

        // 复诊待办跟着最新的 next_visit_date 走。必须 refresh：上面走的是
        // MedicalCase::where()->update()，$case 手里还是更新前的值，不刷新会拿旧日期。
        if ($status !== false && !$isDraft) {
            $case->refresh();
            $this->syncFollowupFromCase($case);
        }

        return ['status' => $status !== false];
    }

    /**
     * 把病例里的「下次复诊日期」同步成一条随访待办。
     *
     * 为什么写进 patient_followups 而不是 appointments：
     *   医生写「两周后复诊」是**医嘱**，不是排期 —— 它没有时间、没有椅位，
     *   病人也还不知道。写进 appointments 会被当成已排期的号：
     *   NurseDashboardService / PharmacyDashboardService / ReceptionistDashboardService
     *   三处都是无条件的 Appointment::today()->count()，会把复诊建议算进「今日预约数」，
     *   护士看到 12 个、实际来 8 个。而 patient_followups 本来就是为这件事建的：
     *   它有 medical_case_id / appointment_id / scheduled_date / status，
     *   还有现成的 scopeOverdue()，护士仪表盘已经在显示 overdue_followups。
     *
     * 幂等键是 medical_case_id：一份病例最多一条自动生成的复诊待办。医生改了日期
     * 是 update 而不是再插一条 —— 否则改三次日期就留三条垃圾待办，比不做更糟。
     * 只处理 Pending 的那条：已经被人约掉（Completed）或取消的，不再回头改动。
     */
    public function syncFollowupFromCase(MedicalCase $case): void
    {
        $existing = PatientFollowup::where('medical_case_id', $case->id)
            ->where('status', PatientFollowup::STATUS_PENDING)
            ->first();

        // 两种情况都要撤销待办，不留孤儿：
        //   日期被清空          = 医嘱撤回
        //   医生取消了勾选      = 条件性复诊（「若症状持续，两周后复诊」）——那是写给
        //                        病人和病历看的，不该让前台去打召回电话。无差别建待办，
        //                        前台会召回一批本来不用来的人，几周后就没人信这个列表了。
        if (empty($case->next_visit_date) || !$case->auto_create_followup) {
            if ($existing) {
                $existing->update(['status' => PatientFollowup::STATUS_CANCELLED]);
            }
            return;
        }

        $scheduledDate = $case->next_visit_date instanceof \DateTimeInterface
            ? $case->next_visit_date->format('Y-m-d')
            : (string) $case->next_visit_date;
        $purpose = $case->next_visit_note ?: __('medical_cases.followup_from_case_purpose');

        if ($existing) {
            $existing->update([
                'scheduled_date' => $scheduledDate,
                'purpose'        => $purpose,
            ]);
            return;
        }

        // followup_type 是 enum('Phone','SMS','Email','Visit','Other')：到店复诊用 Visit
        $this->followupService->createFollowup([
            'followup_type'   => 'Visit',
            'scheduled_date'  => $scheduledDate,
            'purpose'         => $purpose,
            'notes'           => $case->next_visit_note,
            'patient_id'      => $case->patient_id,
            'medical_case_id' => $case->id,
        ]);
    }

    /**
     * Create an amendment request for a locked medical case.
     */
    public function createAmendment(MedicalCase $case, array $newData, string $reason): MedicalCaseAmendment
    {
        // Compute changed fields only
        $oldValues = [];
        $newValues = [];
        $amendmentFields = [];

        foreach ($newData as $key => $value) {
            $original = $case->getOriginal($key);
            if ($original != $value && in_array($key, $case->getFillable())) {
                $oldValues[$key] = $original;
                $newValues[$key] = $value;
                $amendmentFields[] = $key;
            }
        }

        return MedicalCaseAmendment::create([
            'medical_case_id' => $case->id,
            'requested_by' => Auth::id(),
            'amendment_reason' => $reason,
            'amendment_fields' => $amendmentFields,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'status' => MedicalCaseAmendment::STATUS_PENDING,
        ]);
    }

    /**
     * Delete a medical case (soft-delete).
     */
    public function deleteCase(int $id): bool
    {
        OperationLog::logDelete('medical', 'MedicalCase', $id);
        return (bool) MedicalCase::where('id', $id)->delete();
    }

    /**
     * Get print data for a medical case.
     */
    public function getPrintData(int $id): array
    {
        $case = MedicalCase::with(['patient', 'doctor', 'addedBy'])->findOrFail($id);

        $diagnoses = Diagnosis::where('medical_case_id', $id)
            ->whereNull('deleted_at')
            ->orderBy('diagnosis_date', 'desc')
            ->get();

        $treatmentPlans = TreatmentPlan::where('medical_case_id', $id)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->get();

        // vital_signs 上没有 medical_case_id 这一列 —— 体征是按患者/预约记录的
        // （见 VitalSignController::store），而病历表本身也只有 patient_id。
        // 原先按 medical_case_id 查，SQL 直接抛 Unknown column，
        // 也就是说 /print-medical-case/{id} 从来没有成功过一次。
        // 取该患者最近一次体征，与 VitalSignService::getByPatient() 的口径一致。
        // SoftDeletes 已挂在模型上，不必再手写 whereNull('deleted_at')。
        $latestVitalSign = VitalSign::where('patient_id', $case->patient_id)
            ->orderBy('recorded_at', 'desc')
            ->first();

        // Include audit trail for compliance PDF
        $auditTrail = $case->audits()->with('user')->latest()->take(10)->get();

        return compact('case', 'diagnoses', 'treatmentPlans', 'latestVitalSign', 'auditTrail');
    }

    /**
     * Search ICD-10 codes.
     */
    public function searchIcd10(string $query = ''): array
    {
        $icd10Codes = [
            ['id' => 'K00.0', 'text' => 'K00.0 - ' . __('odontogram.anodontia')],
            ['id' => 'K00.1', 'text' => 'K00.1 - ' . __('odontogram.supernumerary_teeth')],
            ['id' => 'K01.0', 'text' => 'K01.0 - ' . __('odontogram.embedded_teeth')],
            ['id' => 'K01.1', 'text' => 'K01.1 - ' . __('odontogram.impacted_teeth')],
            ['id' => 'K02.0', 'text' => 'K02.0 - ' . __('odontogram.caries_enamel')],
            ['id' => 'K02.1', 'text' => 'K02.1 - ' . __('odontogram.caries_dentin')],
            ['id' => 'K02.2', 'text' => 'K02.2 - ' . __('odontogram.caries_cementum')],
            ['id' => 'K02.3', 'text' => 'K02.3 - ' . __('odontogram.arrested_caries')],
            ['id' => 'K03.0', 'text' => 'K03.0 - ' . __('odontogram.attrition')],
            ['id' => 'K03.1', 'text' => 'K03.1 - ' . __('odontogram.abrasion')],
            ['id' => 'K03.2', 'text' => 'K03.2 - ' . __('odontogram.erosion')],
            ['id' => 'K04.0', 'text' => 'K04.0 - ' . __('odontogram.pulpitis')],
            ['id' => 'K04.1', 'text' => 'K04.1 - ' . __('odontogram.pulp_necrosis')],
            ['id' => 'K04.4', 'text' => 'K04.4 - ' . __('odontogram.acute_apical_periodontitis')],
            ['id' => 'K04.5', 'text' => 'K04.5 - ' . __('odontogram.chronic_apical_periodontitis')],
            ['id' => 'K04.6', 'text' => 'K04.6 - ' . __('odontogram.periapical_abscess')],
            ['id' => 'K04.7', 'text' => 'K04.7 - ' . __('odontogram.periapical_abscess_sinus')],
            ['id' => 'K05.0', 'text' => 'K05.0 - ' . __('odontogram.acute_gingivitis')],
            ['id' => 'K05.1', 'text' => 'K05.1 - ' . __('odontogram.chronic_gingivitis')],
            ['id' => 'K05.2', 'text' => 'K05.2 - ' . __('odontogram.acute_periodontitis')],
            ['id' => 'K05.3', 'text' => 'K05.3 - ' . __('odontogram.chronic_periodontitis')],
            ['id' => 'K05.4', 'text' => 'K05.4 - ' . __('odontogram.periodontosis')],
            ['id' => 'K06.0', 'text' => 'K06.0 - ' . __('odontogram.gingival_recession')],
            ['id' => 'K06.1', 'text' => 'K06.1 - ' . __('odontogram.gingival_enlargement')],
            ['id' => 'K07.3', 'text' => 'K07.3 - ' . __('odontogram.tooth_position_anomaly')],
            ['id' => 'K08.0', 'text' => 'K08.0 - ' . __('odontogram.exfoliation_systemic')],
            ['id' => 'K08.1', 'text' => 'K08.1 - ' . __('odontogram.loss_due_accident')],
            ['id' => 'K08.2', 'text' => 'K08.2 - ' . __('odontogram.loss_due_periodontal')],
            ['id' => 'K08.3', 'text' => 'K08.3 - ' . __('odontogram.retained_root')],
        ];

        if ($query) {
            $icd10Codes = array_filter($icd10Codes, function ($code) use ($query) {
                return stripos($code['id'], $query) !== false || stripos($code['text'], $query) !== false;
            });
        }

        return array_values($icd10Codes);
    }

    /**
     * Build DataTable response for the medical cases index listing.
     *
     * @param Collection $data
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function buildIndexDataTable($data)
    {
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('statusBadge', function ($row) {
                $badgeMap = [
                    MedicalCase::STATUS_OPEN => 'success',
                    MedicalCase::STATUS_CLOSED => 'danger',
                    MedicalCase::STATUS_FOLLOW_UP => 'warning',
                ];
                $class = $badgeMap[$row->status] ?? 'default';
                $label = DictItem::nameByCode('medical_case_status', $row->status) ?? $row->status;
                $html = '<span class="label label-' . $class . '">' . e($label) . '</span>';
                if (!empty($row->pending_amendments_count)) {
                    $html .= ' <span class="label label-warning" title="' . __('medical_cases.amendment_pending') . '"><i class="fa fa-clock-o"></i> ' . $row->pending_amendments_count . '</span>';
                }
                return $html;
            })
            ->addColumn('action', function ($row) {
                return ActionColumnHelper::make($row->id)
                    ->add('view')
                    ->primaryIf($row->deleted_at == null, 'edit')
                    ->add('export_pdf', __('medical_cases.export_pdf'), '/medical-cases/' . $row->id . '/export-pdf')
                    ->add('delete')
                    ->render();
            })
            ->rawColumns(['statusBadge', 'action'])
            ->make(true);
    }

    /**
     * Build DataTable response for a patient's medical cases listing.
     *
     * @param Collection $data
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function buildPatientCasesDataTable($data)
    {
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('viewBtn', function ($row) {
                return '<a href="' . url('medical-cases/' . $row->id) . '" class="btn btn-info btn-sm">' . __('common.view') . '</a>';
            })
            ->addColumn('statusBadge', function ($row) {
                $badgeMap = [
                    MedicalCase::STATUS_OPEN => 'success',
                    MedicalCase::STATUS_CLOSED => 'danger',
                    MedicalCase::STATUS_FOLLOW_UP => 'warning',
                ];
                $class = $badgeMap[$row->status] ?? 'default';
                $label = DictItem::nameByCode('medical_case_status', $row->status) ?? $row->status;
                return '<span class="label label-' . $class . '">' . e($label) . '</span>';
            })
            ->rawColumns(['viewBtn', 'statusBadge'])
            ->make(true);
    }

    /**
     * Get active doctors.
     */
    private function getDoctors(): Collection
    {
        return User::where('is_doctor', true)->whereNull('deleted_at')->where('status', User::STATUS_ACTIVE)->orderBy('surname')->get();
    }
}
