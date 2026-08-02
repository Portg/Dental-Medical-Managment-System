<?php

namespace App\Services;

use App\Appointment;
use App\DentalChart;
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
     */
    private const COLOR_TO_STATUS = [
        '1' => 'filled', '2' => 'caries', '3' => 'rct', '4' => 'missing',
        '6' => 'implant', '8' => 'crown', '11' => 'impacted',
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
                // tooth_status 是 NOT NULL enum（默认 normal），不能写 null。
                // 没给状态时先按旧版 color 编号折算，折算不出就落回 normal。
                'tooth_status' => $value['tooth_status']
                    ?? (self::COLOR_TO_STATUS[(string) ($value['color'] ?? '')] ?? 'normal'),
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
    public function getChartSummaryForPatient(int $patientId): array
    {
        $COLOR_TO_STATUS = self::COLOR_TO_STATUS;
        $STATUS_PRIORITY = ['missing', 'implant', 'impacted', 'crown', 'rct', 'filled', 'caries'];
        $SHORT_KEYS = [
            'caries' => 'short_caries', 'filled' => 'short_filled', 'rct' => 'short_rct',
            'crown' => 'short_crown', 'missing' => 'short_missing', 'implant' => 'short_implant',
            'impacted' => 'short_impacted',
        ];

        $rows = DB::table('dental_charts')
            ->join('appointments', 'appointments.id', '=', 'dental_charts.appointment_id')
            ->whereNull('dental_charts.deleted_at')
            ->whereNull('appointments.deleted_at')
            ->where('appointments.patient_id', $patientId)
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
