<?php

namespace App\Services;

use App\Appointment;
use App\AppointmentHistory;
use App\Chair;
use App\DictItem;
use App\DoctorSchedule;
use App\Shift;
use App\Http\Helper\FunctionsHelper;
use App\Http\Helper\NameHelper;
use App\Jobs\SendAppointmentSms;
use App\Notifications\ReminderNotification;
use App\Patient;
use App\SystemSetting;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Thomasjohnkane\Snooze\ScheduledNotification;
use Yajra\DataTables\DataTables;

class AppointmentService
{
    // ─── List / filter ────────────────────────────────────────────

    /**
     * Get filtered appointment list for DataTables.
     */
    public function getAppointmentList(array $filters): Collection
    {
        $query = DB::table('appointments')
            ->join('patients', 'patients.id', 'appointments.patient_id')
            ->join('users', 'users.id', 'appointments.doctor_id')
            ->leftJoin('invoices', 'invoices.appointment_id', 'appointments.id')
            ->whereNull('appointments.deleted_at')
            ->select(
                'appointments.*',
                'patients.surname', 'patients.othername', 'patients.phone_no',
                'users.surname as d_surname', 'users.othername as d_othername',
                DB::raw('DATE_FORMAT(appointments.start_date, "%Y-%m-%d") as start_date'),
                'invoices.id as invoice_id',
                DB::raw('CASE WHEN invoices.id IS NOT NULL THEN "invoiced" ELSE "pending" END as has_invoice_status')
            );

        // Quick search
        if (!empty($filters['quick_search'])) {
            $search = $filters['quick_search'];
            $query->where(function ($q) use ($search) {
                NameHelper::addNameSearch($q, $search, 'patients');
                $q->orWhere('patients.phone_no', 'like', '%' . $search . '%')
                  ->orWhere('appointments.appointment_no', 'like', '%' . $search . '%');
            });
        }

        // Appointment No filter
        if (!empty($filters['appointment_no'])) {
            $query->where('appointments.appointment_no', '=', $filters['appointment_no']);
        }

        // Date range filter
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween(
                DB::raw("DATE_FORMAT(appointments.sort_by, '%Y-%m-%d')"),
                [$filters['start_date'], $filters['end_date']]
            );
        }

        // Doctor filter
        if (!empty($filters['filter_doctor'])) {
            $query->where('appointments.doctor_id', $filters['filter_doctor']);
        }

        // Patient filter (患者详情「预约」Tab 传入)
        if (!empty($filters['patient_id'])) {
            $query->where('appointments.patient_id', (int) $filters['patient_id']);
        }

        // Invoice status filter
        if (!empty($filters['filter_invoice_status'])) {
            if ($filters['filter_invoice_status'] == 'invoiced') {
                $query->whereNotNull('invoices.id');
            } elseif ($filters['filter_invoice_status'] == 'pending') {
                $query->whereNull('invoices.id');
            }
        }

        // DataTables default search
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            if (is_array($search) && !empty($search['value'])) {
                $searchValue = $search['value'];
                $query->where(function ($q) use ($searchValue) {
                    NameHelper::addNameSearch($q, $searchValue, 'patients');
                });
            }
        }

        return $query->orderBy('appointments.sort_by', 'desc')->get();
    }

    /**
     * 在职医生列表，供筛选下拉等处使用。
     *
     * 不按排班过滤：筛选历史预约时需要能选到当日无排班的医生。
     */
    public function getDoctorOptions(): Collection
    {
        return DB::table('users')
            ->where('is_doctor', 1)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->select('id', 'surname', 'othername')
            ->orderBy('surname')
            ->get()
            ->map(fn ($row) => (object) [
                'id'   => $row->id,
                'name' => NameHelper::join($row->surname, $row->othername),
            ]);
    }

    /**
     * 预约状态的配色。
     *
     * 提成公共方法是因为它有**两个**消费方：日历事件块的底色，和状态筛选面板
     * 的色卡。两边各写一份的话，改了一处颜色，图例就和事件对不上 —— 而这个
     * 面板的全部作用就是「按颜色认状态」。
     */
    public static function statusColorMap(): array
    {
        return [
            Appointment::STATUS_WAITING => '#f0ad4e',
            Appointment::STATUS_SCHEDULED => '#5bc0de',
            Appointment::STATUS_CHECKED_IN => '#337ab7',
            Appointment::STATUS_IN_PROGRESS => '#5cb85c',
            Appointment::STATUS_COMPLETED => '#5cb85c',
            Appointment::STATUS_TREATMENT_COMPLETE => '#5cb85c',
            Appointment::STATUS_CANCELLED => '#d9534f',
            Appointment::STATUS_NO_SHOW => '#777777',
            Appointment::STATUS_RESCHEDULED => '#f0ad4e',
            Appointment::STATUS_REJECTED => '#d9534f',
        ];
    }

    /**
     * 状态筛选面板的数据：状态码 + 颜色 + 译名。
     *
     * 只列前台真会拿来筛的那些。TREATMENT_COMPLETE / TREATMENT_INCOMPLETE
     * 是历史遗留的旧枚举值，库里还有数据但新流程不再产生，摆进筛选面板
     * 只会让人以为是两种不同的「完成」。
     */
    public function filterableStatuses(): array
    {
        $colors = self::statusColorMap();

        $codes = [
            Appointment::STATUS_SCHEDULED,
            Appointment::STATUS_WAITING,
            Appointment::STATUS_CHECKED_IN,
            Appointment::STATUS_IN_PROGRESS,
            Appointment::STATUS_COMPLETED,
            Appointment::STATUS_RESCHEDULED,
            Appointment::STATUS_NO_SHOW,
            Appointment::STATUS_CANCELLED,
            Appointment::STATUS_REJECTED,
        ];

        // 译名走 translateStatus（字典表 appointment_status），与气泡、列表
        // 里显示的完全一致 —— 同一个状态在筛选面板叫一个名、在气泡里叫另一个，
        // 比不翻译还糟
        return array_map(fn ($code) => [
            'code'  => $code,
            'color' => $colors[$code] ?? '#3a87ad',
            'label' => $this->translateStatus($code),
        ], $codes);
    }

    /**
     * Get calendar events for FullCalendar.
     */
    public function getCalendarEvents(?string $start, ?string $end): array
    {
        $query = DB::table('appointments')
            ->join('patients', 'patients.id', 'appointments.patient_id')
            ->join('users', 'users.id', 'appointments.doctor_id')
            ->leftJoin('medical_services', 'medical_services.id', 'appointments.service_id')
            // 诊室泳道视图按 chair_id 分列，没有这个 join 每个事件都落不进任何一列
            ->leftJoin('chairs', 'chairs.id', 'appointments.chair_id')
            ->whereNull('appointments.deleted_at')
            ->select(
                'appointments.*',
                'patients.surname', 'patients.othername', 'patients.phone_no as p_phone',
                'patients.gender as p_gender',
                'users.surname as d_surname', 'users.othername as d_othername',
                'medical_services.name as service_name',
                'chairs.chair_name as chair_name'
            )
            // 一次预约可能约了多个项目；主项目（service_id）只是其中第一个。
            // 子查询而不是 join + GROUP BY，见 Appointment::serviceNamesSubquery
            ->addSelect(Appointment::serviceNamesSubquery('appointments'));

        if ($start && $end) {
            $query->whereBetween('appointments.sort_by', [$start, $end]);
        }

        $statusColorMap = self::statusColorMap();

        $events = [];
        foreach ($query->get() as $value) {
            $startDt = date_create($value->sort_by);
            $duration = $value->duration_minutes ?? (int) SystemSetting::get('clinic.default_duration', 30);
            $endDt = clone $startDt;
            $endDt->modify("+{$duration} minutes");

            $patientName = NameHelper::join($value->surname, $value->othername);
            $doctorName = NameHelper::join($value->d_surname, $value->d_othername);
            $bgColor = $statusColorMap[$value->status] ?? '#3a87ad';

            $extendedProps = [
                'patient_name' => $patientName,
                'doctor_name' => $doctorName,
                'patient_phone' => $value->p_phone ?? '',
                'patient_gender' => $value->p_gender ?? '',
                'status' => $this->translateStatus($value->status ?? ''),
                'status_code' => $value->status ?? '',
                // 有多选就显示全部，否则退回主项目（本表上线前的老预约没有透视表记录）
                'service_name' => $value->service_names ?: ($value->service_name ?? ''),
                'start_time' => date_format($startDt, 'H:i'),
                'end_time' => date_format($endDt, 'H:i'),
                'appointment_no' => $value->appointment_no ?? '',
                'doctor_id' => $value->doctor_id,
                // 诊室泳道分列用；未指定椅位的预约会被归进「未分配诊室」一列
                'chair_id' => $value->chair_id,
                'chair_name' => $value->chair_name ?? '',
            ];

            if ((bool) SystemSetting::get('clinic.show_appointment_notes', true)) {
                $extendedProps['notes'] = $value->notes ?? '';
            }

            $events[] = [
                'id' => $value->id,
                'title' => $patientName . ' - ' . $doctorName,
                'start' => date_format($startDt, 'Y-m-d H:i'),
                'end' => date_format($endDt, 'Y-m-d H:i'),
                'resourceId' => $value->doctor_id,
                'backgroundColor' => $bgColor,
                'borderColor' => $bgColor,
                'textColor' => '#ffffff',
                'extendedProps' => $extendedProps,
            ];
        }

        return $events;
    }

    private function translateStatus(string $status): string
    {
        return DictItem::nameByCode('appointment_status', $status) ?? $status;
    }

    /**
     * Get appointment data for Excel export.
     */
    public function getExportData(?string $from, ?string $to): Collection
    {
        $query = DB::table('appointments')
            ->join('patients', 'patients.id', 'appointments.patient_id')
            ->join('users', 'users.id', 'appointments.doctor_id')
            ->whereNull('appointments.deleted_at')
            ->select('appointments.*', 'patients.surname', 'patients.othername',
                'users.surname as d_surname', 'users.othername as d_othername');

        if ($from && $to) {
            $query->whereBetween(DB::raw('DATE(appointments.sort_by)'), [$from, $to]);
        }

        return $query->orderBy('appointments.sort_by', 'DESC')->get();
    }

    // ─── Single appointment ──────────────────────────────────────

    /**
     * Get appointment data for edit form.
     */
    public function getAppointmentForEdit(int $id)
    {
        $appointment = DB::table('appointments')
            ->join('users', 'users.id', 'appointments.doctor_id')
            ->join('patients', 'patients.id', 'appointments.patient_id')
            ->where('appointments.id', $id)
            ->whereNull('appointments.deleted_at')
            ->select('appointments.*', 'users.surname as d_surname', 'users.othername as d_othername',
                'patients.surname', 'patients.othername')
            ->first();

        if (!$appointment) {
            return null;
        }

        // 项目多选的回填：抽屉的 select2 是 ajax 的，不把 {id, text} 现成塞进去，
        // 已选的项目在框里就是空的 —— 前台一打开编辑就以为没选过，一保存全清光
        $appointment->services = DB::table('appointment_services as aps')
            ->join('medical_services as ms', 'ms.id', '=', 'aps.medical_service_id')
            ->where('aps.appointment_id', $id)
            ->orderBy('aps.sort_order')
            ->select('ms.id', 'ms.name as text')
            ->get()
            ->all();

        return $appointment;
    }

    // ─── Conflict detection ───────────────────────────────────────

    /**
     * Check if the doctor already has an appointment that overlaps with the given time range.
     * Returns the conflicting appointment or null.
     */
    public function checkOverbooking(int $doctorId, string $date, string $time, int $durationMinutes, ?int $excludeId = null): ?object
    {
        if ((bool) SystemSetting::get('clinic.allow_overbooking', true)) {
            return null;
        }

        $sortBy = $date . ' ' . date('H:i:s', strtotime($time));
        $newStart = strtotime($sortBy);
        $newEnd = $newStart + ($durationMinutes * 60);

        $query = DB::table('appointments')
            ->where('doctor_id', $doctorId)
            ->whereNull('deleted_at')
            ->whereNotIn('status', [Appointment::STATUS_CANCELLED, Appointment::STATUS_NO_SHOW])
            ->whereDate('start_date', $date);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        foreach ($query->get() as $existing) {
            $existStart = strtotime($existing->sort_by);
            $existDuration = $existing->duration_minutes ?? (int) SystemSetting::get('clinic.default_duration', 30);
            $existEnd = $existStart + ($existDuration * 60);

            if ($newStart < $existEnd && $newEnd > $existStart) {
                return $existing;
            }
        }

        return null;
    }

    // ─── Schedule validation ─────────────────────────────────────

    /**
     * Validate doctor schedule for a booking request.
     * Returns ['shift_id' => ?int, 'error' => ?string].
     *
     * - If a matching on-duty shift is found: shift_id = shift.id, error = null
     * - If no schedule + require_schedule_for_booking=ON: error message returned
     * - If no schedule + require_schedule_for_booking=OFF: shift_id = null, error = null (fallback OK)
     * - If schedule exists but time is outside all on-duty shifts: error message returned
     *
     * @AiGenerated
     * reason: AG-038/AG-042 — schedule validation must run on every booking path (Web + API)
     * generatedAt: 2026-03-16
     * reviewBy: Q2-2026
     */
    public function validateScheduleForBooking(int $doctorId, string $date, string $time): array
    {
        $timeSec = strtotime(date('H:i:s', strtotime($time)));

        $schedules = DoctorSchedule::with('shift')
            ->where('doctor_id', $doctorId)
            ->where('schedule_date', $date)
            ->whereNull('deleted_at')
            ->get();

        // Find the on-duty shift that covers this time slot
        foreach ($schedules as $schedule) {
            $shift = $schedule->shift;
            if (!$shift || !$shift->isOnDuty()) {
                continue;
            }
            $shiftStart = strtotime($shift->start_time);
            $shiftEnd   = strtotime($shift->end_time);
            if ($timeSec >= $shiftStart && $timeSec < $shiftEnd) {
                return ['shift_id' => $shift->id, 'error' => null];
            }
        }

        // Has schedule records but time falls outside all on-duty shifts
        if ($schedules->isNotEmpty()) {
            $hasOnDuty = $schedules->contains(fn ($s) => $s->shift && $s->shift->isOnDuty());
            if ($hasOnDuty) {
                return ['shift_id' => null, 'error' => __('appointment.time_outside_shift')];
            }
            // All shifts are rest — treat as "no schedule"
        }

        // No schedule (or only rest shifts) — check fallback setting
        $requireSchedule = (bool) SystemSetting::get('clinic.require_schedule_for_booking', false);
        if ($requireSchedule) {
            return ['shift_id' => null, 'error' => __('appointment.no_schedule_for_booking')];
        }

        return ['shift_id' => null, 'error' => null];
    }

    // ─── CUD operations ──────────────────────────────────────────

    /**
     * Create a new appointment.
     * Expects $data to include 'shift_id' (resolved by validateScheduleForBooking).
     * Performs atomic max_patients check inside a DB transaction (AG-037, AG-042).
     */
    public function createAppointment(array $data): ?Appointment
    {
        // start_time 是 varchar，一律存 24 小时制 HH:MM。
        // 曾经这里 walk-in 走 format('h:i A')、其余分支直接透传表单原值，于是库里混着
        // 「08:30 PM」这类 12 小时制字符串；2026_08_01_000006 用 LEFT(...,5) 归一时
        // 把 AM/PM 连同真实时段一起截掉，晚 8 点半会变成早 8 点半。
        // 存量数据由 2026_08_02_* 的修复迁移按 sort_by 还原。
        $time24  = date("H:i:s", strtotime($data['appointment_time']));
        $appTime = ($data['visit_information'] == Appointment::VISIT_WALK_IN)
            ? now()->format('H:i')
            : date('H:i', strtotime($data['appointment_time']));
        $shiftId = $data['shift_id'] ?? null;

        return DB::transaction(function () use ($data, $time24, $appTime, $shiftId) {
            // Atomic max_patients check — AG-037, AG-042
            if ($shiftId) {
                $shift = Shift::where('id', $shiftId)->lockForUpdate()->first();
                if ($shift && $shift->max_patients > 0) {
                    $booked = DB::table('appointments')
                        ->where('doctor_id', $data['doctor_id'])
                        ->whereDate('start_date', $data['appointment_date'])
                        ->whereNull('deleted_at')
                        ->whereNotIn('status', [Appointment::STATUS_CANCELLED, Appointment::STATUS_NO_SHOW])
                        ->where('sort_by', '>=', $data['appointment_date'] . ' ' . $shift->start_time)
                        ->where('sort_by', '<',  $data['appointment_date'] . ' ' . $shift->end_time)
                        ->lockForUpdate()
                        ->count();

                    if ($booked >= $shift->max_patients) {
                        return null; // Shift is full (atomic guard)
                    }
                }
            }

            $appointment = Appointment::create([
                'appointment_no'   => Appointment::AppointmentNo(),
                'patient_id'       => $data['patient_id'],
                'doctor_id'        => $data['doctor_id'],
                'start_date'       => $data['appointment_date'],
                'end_date'         => $data['appointment_date'],
                'start_time'       => $appTime,
                'visit_information'=> $data['visit_information'],
                'notes'            => $data['notes'] ?? null,
                'branch_id'        => Auth::user()->branch_id,
                'sort_by'          => $data['appointment_date'] . " " . $time24,
                'chair_id'         => $data['chair_id'] ?? null,
                // 主项目：多选里的第一个（见 normalizeServiceIds 的说明）
                'service_id'       => $this->primaryServiceId($data),
                'appointment_type' => $data['appointment_type'] ?? 'revisit',
                'duration_minutes' => $data['duration_minutes'] ?? (int) SystemSetting::get('clinic.default_duration', 30),
                'shift_id'         => $shiftId,
                '_who_added'       => Auth::user()->id,
            ]);

            if ($appointment) {
                $this->syncAppointmentServices($appointment, $data);
                $sendSms = ($data['send_sms'] ?? '1') === '1';
                $this->createAppointmentHistory($appointment->id, "Created", $sendSms);
                $this->closeFollowupIfRequested($appointment, $data);
            }

            return $appointment;
        });
    }

    /**
     * 「约下次」带着复诊待办 id 过来时，约成即闭环：回填 appointment_id、置为已完成。
     *
     * 不这么做的话，前台在病人离店时明明已经约好了，那条待办还会在复诊日当天
     * 跳进随访列表让人再打一次电话 —— 病人接到"该来复诊了"的电话，回一句
     * "我不是已经约了吗"。patient_followups.appointment_id 这个字段本来就是
     * 为这一步留的，此前全仓库没有任何地方写过它。
     *
     * 只认自己患者名下、且仍是 Pending 的那条：id 是从前端带来的，不能直接信。
     */
    private function closeFollowupIfRequested(Appointment $appointment, array $data): void
    {
        $followupId = $data['followup_id'] ?? null;
        if (!$followupId) {
            return;
        }

        \App\PatientFollowup::where('id', (int) $followupId)
            ->where('patient_id', $appointment->patient_id)
            ->where('status', \App\PatientFollowup::STATUS_PENDING)
            ->update([
                'status'         => \App\PatientFollowup::STATUS_COMPLETED,
                'completed_date' => now(),
                'appointment_id' => $appointment->id,
            ]);
    }

    /**
     * 把请求里的项目选择理成一个有序、去重的 id 列表。
     *
     * 兼容两种入参：service_ids[]（预约抽屉的多选）与 service_id（挂号、API v1、
     * 以及所有还没改的老调用点）。两者都给时以多选为准 —— 抽屉提交时两个字段
     * 会同时在，只认一个才不会出现「界面勾了三个、存进去一个」。
     */
    private function normalizeServiceIds(array $data): array
    {
        $ids = $data['service_ids'] ?? null;

        if ($ids === null && !empty($data['service_id'])) {
            $ids = [$data['service_id']];
        }

        return collect((array) $ids)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** 主项目 = 勾选里的第一个；一个都没勾就是 null */
    private function primaryServiceId(array $data): ?int
    {
        return $this->normalizeServiceIds($data)[0] ?? null;
    }

    /**
     * 同步 appointment_services（全集）。
     *
     * 这是 service_id 与透视表唯一的同步点 —— 两处存着重叠的信息，同步散在
     * 多个地方就一定会漂。
     *
     * 请求里完全没提到项目时不动透视表：改期、改状态这类局部更新不该顺手
     * 把项目清空。
     */
    private function syncAppointmentServices(Appointment $appointment, array $data): void
    {
        if (!array_key_exists('service_ids', $data) && !array_key_exists('service_id', $data)) {
            return;
        }

        $payload = [];
        foreach ($this->normalizeServiceIds($data) as $i => $id) {
            $payload[$id] = ['sort_order' => $i];
        }

        $appointment->services()->sync($payload);
    }

    /**
     * Update an existing appointment.
     */
    public function updateAppointment(int $id, array $data): bool
    {
        $time24 = date("H:i:s", strtotime($data['appointment_time']));

        $payload = [
            'patient_id' => $data['patient_id'],
            'doctor_id' => $data['doctor_id'],
            'start_date' => $data['appointment_date'],
            'end_date' => $data['appointment_date'],
            'start_time' => $data['appointment_time'],
            'visit_information' => $data['visit_information'],
            'sort_by' => $data['appointment_date'] . " " . $time24,
            'notes' => $data['notes'] ?? null,
            '_who_added' => Auth::user()->id,
        ];

        // 请求提到了项目才动它：改期、改状态这类局部更新不该顺手清空项目
        $touchesServices = array_key_exists('service_ids', $data) || array_key_exists('service_id', $data);
        if ($touchesServices) {
            $payload['service_id'] = $this->primaryServiceId($data);
        }

        $updated = (bool) Appointment::where('id', $id)->update($payload);

        if ($updated && $touchesServices) {
            $appointment = Appointment::find($id);
            if ($appointment) {
                $this->syncAppointmentServices($appointment, $data);
            }
        }

        return $updated;
    }

    /**
     * Reschedule an appointment.
     */
    public function rescheduleAppointment(int $id, array $data): bool
    {
        $time24 = date("H:i:s", strtotime($data['appointment_time']));

        $success = (bool) Appointment::where('id', $id)->update([
            'start_date' => $data['appointment_date'],
            'end_date' => $data['appointment_date'],
            'start_time' => $data['appointment_time'],
            'sort_by' => $data['appointment_date'] . " " . $time24,
            'visit_information' => Appointment::VISIT_APPOINTMENT,
            'status' => Appointment::STATUS_RESCHEDULED,
        ]);

        if ($success) {
            $this->createAppointmentHistory($id, "Rescheduled");
        }

        return $success;
    }

    /**
     * Delete (soft-delete) an appointment.
     */
    public function deleteAppointment(int $id): bool
    {
        return (bool) Appointment::where('id', $id)->delete();
    }

    // ─── History & notifications ─────────────────────────────────

    /**
     * Create appointment history and send notifications if needed.
     */
    public function createAppointmentHistory(int $appointmentId, string $status, bool $sendSms = true): void
    {
        $message = '';
        $record = DB::table('appointments')
            ->leftJoin('patients', 'patients.id', 'appointments.patient_id')
            ->where('appointments.id', $appointmentId)
            ->select('patients.surname', 'patients.othername', 'patients.phone_no',
                'appointments.*', 'appointments.visit_information',
                DB::raw('DATE_FORMAT(appointments.start_date, "%Y-%m-%d") as formatted_date'))
            ->first();

        if ($status == "Created" && $record->visit_information != Appointment::VISIT_WALK_IN) {
            $message = __('sms.appointment_scheduled', [
                'name' => $record->othername,
                'company' => config('app.name', 'Laravel'),
                'date' => $record->formatted_date,
                'time' => $record->start_time,
            ]);

            if ($sendSms && $record->phone_no != null) {
                $patient = Patient::where('id', $record->patient_id)->first();
                dispatch(new SendAppointmentSms($record->phone_no, $message, "Appointment"));

                $convertedTime = date("H:i:s", strtotime($record->start_time));
                $appointmentTime = $record->start_date . " " . $convertedTime;
                $reminderDate = date('Y-m-d H:i:s', strtotime('-1 day', strtotime($appointmentTime)));
                $sendReminder = FunctionsHelper::getRangeDateString($reminderDate);

                if ($sendReminder == "Tomorrow" || $sendReminder == "future days") {
                    ScheduledNotification::create(
                        $patient,
                        new ReminderNotification('Dear, ' . $patient->othername .
                            " This is a polite reminder about your appointment at " . config('app.company_name') . " scheduled for "
                            . $record->formatted_date . " at " . $record->start_time),
                        Carbon::parse($reminderDate)
                    );
                }
            }
        }

        AppointmentHistory::create([
            'start_date' => $record->start_date,
            'end_date' => $record->start_date,
            'start_time' => $record->start_time,
            'status' => $status,
            'message' => $message,
            'appointment_id' => $appointmentId,
        ]);
    }

    // ─── Scheduling helpers ──────────────────────────────────────

    /**
     * Get available chairs for a branch.
     */
    public function getChairs(?int $branchId): Collection
    {
        $query = Chair::active()
            ->select('id', 'chair_name as text');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get();
    }

    /**
     * Get doctor time slots for a specific date.
     * Integrates with Shift-based scheduling.
     * Fallback behavior controlled by schedule.require_schedule_for_booking setting.
     */
    public function getDoctorTimeSlots(int $doctorId, string $date): array
    {
        // Fetch all schedules for this doctor+date (may have multiple shifts)
        $schedules = DoctorSchedule::with('shift')
            ->where('doctor_id', $doctorId)
            ->where('schedule_date', $date)
            ->whereNull('deleted_at')
            ->get();

        // Filter to on-duty shifts only
        $onDutySchedules = $schedules->filter(function ($s) {
            return $s->shift && $s->shift->isOnDuty();
        });

        $slotInterval = (int) SystemSetting::get('clinic.slot_interval', 30);
        $intervalSec = $slotInterval * 60;
        $hasSchedule = $schedules->isNotEmpty();
        $slots = [];
        $maxPatientsMap = []; // time => max_patients for AG-037

        if ($onDutySchedules->isNotEmpty()) {
            // Generate slots from each on-duty shift's time range
            foreach ($onDutySchedules as $schedule) {
                $shift = $schedule->shift;
                $startTime = strtotime($shift->start_time);
                $endTime = strtotime($shift->end_time);
                $maxPatients = $shift->max_patients;

                for ($time = $startTime; $time < $endTime; $time += $intervalSec) {
                    $timeStr = date('H:i', $time);
                    if (!isset($maxPatientsMap[$timeStr])) {
                        $period = $time < strtotime('12:00') ? 'morning' : 'afternoon';
                        $slots[] = ['time' => $timeStr, 'period' => $period, 'is_rest' => false];
                        $maxPatientsMap[$timeStr] = $maxPatients;
                    }
                }
            }

            // Sort slots by time
            usort($slots, function ($a, $b) {
                return strcmp($a['time'], $b['time']);
            });
        } elseif (!$hasSchedule) {
            // No schedule at all — check fallback setting
            $requireSchedule = (bool) SystemSetting::get('clinic.require_schedule_for_booking', false);

            if ($requireSchedule) {
                // Strict mode: no schedule = no slots
                return [
                    'slots' => [],
                    'booked' => [],
                    'has_schedule' => false,
                    'max_patients_map' => [],
                ];
            }

            // Fallback: use system default times
            $defaultStart = SystemSetting::get('clinic.start_time', '08:30');
            $defaultEnd   = SystemSetting::get('clinic.end_time', '18:30');
            $start = strtotime($defaultStart);
            $end   = strtotime($defaultEnd);

            for ($time = $start; $time < $end; $time += $intervalSec) {
                $timeStr = date('H:i', $time);
                $period  = $time < strtotime('12:00') ? 'morning' : 'afternoon';
                $slots[] = ['time' => $timeStr, 'period' => $period, 'is_rest' => false];
            }
        }
        // else: has schedule but all are rest shifts → no slots

        // Existing bookings
        $existingAppointments = DB::table('appointments')
            ->leftJoin('patients', 'patients.id', 'appointments.patient_id')
            ->where('appointments.doctor_id', $doctorId)
            ->where('appointments.start_date', $date)
            ->whereNull('appointments.deleted_at')
            ->whereNotIn('appointments.status', [Appointment::STATUS_CANCELLED, Appointment::STATUS_NO_SHOW])
            ->select('appointments.start_time', 'patients.surname as p_surname', 'patients.othername as p_othername')
            ->get();

        $booked = [];
        $bookedCount = []; // time => count for max_patients check
        foreach ($existingAppointments as $appt) {
            $timeKey = date('H:i', strtotime($appt->start_time));
            $booked[$timeKey] = [
                'patient_name' => NameHelper::join($appt->p_surname, $appt->p_othername),
            ];
            $bookedCount[$timeKey] = ($bookedCount[$timeKey] ?? 0) + 1;
        }

        return [
            'slots' => $slots,
            'booked' => $booked,
            'booked_count' => $bookedCount,
            'has_schedule' => $hasSchedule,
            'max_patients_map' => $maxPatientsMap,
        ];
    }

    // ─── DataTable formatting ────────────────────────────────────

    /**
     * Build DataTables response for the appointments index page.
     *
     * @param bool $embedded 表格是否嵌在别的页面里（目前只有患者详情的「预约记录」页签）。
     *                       操作列会据此裁剪，见 buildActionColumn() 的注释。
     */
    public function buildIndexDataTable($data, bool $embedded = false)
    {
        return DataTables::of($data)
            ->addIndexColumn()
            ->filter(function ($instance) {
            })
            ->editColumn('sort_by', function ($row) {
                return $row->sort_by ? \Carbon\Carbon::parse($row->sort_by)->format('Y-m-d H:i') : '-';
            })
            ->editColumn('start_time', function ($row) {
                return $row->start_time ?: '-';
            })
            ->editColumn('status', function ($row) {
                return DictItem::nameByCode('appointment_status', $row->status) ?? $row->status;
            })
            ->addColumn('patient', function ($row) {
                return NameHelper::join($row->surname, $row->othername);
            })
            ->addColumn('doctor', function ($row) {
                return NameHelper::join($row->d_surname, $row->d_othername);
            })
            ->addColumn('visit_information', function ($row) {
                $action = '';
                if ($row->visit_information == Appointment::VISIT_REVIEW_TREATMENT && $row->status != Appointment::STATUS_WAITING) {
                    $action = '<br> <a href="#"  onclick="ReactivateAppointment(' .
                        $row->id . ')"  class="text-primary">Re-activate Appointment</a>';
                }
                $label = DictItem::nameByCode('appointment_visit_information', $row->visit_information) ?? $row->visit_information;
                return e($label) . $action;
            })
            ->addColumn('invoice_status', function ($row) {
                if ($row->has_invoice_status === 'pending') {
                    return '<span class="text-danger">' . __('messages.no_invoice_yet') . '</span>';
                }
                return '<span class="text-primary">' . __('messages.invoice_already_generated') . '</span>';
            })
            ->addColumn('action', fn($row) => $this->buildActionColumn($row, $embedded))
            ->rawColumns(['visit_information', 'invoice_status', 'action'])
            ->make(true);
    }

    /**
     * 操作列。
     *
     * 同一份 HTML 供两个地方消费：预约页 /appointments，以及患者详情的「预约记录」页签
     * （patient_detail.js 请求的也是 /appointments，只是多带一个 patient_id）。
     *
     * 但「改约 / 生成账单 / 编辑 / 删除」四项是 onclick 调全局函数，而其中三个函数写死在
     * appointments/index.blade.php 的内联脚本里，患者详情页压根没有 —— 结果那四项在患者页
     * 点了毫无反应，且因为下拉菜单一直被 .table-scrollable 裁掉，六年没人发现。
     *
     * 这里按上下文裁剪：
     *   · 改约      两边都留。弹窗是独立 partial，患者页 @include 一份即可，
     *               而改约是前台在患者档案里最常做的动作，值得就地完成。
     *   · 查看账单  两边都留 —— 本来就是纯链接。
     *   · 治疗历史  两边都留 —— 本来就是纯链接。
     *   · 生成账单 / 编辑 / 删除
     *               只在预约页出现。它们的处理函数写死在预约页的内联脚本里，搬过来
     *               等于两处维护；患者页改成一个「去预约页处理」的跳转，带 focus 参数
     *               让落地后能定位到那一行。
     */
    private function buildActionColumn($row, bool $embedded): string
    {
        $items = [];

        $items[] = '<li><a href="#" onclick="RescheduleAppointment(' . $row->id . ')">'
            . __('appointment.reschedule') . '</a></li>';

        // 账单入口按权限显示：「生成账单」最终 POST /invoices（需 create-invoices），
        // 「查看账单」打开 /invoices/{id}（需 view-invoices）。护士两个权限都不持有，
        // 此前无论落到哪个分支拿到的都是点了必然 403 的死链接；医生只有 view-invoices，
        // 却同样看得到「生成账单」。服务端权限不变，这里只是不再展示必然失败的入口。
        $user = auth()->user();
        if ($row->has_invoice_status === 'pending') {
            if (!$embedded && $user && $user->can('create-invoices')) {
                $items[] = '<li><a href="#" onclick="RecordPayment(' . $row->id . ')">'
                    . __('invoices.generate_invoice') . '</a></li>';
            }
        } elseif ($user && $user->can('view-invoices')) {
            $items[] = '<li><a href="' . url('invoices/' . $row->invoice_id) . '">'
                . __('invoices.view_invoice') . '</a></li>';
        }

        if (!$embedded) {
            $items[] = '<li><a href="#" onclick="editRecord(' . $row->id . ')">'
                . __('common.edit') . '</a></li>';
        }

        $items[] = '<li><a href="' . url('medical-treatment/' . $row->id) . '">'
            . __('medical_treatment.treatment_history') . '</a></li>';

        if ($embedded) {
            $items[] = '<li class="divider"></li>';
            // 传预约编号而不是 id：预约页已有按 appointment_no 的服务端筛选
            // （#appointment_no_filter），落地直接筛出这一条，不用另加接口
            $items[] = '<li><a href="' . url('appointments?focus=' . urlencode($row->appointment_no)) . '">'
                . __('appointment.manage_on_appointments_page') . '</a></li>';
        } else {
            $items[] = '<li><a href="#" onclick="deleteRecord(' . $row->id . ')">'
                . __('common.delete') . '</a></li>';
        }

        return '<div class="btn-group">'
            . '<button class="btn blue dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">'
            . __('common.action') . ' <i class="fa fa-angle-down"></i>'
            . '</button>'
            . '<ul class="dropdown-menu" role="menu">' . implode('', $items) . '</ul>'
            . '</div>';
    }
}
