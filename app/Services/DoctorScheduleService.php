<?php

namespace App\Services;

use App\Branch;
use App\DoctorSchedule;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DoctorScheduleService
{
    /**
     * Get schedule list for DataTables.
     */
    public function getScheduleList(): Collection
    {
        return DB::table('doctor_schedules')
            ->leftJoin('users as doctor', 'doctor.id', 'doctor_schedules.doctor_id')
            ->leftJoin('users as creator', 'creator.id', 'doctor_schedules._who_added')
            ->leftJoin('branches', 'branches.id', 'doctor_schedules.branch_id')
            ->whereNull('doctor_schedules.deleted_at')
            ->select([
                'doctor_schedules.*',
                DB::raw("CONCAT(doctor.surname, doctor.othername) as doctor_name"),
                DB::raw("CONCAT(creator.surname, creator.othername) as added_by"),
                'branches.name as branch_name',
            ])
            ->orderBy('doctor_schedules.schedule_date', 'desc')
            ->get();
    }

    /**
     * Get doctors and branches for the index view.
     */
    public function getFormData(): array
    {
        $doctors = User::where('is_doctor', true)->whereNull('deleted_at')->get();
        $branches = Branch::all();

        return compact('doctors', 'branches');
    }

    /**
     * Create a new schedule (and recurring instances if applicable).
     */
    public function createSchedule(array $data): ?DoctorSchedule
    {
        $scheduleData = [
            'doctor_id' => $data['doctor_id'],
            'schedule_date' => $data['schedule_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'is_recurring' => !empty($data['is_recurring']),
            'recurring_pattern' => !empty($data['is_recurring']) ? ($data['recurring_pattern'] ?? null) : null,
            'recurring_until' => !empty($data['is_recurring']) ? ($data['recurring_until'] ?? null) : null,
            'max_patients' => $data['max_patients'],
            'notes' => $data['notes'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            '_who_added' => Auth::user()->id,
        ];

        $schedule = DoctorSchedule::create($scheduleData);

        // Generate recurring schedules if needed
        if (!empty($data['is_recurring']) && !empty($data['recurring_pattern']) && !empty($data['recurring_until'])) {
            $this->generateRecurringSchedules($scheduleData, $data['recurring_until']);
        }

        return $schedule;
    }

    /**
     * Find a schedule by ID.
     */
    public function find(int $id): ?DoctorSchedule
    {
        return DoctorSchedule::where('id', $id)->first();
    }

    /**
     * Update an existing schedule.
     */
    public function updateSchedule(int $id, array $data): bool
    {
        return (bool) DoctorSchedule::where('id', $id)->update([
            'doctor_id' => $data['doctor_id'],
            'schedule_date' => $data['schedule_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'is_recurring' => !empty($data['is_recurring']),
            'recurring_pattern' => !empty($data['is_recurring']) ? ($data['recurring_pattern'] ?? null) : null,
            'recurring_until' => !empty($data['is_recurring']) ? ($data['recurring_until'] ?? null) : null,
            'max_patients' => $data['max_patients'],
            'notes' => $data['notes'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'changed_by' => Auth::user()->id,
        ]);
    }

    /**
     * Delete a schedule (soft-delete).
     */
    public function deleteSchedule(int $id): bool
    {
        return (bool) DoctorSchedule::where('id', $id)->delete();
    }

    /**
     * Get schedules for calendar view.
     */
    public function getCalendarSchedules(string $start, string $end): Collection
    {
        return DoctorSchedule::with('doctor')
            ->whereBetween('schedule_date', [$start, $end])
            ->get()
            ->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'title' => $schedule->doctor->full_name . ' (' . substr($schedule->start_time, 0, 5) . '-' . substr($schedule->end_time, 0, 5) . ')',
                    'start' => $schedule->schedule_date->format('Y-m-d') . 'T' . $schedule->start_time,
                    'end' => $schedule->schedule_date->format('Y-m-d') . 'T' . $schedule->end_time,
                    'color' => $this->getDoctorColor($schedule->doctor_id),
                ];
            });
    }

    /**
     * Generate recurring schedules.
     */
    private function generateRecurringSchedules(array $data, string $until): void
    {
        $currentDate = Carbon::parse($data['schedule_date']);
        $endDate = Carbon::parse($until);
        $pattern = $data['recurring_pattern'];

        while ($currentDate->lt($endDate)) {
            switch ($pattern) {
                case 'daily':
                    $currentDate->addDay();
                    break;
                case 'weekly':
                    $currentDate->addWeek();
                    break;
                case 'monthly':
                    $currentDate->addMonth();
                    break;
            }

            if ($currentDate->lte($endDate)) {
                $newData = $data;
                $newData['schedule_date'] = $currentDate->format('Y-m-d');
                DoctorSchedule::create($newData);
            }
        }
    }

    /**
     * Get a consistent color for each doctor.
     */
    private function getDoctorColor(int $doctorId): string
    {
        $colors = ['#3498db', '#2ecc71', '#e74c3c', '#9b59b6', '#f39c12', '#1abc9c', '#e67e22', '#34495e'];
        return $colors[$doctorId % count($colors)];
    }
}
