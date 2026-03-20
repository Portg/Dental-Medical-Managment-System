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

class DoctorScheduleService
{
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
}
