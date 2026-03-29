<?php

namespace App\Services;

use App\Appointment;
use App\Branch;
use App\DoctorSchedule;
use App\Shift;
use App\User;
use App\WaitingQueue;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;

class DoctorScheduleService
{
    /**
     * Get schedules for list view / DataTables.
     */
    public function getListSchedules(?int $doctorId = null): Collection
    {
        $query = DoctorSchedule::with(['doctor', 'shift', 'branch'])
            ->whereNull('deleted_at');

        if ($doctorId) {
            $query->where('doctor_id', $doctorId);
        }

        $user = Auth::user();
        if ($user && !$user->can('manage-schedules') && !$user->can('view-all-schedules')) {
            $query->where('doctor_id', $user->id);
        }

        return $query->orderByDesc('schedule_date')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Build the DataTables response for the schedule list view.
     */
    public function buildIndexDataTable($data)
    {
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('doctor_name', function ($row) {
                if (!$row->doctor) {
                    return __('common.none');
                }

                return app()->getLocale() === 'zh-CN'
                    ? trim(($row->doctor->surname ?? '') . ($row->doctor->othername ?? ''))
                    : trim(($row->doctor->surname ?? '') . ' ' . ($row->doctor->othername ?? ''));
            })
            ->addColumn('time_range', function ($row) {
                if ($row->shift && !empty($row->shift->time_range)) {
                    return $row->shift->time_range;
                }

                $start = $row->getEffectiveStartTime();
                $end = $row->getEffectiveEndTime();

                if (!$start && !$end) {
                    return __('common.none');
                }

                return trim(($start ?: '--:--') . ' - ' . ($end ?: '--:--'));
            })
            ->addColumn('max_patients', function ($row) {
                return $row->getEffectiveMaxPatients();
            })
            ->addColumn('recurring_info', function ($row) {
                if (!$row->is_recurring) {
                    return __('common.no');
                }

                $patterns = [
                    'daily' => __('doctor_schedules.pattern_daily'),
                    'weekly' => __('doctor_schedules.pattern_weekly'),
                    'monthly' => __('doctor_schedules.pattern_monthly'),
                ];

                $label = $patterns[$row->recurring_pattern] ?? __('common.yes');
                if ($row->recurring_until) {
                    return $label . ' · ' . $row->recurring_until->format('Y-m-d');
                }

                return $label;
            })
            ->addColumn('branch_name', function ($row) {
                return optional($row->branch)->name ?: __('common.none');
            })
            ->addColumn('editBtn', function ($row) {
                return '<a href="#" onclick="editRecord(' . $row->id . ')" class="btn btn-primary btn-sm">' . __('common.edit') . '</a>';
            })
            ->addColumn('deleteBtn', function ($row) {
                return '<a href="#" onclick="deleteRecord(' . $row->id . ')" class="btn btn-danger btn-sm">' . __('common.delete') . '</a>';
            })
            ->editColumn('schedule_date', function ($row) {
                return $row->schedule_date ? $row->schedule_date->format('Y-m-d') : '';
            })
            ->rawColumns(['editBtn', 'deleteBtn'])
            ->make(true);
    }

    /**
     * Get all data needed for the monthly grid view.
     */
    public function getGridViewData(): array
    {
        $doctors = User::where('is_doctor', true)
            ->whereNull('deleted_at')
            ->where('status', User::STATUS_ACTIVE)
            ->orderBy('surname')
            ->get();

        $shifts = Shift::ordered()->get();
        $branches = Branch::all();

        return compact('doctors', 'shifts', 'branches');
    }

    /**
     * Get schedules for a specific month, keyed by "doctorId_day".
     */
    public function getMonthSchedules(string $yearMonth): array
    {
        $schedules = DoctorSchedule::with('shift')
            ->forMonth($yearMonth)
            ->whereNull('deleted_at')
            ->get();

        $grid = [];
        foreach ($schedules as $schedule) {
            $day = $schedule->schedule_date->day;
            $key = $schedule->doctor_id . '_' . $day;
            if (!isset($grid[$key])) {
                $grid[$key] = [];
            }
            $grid[$key][] = [
                'id'       => $schedule->id,
                'shift_id' => $schedule->shift_id,
                'name'     => $schedule->shift ? $schedule->shift->name : __('doctor_schedules.legacy_shift'),
                'color'    => $schedule->shift ? $schedule->shift->color : '#409EFF',
            ];
        }

        return $grid;
    }

    /**
     * Assign a shift to a doctor on a specific date.
     * Checks for time conflicts (AG-010 enhanced).
     *
     * @return array{success: bool, message: string, data?: array}
     */
    public function assignShift(int $doctorId, string $date, int $shiftId): array
    {
        $shift = Shift::find($shiftId);
        if (!$shift) {
            return ['success' => false, 'message' => __('shifts.not_found')];
        }

        // Check for time overlap conflicts (skip if rest shift)
        if ($shift->isOnDuty()) {
            $conflict = $this->checkTimeConflict($doctorId, $date, $shiftId);
            if ($conflict) {
                return ['success' => false, 'message' => $conflict];
            }
        }

        // Check if this exact assignment already exists
        $existing = DoctorSchedule::where('doctor_id', $doctorId)
            ->where('schedule_date', $date)
            ->where('shift_id', $shiftId)
            ->whereNull('deleted_at')
            ->first();

        if ($existing) {
            return ['success' => false, 'message' => __('doctor_schedules.already_assigned')];
        }

        $schedule = DoctorSchedule::create([
            'doctor_id'     => $doctorId,
            'shift_id'      => $shiftId,
            'schedule_date' => $date,
            '_who_added'    => Auth::id(),
        ]);

        return [
            'success' => true,
            'message' => __('doctor_schedules.added_successfully'),
            'data'    => [
                'id'       => $schedule->id,
                'shift_id' => $shift->id,
                'name'     => $shift->name,
                'color'    => $shift->color,
            ],
        ];
    }

    /**
     * Remove a schedule entry. Checks for linked appointments (AG-036).
     *
     * @return array{success: bool, message: string}
     */
    public function removeSchedule(int $scheduleId): array
    {
        $schedule = DoctorSchedule::with('shift')->find($scheduleId);
        if (!$schedule) {
            return ['success' => false, 'message' => __('doctor_schedules.not_found')];
        }

        // AG-033: Doctors cannot delete today's or past schedules
        if (!Auth::user()->hasPermission('manage-schedules') && $schedule->schedule_date->lte(Carbon::today())) {
            return ['success' => false, 'message' => __('doctor_schedules.cannot_delete_past')];
        }

        // AG-036: Check for linked appointments
        $appointmentCount = $this->countLinkedAppointments(
            $schedule->doctor_id,
            $schedule->schedule_date->format('Y-m-d'),
            $schedule->shift
        );

        if ($appointmentCount > 0) {
            return [
                'success' => false,
                'message' => __('doctor_schedules.has_linked_appointments', ['count' => $appointmentCount]),
            ];
        }

        // AG-073: Check for active waiting-queue patients on this schedule date
        $waitingCount = WaitingQueue::where('doctor_id', $schedule->doctor_id)
            ->whereDate('check_in_time', $schedule->schedule_date->toDateString())
            ->whereNotIn('status', [
                WaitingQueue::STATUS_COMPLETED,
                WaitingQueue::STATUS_CANCELLED,
                WaitingQueue::STATUS_NO_SHOW,
            ])
            ->whereNull('deleted_at')
            ->count();

        if ($waitingCount > 0) {
            return [
                'success' => false,
                'message' => __('doctor_schedules.delete_has_waiting_patients', ['count' => $waitingCount]),
            ];
        }

        $schedule->delete();

        return ['success' => true, 'message' => __('doctor_schedules.deleted_successfully')];
    }

    /**
     * Store a manually configured schedule row from the list modal.
     */
    public function createSchedule(array $data, int $userId): array
    {
        $conflict = $this->checkManualTimeConflict(
            (int) $data['doctor_id'],
            $data['schedule_date'],
            $data['start_time'],
            $data['end_time']
        );

        if ($conflict) {
            return ['success' => false, 'message' => $conflict];
        }

        $schedule = DoctorSchedule::create($this->buildSchedulePayload($data, $userId));

        return [
            'success' => true,
            'message' => __('doctor_schedules.added_successfully'),
            'data' => $schedule,
        ];
    }

    /**
     * Update a manually configured schedule row from the list modal.
     */
    public function updateSchedule(int $id, array $data, int $userId): array
    {
        $schedule = DoctorSchedule::find($id);
        if (!$schedule) {
            return ['success' => false, 'message' => __('doctor_schedules.not_found')];
        }

        $conflict = $this->checkManualTimeConflict(
            (int) $data['doctor_id'],
            $data['schedule_date'],
            $data['start_time'],
            $data['end_time'],
            $id
        );

        if ($conflict) {
            return ['success' => false, 'message' => $conflict];
        }

        $payload = $this->buildSchedulePayload($data, $userId);
        $payload['changed_by'] = $userId;
        $schedule->update($payload);

        return [
            'success' => true,
            'message' => __('doctor_schedules.updated_successfully'),
            'data' => $schedule->fresh(),
        ];
    }

    /**
     * Copy a week's schedules to another week.
     *
     * @return array{success: bool, message: string, count?: int}
     */
    public function copyWeek(string $sourceStart, string $targetStart): array
    {
        $sourceStartDate = Carbon::parse($sourceStart)->startOfWeek(Carbon::MONDAY);
        $targetStartDate = Carbon::parse($targetStart)->startOfWeek(Carbon::MONDAY);
        $sourceEndDate = $sourceStartDate->copy()->endOfWeek(Carbon::SUNDAY);

        $sourceSchedules = DoctorSchedule::whereBetween('schedule_date', [
            $sourceStartDate->format('Y-m-d'),
            $sourceEndDate->format('Y-m-d'),
        ])->whereNull('deleted_at')->get();

        if ($sourceSchedules->isEmpty()) {
            return ['success' => false, 'message' => __('doctor_schedules.source_week_empty')];
        }

        $dayDiff = $sourceStartDate->diffInDays($targetStartDate);
        $created = 0;

        DB::beginTransaction();
        try {
            foreach ($sourceSchedules as $source) {
                $newDate = $source->schedule_date->copy()->addDays($dayDiff)->format('Y-m-d');

                // Skip if already exists
                $exists = DoctorSchedule::where('doctor_id', $source->doctor_id)
                    ->where('schedule_date', $newDate)
                    ->where('shift_id', $source->shift_id)
                    ->whereNull('deleted_at')
                    ->exists();

                if (!$exists) {
                    DoctorSchedule::create([
                        'doctor_id'     => $source->doctor_id,
                        'shift_id'      => $source->shift_id,
                        'schedule_date' => $newDate,
                        '_who_added'    => Auth::id(),
                    ]);
                    $created++;
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('copyWeek failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => __('doctor_schedules.copy_failed')];
        }

        return [
            'success' => true,
            'message' => __('doctor_schedules.copy_week_success', ['count' => $created]),
            'count'   => $created,
        ];
    }

    /**
     * Copy previous month's schedules to current month.
     *
     * @return array{success: bool, message: string, count?: int}
     */
    public function copyMonth(string $targetYearMonth): array
    {
        $targetDate = Carbon::parse($targetYearMonth . '-01');
        $sourceDate = $targetDate->copy()->subMonth();

        $sourceSchedules = DoctorSchedule::forMonth($sourceDate->format('Y-m'))
            ->whereNull('deleted_at')
            ->get();

        if ($sourceSchedules->isEmpty()) {
            return ['success' => false, 'message' => __('doctor_schedules.source_month_empty')];
        }

        $created = 0;

        DB::beginTransaction();
        try {
            foreach ($sourceSchedules as $source) {
                $sourceDay = $source->schedule_date->day;
                $targetDaysInMonth = $targetDate->daysInMonth;

                // Skip if day exceeds target month's days
                if ($sourceDay > $targetDaysInMonth) {
                    continue;
                }

                $newDate = $targetDate->copy()->day($sourceDay)->format('Y-m-d');

                $exists = DoctorSchedule::where('doctor_id', $source->doctor_id)
                    ->where('schedule_date', $newDate)
                    ->where('shift_id', $source->shift_id)
                    ->whereNull('deleted_at')
                    ->exists();

                if (!$exists) {
                    DoctorSchedule::create([
                        'doctor_id'     => $source->doctor_id,
                        'shift_id'      => $source->shift_id,
                        'schedule_date' => $newDate,
                        '_who_added'    => Auth::id(),
                    ]);
                    $created++;
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('copyMonth failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => __('doctor_schedules.copy_failed')];
        }

        return [
            'success' => true,
            'message' => __('doctor_schedules.copy_month_success', ['count' => $created]),
            'count'   => $created,
        ];
    }

    /**
     * Check if a new shift assignment would conflict with existing ones.
     */
    private function checkTimeConflict(int $doctorId, string $date, int $newShiftId): ?string
    {
        $newShift = Shift::find($newShiftId);
        if (!$newShift) {
            return null;
        }

        $existingSchedules = DoctorSchedule::with('shift')
            ->where('doctor_id', $doctorId)
            ->where('schedule_date', $date)
            ->whereNull('deleted_at')
            ->get();

        foreach ($existingSchedules as $existing) {
            if (!$existing->shift || !$existing->shift->isOnDuty()) {
                continue;
            }

            // Check time overlap
            if ($newShift->start_time < $existing->shift->end_time
                && $newShift->end_time > $existing->shift->start_time) {
                return __('doctor_schedules.time_conflict', [
                    'shift' => $existing->shift->name,
                    'time'  => $existing->shift->time_range,
                ]);
            }
        }

        return null;
    }

    /**
     * Count appointments linked to a doctor on a date within a shift's time range.
     */
    private function countLinkedAppointments(int $doctorId, string $date, ?Shift $shift): int
    {
        $query = Appointment::where('doctor_id', $doctorId)
            ->whereDate('start_date', $date)
            ->whereNotIn('status', [Appointment::STATUS_CANCELLED, Appointment::STATUS_NO_SHOW])
            ->whereNull('deleted_at');

        if ($shift && $shift->isOnDuty()) {
            $query->where('start_time', '>=', $shift->start_time)
                  ->where('start_time', '<', $shift->end_time);
        }

        return $query->count();
    }

    // ---- Legacy methods kept for backward compatibility ----

    /**
     * @deprecated Use getGridViewData() instead.
     */
    public function getFormData(): array
    {
        $doctors = User::where('is_doctor', true)->whereNull('deleted_at')->where('status', User::STATUS_ACTIVE)->get();
        $branches = Branch::all();

        return compact('doctors', 'branches');
    }

    /**
     * Find a schedule by ID.
     */
    public function find(int $id): ?DoctorSchedule
    {
        return DoctorSchedule::with('shift')->find($id);
    }

    private function buildSchedulePayload(array $data, int $userId): array
    {
        $isRecurring = !empty($data['is_recurring']);

        return [
            'doctor_id' => (int) $data['doctor_id'],
            'branch_id' => !empty($data['branch_id']) ? (int) $data['branch_id'] : null,
            'schedule_date' => $data['schedule_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'max_patients' => (int) $data['max_patients'],
            'notes' => $data['notes'] ?? null,
            'is_recurring' => $isRecurring,
            'recurring_pattern' => $isRecurring ? ($data['recurring_pattern'] ?? 'weekly') : null,
            'recurring_until' => $isRecurring ? ($data['recurring_until'] ?? null) : null,
            '_who_added' => $userId,
        ];
    }

    private function checkManualTimeConflict(
        int $doctorId,
        string $date,
        string $startTime,
        string $endTime,
        ?int $ignoreScheduleId = null
    ): ?string {
        $schedules = DoctorSchedule::with('shift')
            ->where('doctor_id', $doctorId)
            ->where('schedule_date', $date)
            ->whereNull('deleted_at')
            ->when($ignoreScheduleId, function ($query) use ($ignoreScheduleId) {
                $query->where('id', '!=', $ignoreScheduleId);
            })
            ->get();

        foreach ($schedules as $existing) {
            $existingStart = $existing->getEffectiveStartTime();
            $existingEnd = $existing->getEffectiveEndTime();

            if (!$existingStart || !$existingEnd) {
                continue;
            }

            if ($startTime < $existingEnd && $endTime > $existingStart) {
                return __('doctor_schedules.time_conflict', [
                    'shift' => $existing->shift ? $existing->shift->name : __('doctor_schedules.legacy_shift'),
                    'time' => trim($existingStart . ' - ' . $existingEnd),
                ]);
            }
        }

        return null;
    }
}
