<?php

namespace App\Services;

use App\MedicalCase;
use App\MedicalCaseItem;
use App\Patient;
use App\Http\Helper\NameHelper;
use Illuminate\Support\Facades\DB;

/**
 * 椅旁工作台的取数。
 *
 * 诊疗页原先是三层嵌套页签里塞了四张后台 CRUD 表，医生要在「牙齿图表」和
 * 「牙科记录」两个兄弟页签之间来回切 —— 而口腔科写病历时，人是看着 16 那颗牙
 * 在写 16 的诊断。这里把接诊真正需要的三件事一次取齐：
 *
 *   警示   —— 药物过敏、系统病。用药安全信息，必须常驻，不能藏在第三个页签里
 *   钱     —— 欠费（患者欠诊所）与预收未兑现（诊所欠患者），两个方向分开报
 *   治疗史 —— 这颗牙 / 这个人历次做过什么
 *
 * 治疗史是补的最要紧的一块：原来整页都在讲「今天」，而医生要知道的是累计，
 * 且不该每次都跑去别处查一遍。数据本来就有，只是没聚过。
 */
class ChairsideService
{
    private PrepaidItemService $prepaidItems;

    public function __construct(PrepaidItemService $prepaidItems)
    {
        $this->prepaidItems = $prepaidItems;
    }

    /**
     * 患者条：身份 + 临床警示 + 两个方向的钱。
     */
    public function patientBar(Patient $patient): array
    {
        $outstanding = (float) DB::table('invoices')
            ->where('patient_id', $patient->id)
            ->whereNull('deleted_at')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->sum('outstanding_amount');

        $openInvoices = (int) DB::table('invoices')
            ->where('patient_id', $patient->id)
            ->whereNull('deleted_at')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->count();

        $prepaid = $this->prepaidItems->getPatientSummary($patient->id);

        return [
            'id'          => $patient->id,
            'name'        => $patient->full_name,
            'patient_no'  => $patient->patient_no,
            'gender'      => $patient->gender,
            'age'         => $patient->age ?: null,
            'phone'       => $patient->phone_no,
            'alerts'      => $this->alerts($patient),
            'outstanding' => round($outstanding, 2),
            'open_invoices' => $openInvoices,
            'prepaid_value' => $prepaid['prepaid_value'],
            'prepaid_qty'   => $prepaid['remaining_qty'],
        ];
    }

    /**
     * 临床警示 —— 只收会影响今天用药与操作的两类：药物过敏、系统病。
     *
     * 刻意不把「吸烟」「妊娠」之类一起塞进来：警示位一旦什么都往里放，
     * 医生就不再看它了，而这一条的全部价值就是扎眼。
     */
    private function alerts(Patient $patient): array
    {
        $alerts = [];

        foreach ((array) $patient->drug_allergies as $item) {
            if ($item !== '' && $item !== null) {
                $alerts[] = ['type' => 'allergy', 'text' => (string) $item];
            }
        }
        if (!empty($patient->drug_allergies_other)) {
            $alerts[] = ['type' => 'allergy', 'text' => (string) $patient->drug_allergies_other];
        }

        foreach ((array) $patient->systemic_diseases as $item) {
            if ($item !== '' && $item !== null) {
                $alerts[] = ['type' => 'systemic', 'text' => (string) $item];
            }
        }
        if (!empty($patient->systemic_diseases_other)) {
            $alerts[] = ['type' => 'systemic', 'text' => (string) $patient->systemic_diseases_other];
        }

        return $alerts;
    }

    /**
     * 牙位当前状态 —— 牙位图的底色，也是「这个人做过什么」的第一层答案。
     *
     * 一颗牙可能被标过很多次，只取最后一次：牙位图表达的是**现状**，
     * 历次过程由 toothHistory() 负责。
     */
    public function toothStates(int $patientId): array
    {
        $rows = DB::table('dental_charts as dc')
            ->join('medical_cases as mc', 'mc.id', '=', 'dc.medical_case_id')
            ->where('mc.patient_id', $patientId)
            ->whereNull('dc.deleted_at')
            ->whereNull('mc.deleted_at')
            ->whereNotNull('dc.tooth_number')
            ->orderBy('dc.changed_at')
            ->orderBy('dc.id')
            ->select('dc.tooth_number', 'dc.tooth_status', 'dc.color')
            ->get();

        $states = [];
        foreach ($rows as $row) {
            $states[(string) $row->tooth_number] = [
                'status' => $row->tooth_status,
                'color'  => $row->color,
            ];
        }

        return $states;
    }

    /**
     * 这颗牙历次做过什么。
     *
     * 取治疗段与诊断段：医生问「这颗牙以前怎么了」时，要的是这两样，
     * 检查所见太细、治疗计划是还没发生的事，都不该混进时间线。
     *
     * tooth_no 是一行里可能写了多颗（「16,17」），所以用 FIND_IN_SET 而不是
     * 等值比较 —— 直接 = '16' 会漏掉「16,17 残根」那一行。
     */
    public function toothHistory(int $patientId, string $toothNo, int $limit = 20): array
    {
        return $this->historyQuery($patientId)
            ->whereRaw("FIND_IN_SET(?, REPLACE(mci.tooth_no, ' ', ''))", [$toothNo])
            ->limit($limit)
            ->get()
            ->map(fn ($row) => $this->presentHistoryRow($row))
            ->toArray();
    }

    /**
     * 全口历次就诊 —— 不选牙位时看的那份，一次就诊一行。
     */
    public function visitHistory(int $patientId, int $limit = 15): array
    {
        $cases = DB::table('medical_cases as mc')
            ->leftJoin('users as d', 'd.id', '=', 'mc.doctor_id')
            ->where('mc.patient_id', $patientId)
            ->whereNull('mc.deleted_at')
            ->orderByDesc('mc.case_date')
            ->orderByDesc('mc.id')
            ->limit($limit)
            ->select('mc.id', 'mc.case_date', 'mc.visit_type',
                'd.surname as d_surname', 'd.othername as d_othername')
            ->get();

        if ($cases->isEmpty()) {
            return [];
        }

        // 一次查完所有治疗段，再在内存里归到各次就诊下 ——
        // 每次就诊单独查一遍是 N+1，而这个列表一打开页面就要出
        $items = DB::table('medical_case_items')
            ->whereIn('medical_case_id', $cases->pluck('id'))
            ->where('section', MedicalCaseItem::SECTION_TREATMENT)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->select('medical_case_id', 'tooth_no', 'content')
            ->get()
            ->groupBy('medical_case_id');

        return $cases->map(function ($case) use ($items) {
            $done = ($items[$case->id] ?? collect())
                ->map(fn ($i) => trim(($i->tooth_no ? $i->tooth_no . ' ' : '') . $i->content))
                ->filter()
                ->implode('；');

            return [
                'case_id'     => $case->id,
                'date'        => $case->case_date ? date('Y-m-d', strtotime((string) $case->case_date)) : '',
                'visit_type'  => $case->visit_type,
                'doctor_name' => NameHelper::join($case->d_surname, $case->d_othername),
                'summary'     => $done,
            ];
        })->toArray();
    }

    private function historyQuery(int $patientId)
    {
        return DB::table('medical_case_items as mci')
            ->join('medical_cases as mc', 'mc.id', '=', 'mci.medical_case_id')
            ->leftJoin('users as d', 'd.id', '=', 'mc.doctor_id')
            ->where('mc.patient_id', $patientId)
            ->whereIn('mci.section', [
                MedicalCaseItem::SECTION_TREATMENT,
                MedicalCaseItem::SECTION_DIAGNOSIS,
            ])
            ->whereNull('mci.deleted_at')
            ->whereNull('mc.deleted_at')
            ->orderByDesc('mc.case_date')
            ->orderByDesc('mci.id')
            ->select('mci.section', 'mci.tooth_no', 'mci.content',
                'mc.id as case_id', 'mc.case_date',
                'd.surname as d_surname', 'd.othername as d_othername');
    }

    private function presentHistoryRow(object $row): array
    {
        return [
            'case_id'     => $row->case_id,
            'date'        => $row->case_date ? date('Y-m-d', strtotime((string) $row->case_date)) : '',
            'section'     => $row->section,
            'tooth_no'    => $row->tooth_no ?? '',
            'content'     => $row->content ?? '',
            'doctor_name' => NameHelper::join($row->d_surname, $row->d_othername),
        ];
    }

    /**
     * 今天要做什么 —— 从治疗计划里还没完成的项目带过来。
     */
    public function pendingPlan(int $patientId, int $limit = 8): array
    {
        return DB::table('treatment_plans')
            ->where('patient_id', $patientId)
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->orderBy('priority')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'id'     => $row->id,
                'name'   => $row->plan_name,
                'teeth'  => $row->related_teeth ?? '',
                'status' => $row->status,
            ])
            ->toArray();
    }

    /**
     * 本次就诊的病历（有则给 id，供中间栏渲染病历纸 / 跳录入表单）。
     */
    public function currentCase(int $appointmentId): ?MedicalCase
    {
        // 就诊与病历的关联落在 appointments.medical_case_id 上（一次就诊一份病历）
        $caseId = DB::table('appointments')
            ->where('id', $appointmentId)
            ->value('medical_case_id');

        return $caseId ? MedicalCase::find($caseId) : null;
    }
}
