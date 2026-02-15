<?php

namespace App\Services;

use App\Appointment;
use App\AppointmentHistory;
use App\Http\Helper\FunctionsHelper;
use App\Http\Helper\NameHelper;
use App\Jobs\SendAppointmentSms;
use App\Notifications\ReminderNotification;
use App\Patient;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Thomasjohnkane\Snooze\ScheduledNotification;

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
                DB::raw('DATE_FORMAT(appointments.start_date, "%d-%b-%Y") as start_date'),
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
     * Get calendar events for FullCalendar.
     */
    public function getCalendarEvents(?string $start, ?string $end): array
    {
        $query = DB::table('appointments')
            ->join('patients', 'patients.id', 'appointments.patient_id')
            ->join('users', 'users.id', 'appointments.doctor_id')
            ->whereNull('appointments.deleted_at')
            ->select('appointments.*', 'patients.surname', 'patients.othername',
                'users.surname as d_surname', 'users.othername as d_othername');

        if ($start && $end) {
            $query->whereBetween('appointments.sort_by', [$start, $end]);
        }

        $events = [];
        foreach ($query->get() as $value) {
            $events[] = [
                'title' => NameHelper::join($value->surname, $value->othername),
                'start' => date_format(date_create($value->sort_by), "Y-m-d\TH:i:s"),
                'end' => date_format(date_create($value->sort_by), "Y-m-d\TH:i:s"),
                'textColor' => '#ffffff',
            ];
        }

        return $events;
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
        return DB::table('appointments')
            ->join('users', 'users.id', 'appointments.doctor_id')
            ->join('patients', 'patients.id', 'appointments.patient_id')
            ->where('appointments.id', $id)
            ->whereNull('appointments.deleted_at')
            ->select('appointments.*', 'users.surname as d_surname', 'users.othername as d_othername',
                'patients.surname', 'patients.othername')
            ->first();
    }

    // ─── CUD operations ──────────────────────────────────────────

    /**
     * Create a new appointment.
     */
    public function createAppointment(array $data): ?Appointment
    {
        $time24 = date("H:i:s", strtotime($data['appointment_time']));

        $appTime = ($data['visit_information'] == 'walk_in')
            ? (new DateTime('now'))->format("h:i A")
            : $data['appointment_time'];

        $appointment = Appointment::create([
            'appointment_no' => Appointment::AppointmentNo(),
            'patient_id' => $data['patient_id'],
            'doctor_id' => $data['doctor_id'],
            'start_date' => $data['appointment_date'],
            'end_date' => $data['appointment_date'],
            'start_time' => $appTime,
            'visit_information' => $data['visit_information'],
            'notes' => $data['notes'] ?? null,
            'branch_id' => Auth::user()->branch_id,
            'sort_by' => $data['appointment_date'] . " " . $time24,
            'chair_id' => $data['chair_id'] ?? null,
            'service_id' => $data['service_id'] ?? null,
            'appointment_type' => $data['appointment_type'] ?? 'revisit',
            'duration_minutes' => $data['duration_minutes'] ?? 30,
            '_who_added' => Auth::user()->id,
        ]);

        if ($appointment) {
            $this->createAppointmentHistory($appointment->id, "Created");
        }

        return $appointment;
    }

    /**
     * Update an existing appointment.
     */
    public function updateAppointment(int $id, array $data): bool
    {
        $time24 = date("H:i:s", strtotime($data['appointment_time']));

        return (bool) Appointment::where('id', $id)->update([
            'patient_id' => $data['patient_id'],
            'doctor_id' => $data['doctor_id'],
            'start_date' => $data['appointment_date'],
            'end_date' => $data['appointment_date'],
            'start_time' => $data['appointment_time'],
            'visit_information' => $data['visit_information'],
            'sort_by' => $data['appointment_date'] . " " . $time24,
            'notes' => $data['notes'] ?? null,
            '_who_added' => Auth::user()->id,
        ]);
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
            'visit_information' => 'appointment',
            'status' => 'Rescheduled',
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
    public function createAppointmentHistory(int $appointmentId, string $status): void
    {
        $message = '';
        $record = DB::table('appointments')
            ->leftJoin('patients', 'patients.id', 'appointments.patient_id')
            ->where('appointments.id', $appointmentId)
            ->select('patients.surname', 'patients.othername', 'patients.phone_no',
                'appointments.*', 'appointments.visit_information',
                DB::raw('DATE_FORMAT(appointments.start_date, "%d-%b-%Y") as formatted_date'))
            ->first();

        if ($status == "Created" && $record->visit_information != "walk_in") {
            $message = __('sms.appointment_scheduled', [
                'name' => $record->othername,
                'company' => config('app.name', 'Laravel'),
                'date' => $record->formatted_date,
                'time' => $record->start_time,
            ]);

            $patient = Patient::where('id', $record->patient_id)->first();
            if ($record->phone_no != null) {
                dispatch(new SendAppointmentSms($record->phone_no, $message, "Appointment"));

                $convertedTime = date("H:i:s", strtotime($record->start_time));
                $appointmentTime = $record->start_date . " " . $convertedTime;
                $reminderDate = date('Y-m-d H:i:s', strtotime('-1 day', strtotime($appointmentTime)));
                $sendReminder = FunctionsHelper::getRangeDateString($reminderDate);

                if ($sendReminder == "Tomorrow" || $sendReminder == "future days") {
                    ScheduledNotification::create(
                        $patient,
                        new ReminderNotification('Dear, ' . $patient->othername .
                            " This is a polite reminder about your appointment at " . env('CompanyName', null) . " scheduled for "
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
    public function getChairs(int $branchId): Collection
    {
        return DB::table('chairs')
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->select('id', 'name as text')
            ->get();
    }

    /**
     * Get doctor time slots for a specific date.
     */
    public function getDoctorTimeSlots(int $doctorId, string $date): array
    {
        $dayOfWeek = date('l', strtotime($date));
        $schedule = DB::table('doctor_schedules')
            ->where('doctor_id', $doctorId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();

        $slots = [];
        if ($schedule) {
            $startTime = strtotime($schedule->start_time);
            $endTime = strtotime($schedule->end_time);
            $lunchStart = $schedule->lunch_start ? strtotime($schedule->lunch_start) : strtotime('12:00');
            $lunchEnd = $schedule->lunch_end ? strtotime($schedule->lunch_end) : strtotime('14:00');
            $interval = 30 * 60;

            for ($time = $startTime; $time < $endTime; $time += $interval) {
                $isRest = ($time >= $lunchStart && $time < $lunchEnd);
                $timeStr = date('H:i', $time);
                $period = $time < strtotime('12:00') ? 'morning' : 'afternoon';
                $slots[] = ['time' => $timeStr, 'period' => $period, 'is_rest' => $isRest];
            }
        } else {
            $defaultTimes = [
                ['09:00', 'morning'], ['09:30', 'morning'], ['10:00', 'morning'], ['10:30', 'morning'],
                ['11:00', 'morning'], ['11:30', 'morning'],
                ['14:00', 'afternoon'], ['14:30', 'afternoon'], ['15:00', 'afternoon'], ['15:30', 'afternoon'],
                ['16:00', 'afternoon'], ['16:30', 'afternoon'], ['17:00', 'afternoon'], ['17:30', 'afternoon'],
            ];
            foreach ($defaultTimes as $dt) {
                $slots[] = ['time' => $dt[0], 'period' => $dt[1], 'is_rest' => false];
            }
        }

        // Existing bookings
        $existingAppointments = DB::table('appointments')
            ->leftJoin('patients', 'patients.id', 'appointments.patient_id')
            ->where('appointments.doctor_id', $doctorId)
            ->where('appointments.start_date', $date)
            ->whereNull('appointments.deleted_at')
            ->whereNotIn('appointments.status', ['Cancelled', 'no_show'])
            ->select('appointments.start_time', 'patients.surname as p_surname', 'patients.othername as p_othername')
            ->get();

        $booked = [];
        foreach ($existingAppointments as $appt) {
            $timeKey = date('H:i', strtotime($appt->start_time));
            $booked[$timeKey] = [
                'patient_name' => NameHelper::join($appt->p_surname, $appt->p_othername),
            ];
        }

        return ['slots' => $slots, 'booked' => $booked];
    }
}
