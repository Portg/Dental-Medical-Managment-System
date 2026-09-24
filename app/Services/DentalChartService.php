<?php

namespace App\Services;

use App\Appointment;
use App\DentalChart;
use App\MedicalCase;
use App\MedicalCaseItem;
use App\Patient;
use App\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DentalChartService
{
    /**
     * 旧版 Angular 牙位图只存 color 编号，没有 tooth_status。
     * 写入和读取都用这张表把颜色编号折算成状态。
     *
     * public：public/include_js/dental_chart_editor.js 里有一份同样的映射，
     * DentalChartColorMapParityTest 用这个常量断言两份不漂移。
     */
    public const COLOR_TO_STATUS = [
        '1' => 'filled', '2' => 'caries', '3' => 'rct', '4' => 'missing',
        '6' => 'implant', '8' => 'crown', '11' => 'impacted',
    ];

    /**
     * dental_charts.tooth_status 的合法枚举值，与 2026_01_17_800003 建列时一致。
     *
     * 该列是 NOT NULL enum，写进不在表内的值会被 MySQL 严格模式拒绝（1265），
     * 非严格模式下则静默截断成空串。写入端一律先过这张白名单。
     */
    public const TOOTH_STATUSES = [
        'normal', 'caries', 'filled', 'crown', 'rct',
        'missing', 'implant', 'pontic', 'extraction_planned', 'impacted',
        'residual_root',
    ];

    /**
     * Get patients with dental chart records for DataTables.
     */
    public function getPatientChartList(): \Illuminate\Database\Query\Builder
    {
        return DB::table('dental_charts')
            ->join('appointments', 'dental_charts.appointment_id', '=', 'appointments.id')
            ->join('patients', 'appointments.patient_id', '=', 'patients.id')
            ->whereNull('dental_charts.deleted_at')
            ->whereNull('patients.deleted_at')
            ->select(
                'patients.id as patient_id',
                'patients.patient_no',
                DB::raw(app()->getLocale() === 'zh-CN' ? "CONCAT(patients.surname, patients.othername) as patient_name" : "CONCAT(patients.surname, ' ', patients.othername) as patient_name"),
                DB::raw('COUNT(dental_charts.id) as tooth_count'),
                DB::raw('MAX(dental_charts.updated_at) as last_updated')
            )
            ->groupBy('patients.id', 'patients.patient_no', 'patients.surname', 'patients.othername')
            ->orderBy('last_updated', 'desc');
    }

    /**
     * Get the latest usable appointment for a patient.
     */
    public function getLatestAppointment(int $patientId): ?Appointment
    {
        return Appointment::where('patient_id', $patientId)
            ->whereNotIn('status', [
                Appointment::STATUS_CANCELLED,
                Appointment::STATUS_NO_SHOW,
                Appointment::STATUS_REJECTED,
            ])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Resolve an appointment to open the chart editor for a patient.
     *
     * Chart rows are keyed by appointment_id, but load/save already scope by
     * patient — so any historical appointment can carry the editor session.
     * Only create a lightweight today visit when the patient has never had one.
     */
    public function resolveAppointmentForChart(int $patientId): Appointment
    {
        $patient = Patient::whereNull('deleted_at')->findOrFail($patientId);

        $existing = $this->getLatestAppointment((int) $patient->id);
        if ($existing) {
            return $existing;
        }

        // 取消/爽约等也复用，避免仅为图表再插一条出现在排班/今日工作里
        $anyAppointment = Appointment::where('patient_id', $patient->id)
            ->orderByDesc('id')
            ->first();
        if ($anyAppointment) {
            return $anyAppointment;
        }

        $user = Auth::user();
        if (!$user) {
            throw new \RuntimeException(__('odontogram.no_doctor_for_chart'));
        }

        $doctorId = $this->resolveDoctorIdForChart();
        if (!$doctorId) {
            throw new \RuntimeException(__('odontogram.no_doctor_for_chart'));
        }

        $today = now()->toDateString();
        // appointments.start_time 是 varchar，存什么显示什么，业务粒度为分钟，
        // 故直接存 HH:MM，不要存 HH:MM:SS 再到展示层截断。
        $nowTime = now()->format('H:i');
        // sort_by 是 datetime 列，用于排序与区间筛选，补足秒位
        $sortBy = $today . ' ' . $nowTime . ':00';

        return Appointment::create([
            'appointment_no'    => Appointment::AppointmentNo(),
            'patient_id'        => $patient->id,
            'doctor_id'         => $doctorId,
            'start_date'        => $today,
            'end_date'          => $today,
            'start_time'        => $nowTime,
            'duration_minutes'  => 30,
            'appointment_type'  => 'consultation',
            'source'            => 'walk_in',
            'visit_information' => Appointment::VISIT_WALK_IN,
            'status'            => Appointment::STATUS_SCHEDULED,
            // 不写面向用户的备注：会污染「就诊记录」摘要；仅作图表数据载体即可
            'notes'             => null,
            'branch_id'         => $user->branch_id,
            'sort_by'           => $sortBy,
            '_who_added'        => $user->id,
        ]);
    }

    /**
     * Get patient model for dental chart editor page.
     */
    public function getPatientForChart(int $appointmentId): ?Patient
    {
        $appointment = Appointment::where('id', $appointmentId)->first();
        if (!$appointment) {
            return null;
        }

        return Patient::whereNull('deleted_at')->find($appointment->patient_id);
    }

    private function resolveDoctorIdForChart(): ?int
    {
        $user = Auth::user();
        if ($user && $user->is_doctor) {
            return (int) $user->id;
        }

        $doctorId = User::where('is_doctor', true)
            ->where('status', User::STATUS_ACTIVE)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->value('id');

        return $doctorId ? (int) $doctorId : null;
    }

    /**
     * Replace all dental chart entries for a patient with new data.
     */
    public function replaceChartData(int $appointmentId, array $chartData): bool
    {
        $appointment = Appointment::where('id', $appointmentId)->first();
        if (!$appointment) {
            throw new \InvalidArgumentException(__('odontogram.patient_not_found'));
        }

        // Delete all previous patient dental chart records
        DB::table('dental_charts')
            ->leftJoin('appointments', 'appointments.id', 'dental_charts.appointment_id')
            ->where('appointments.patient_id', $appointment->patient_id)
            ->delete();

        foreach ($chartData as $value) {
            $tooth = $value['tooth_number'] ?? $value['tooth'] ?? null;
            if ($tooth === null || $tooth === '') {
                continue;
            }
            $tooth = (int) $tooth;
            // Legacy Angular payload used "position"; new editor uses section/null
            $section = $value['section'] ?? $value['position'] ?? null;

            DentalChart::create([
                'tooth' => $tooth,
                'tooth_number' => $tooth,
                'tooth_type' => $value['tooth_type'] ?? ($tooth >= 51 ? 'primary' : 'permanent'),
                'tooth_status' => $this->resolveToothStatus($value),
                'section' => $section,
                'color' => $value['color'] ?? null,
                'surface' => $value['surface'] ?? null,
                'appointment_id' => $appointmentId,
                'doctor_id' => Auth::id(),
                '_who_added' => Auth::id(),
            ]);
        }

        return true;
    }

    /**
     * 解析单个牙位要写入的 tooth_status。
     *
     * tooth_status 是 NOT NULL enum（默认 normal），既不能写 null，也不能写枚举外的值。
     * 优先级：请求给的合法状态 → 按旧版 color 编号折算 → normal。
     *
     * 兜底放在 Service 而非只放在 API 校验里：Web 端 DentalChartController::store()
     * 直接把请求体透传进来，没有 Validator 把关，两个入口共用这一道闸。
     */
    private function resolveToothStatus(array $value): string
    {
        $status = $value['tooth_status'] ?? null;

        if (is_string($status) && in_array($status, self::TOOTH_STATUSES, true)) {
            return $status;
        }

        return self::COLOR_TO_STATUS[(string) ($value['color'] ?? '')] ?? 'normal';
    }

    /**
     * Get all dental chart entries for a patient by appointment ID.
     */
    public function getChartByAppointment(int $appointmentId): Collection
    {
        $appointment = Appointment::where('id', $appointmentId)->first();
        if (!$appointment) {
            return collect();
        }

        return DB::table('dental_charts')
            ->leftJoin('appointments', 'appointments.id', 'dental_charts.appointment_id')
            ->whereNull('dental_charts.deleted_at')
            ->where('appointments.patient_id', $appointment->patient_id)
            ->select('dental_charts.*')
            ->get();
    }

    /**
     * Current dental-chart summary for patient detail page.
     *
     * Chart rows are stored per appointment but scoped by patient on load/save;
     * there is no versioned history — only the latest mark set.
     */
    /**
     * 牙位标记 → 牙位状态。
     *
     *     △ residual_root → residual_root  牙冠没了、牙根还在
     *     ✕ extracted     → missing        牙已经不在
     *     — missing       → missing
     *
     * ✕ 与 — 都落到 missing：就**当前状态**而言，拔掉的和天生缺的没有区别；
     * 区别在于「怎么没的」，那是病历里记的事，不该塞进状态枚举。
     */
    /**
     * public：DentalChartColorMapParityTest 用它断言「投影能写出来的状态，
     * 牙位图编辑器都认识」—— 编辑器的 buildPayload 会跳过 STATUS_MAP 里没有的
     * 状态，漏一个就意味着医生一点保存就把投影抹掉，且不会有任何报错。
     */
    public const MARK_TO_STATUS = [
        MedicalCaseItem::MARK_RESIDUAL_ROOT => 'residual_root',
        MedicalCaseItem::MARK_EXTRACTED     => 'missing',
        MedicalCaseItem::MARK_MISSING       => 'missing',
    ];

    /**
     * 哪些段落的标记能投影成牙位状态。
     *
     * 检查 / 其他检查  —— 记的是「我看到这颗牙现在是什么样」，是事实
     * 治疗            —— 记的是「这次做了什么」，做完牙的状态就变了，也是事实
     * **治疗计划不在其列** —— 它记的是「打算做什么」。医生在治疗计划里给一颗牙
     *   标 ✕ 表示「计划拔除」，那颗牙此刻还在嘴里；投影成 missing 等于把打算
     *   当成了既成事实，牙位图上会显示一颗根本没拔的牙已经没了。
     */
    private const PROJECTABLE_SECTIONS = [
        MedicalCaseItem::SECTION_EXAMINATION,
        MedicalCaseItem::SECTION_AUXILIARY,
        MedicalCaseItem::SECTION_TREATMENT,
    ];

    /**
     * 把一份病历里的牙位标记投影成牙位状态。
     *
     * dental_charts 存的是牙齿的**当前状态**，它该由历次临床观察派生 —— 而不是
     * 另开一个录入口让人手工维护。那张表此前只有 1 条记录，正是因为从来没有东西
     * 去喂它：医生在病历里写「45 残根」，牙位图上 45 还是 normal，于是「45 这颗牙
     * 历次做过什么」根本查不出来。
     *
     * 整份替换（先删这份病历投出去的旧记录再重建），与 syncCaseItems 同样的口径：
     * 医生把标记改掉或去掉时，旧的投影必须跟着消失，否则会留下永远撤不掉的状态。
     */
    public function projectFromMedicalCase(MedicalCase $case): void
    {
        DB::transaction(function () use ($case) {
            // 这份病历以前投出去的，整批撤掉（软删，审计链不断）
            DentalChart::where('medical_case_id', $case->id)->delete();

            $rows = MedicalCaseItem::where('medical_case_id', $case->id)
                ->whereIn('section', self::PROJECTABLE_SECTIONS)
                ->whereNotNull('tooth_mark')
                ->whereNotNull('tooth_no')
                ->orderBy('sort_order')->orderBy('id')
                ->get(['tooth_no', 'tooth_mark', 'content']);

            // 同一颗牙在几段里都标了：以最后一条为准（治疗排在检查之后，
            // 「这次拔掉了」应当盖过「检查时是残根」）
            $byTooth = [];
            foreach ($rows as $row) {
                // 象限码（一位数字的 tooth_no）说的是「这个区有颗牙怎么样」，
                // 不是某颗牙。这张表一行就是一颗牙的当前状态，写不出「某区的某颗牙」
                // —— 硬写会落成 tooth_number='1'，而 1 在 FDI 里不是牙位，
                // 牙位图上会多出一颗根本不存在的牙。
                if (MedicalCaseItem::isQuadrantCode((string) $row->tooth_no)) {
                    continue;
                }

                $status = self::MARK_TO_STATUS[$row->tooth_mark] ?? null;
                if ($status === null) {
                    continue;
                }
                $byTooth[(string) $row->tooth_no] = ['status' => $status, 'notes' => $row->content];
            }

            $appointmentId = \App\Appointment::where('medical_case_id', $case->id)->value('id');

            foreach ($byTooth as $tooth => $info) {
                DentalChart::create([
                    'tooth'           => is_numeric($tooth) ? (float) $tooth : null,
                    'tooth_number'    => $tooth,
                    'tooth_status'    => $info['status'],
                    'notes'           => $info['notes'],
                    'medical_case_id' => $case->id,
                    // 带上就诊。关联方向是反的 —— medical_cases 上没有 appointment_id，
                    // 是 appointments.medical_case_id 指过来（一次就诊一份病历）。
                    // 带上它是因为 getChartSummaryForPatient 按 appointment 找患者，
                    // 不带的话这条记录对那份汇总是隐形的；下面把那个查询也补上了
                    // 走 medical_case 的路，两头都兜住。
                    'appointment_id'  => $appointmentId,
                    'doctor_id'       => $case->doctor_id,
                    '_who_added'      => Auth::id() ?? $case->_who_added,
                ]);
            }
        });
    }

    public function getChartSummaryForPatient(int $patientId): array
    {
        $COLOR_TO_STATUS = self::COLOR_TO_STATUS;
        // residual_root 排在 impacted 之后、crown 之前：牙冠没了比任何修复体状态都
        // 更该被一眼看到，但比「牙已经不在」弱一档。
        $STATUS_PRIORITY = ['missing', 'implant', 'impacted', 'residual_root', 'crown', 'rct', 'filled', 'caries'];
        $SHORT_KEYS = [
            'caries' => 'short_caries', 'filled' => 'short_filled', 'rct' => 'short_rct',
            'crown' => 'short_crown', 'missing' => 'short_missing', 'implant' => 'short_implant',
            'impacted' => 'short_impacted', 'residual_root' => 'short_residual_root',
        ];

        // 患者要从两条路认：预约，或者病历。
        //
        // 原来只 join appointments（还是 inner join）—— 只挂 medical_case_id 没挂
        // appointment_id 的记录对这份汇总完全隐形。病历投影出来的状态正是这种：
        // 从患者页建的病历没有对应预约。漏掉的不会报错，只是牙位图上少几颗牙的
        // 状态，最难发现。
        $rows = DB::table('dental_charts')
            ->leftJoin('appointments', 'appointments.id', '=', 'dental_charts.appointment_id')
            ->leftJoin('medical_cases', 'medical_cases.id', '=', 'dental_charts.medical_case_id')
            ->whereNull('dental_charts.deleted_at')
            ->where(function ($q) use ($patientId) {
                $q->where(function ($a) use ($patientId) {
                    $a->where('appointments.patient_id', $patientId)
                      ->whereNull('appointments.deleted_at');
                })->orWhere(function ($m) use ($patientId) {
                    $m->where('medical_cases.patient_id', $patientId)
                      ->whereNull('medical_cases.deleted_at');
                });
            })
            ->select(
                'dental_charts.tooth_number',
                'dental_charts.tooth',
                'dental_charts.tooth_status',
                'dental_charts.color',
                'dental_charts.updated_at'
            )
            ->orderBy('dental_charts.tooth_number')
            ->get();

        $byTooth = [];
        $lastUpdated = null;
        foreach ($rows as $row) {
            $tooth = (string) ($row->tooth_number ?: $row->tooth);
            if ($tooth === '' || $tooth === '0') {
                continue;
            }
            if (!isset($byTooth[$tooth])) {
                $byTooth[$tooth] = [];
            }
            $st = $row->tooth_status ?: ($COLOR_TO_STATUS[(string) $row->color] ?? null);
            // normal 是"没问题"，不该出现在摘要标记里
            if ($st && $st !== 'normal') {
                $byTooth[$tooth][] = $st;
            }
            if ($row->updated_at && ($lastUpdated === null || $row->updated_at > $lastUpdated)) {
                $lastUpdated = $row->updated_at;
            }
        }

        $marks = [];
        foreach ($byTooth as $tooth => $statuses) {
            $statuses = array_values(array_unique($statuses));
            $primary = null;
            foreach ($STATUS_PRIORITY as $candidate) {
                if (in_array($candidate, $statuses, true)) {
                    $primary = $candidate;
                    break;
                }
            }
            $primary = $primary ?: ($statuses[0] ?? null);
            if (!$primary) {
                continue;
            }
            $shortKey = $SHORT_KEYS[$primary] ?? $primary;
            $marks[] = [
                'tooth' => $tooth,
                'status' => $primary,
                'label' => __('odontogram.' . $shortKey),
            ];
        }

        usort($marks, fn ($a, $b) => (int) $a['tooth'] <=> (int) $b['tooth']);

        return [
            'tooth_count' => count($marks),
            'last_updated' => $lastUpdated,
            'marks' => $marks,
        ];
    }
}
