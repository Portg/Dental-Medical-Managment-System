<?php

namespace App\Services;

use App\Appointment;
use App\AppointmentHistory;
use App\Chair;
use App\DoctorSchedule;
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
                DB::raw('DATE_FORMAT(appointments.start_date, "%d-%b-%Y") as start_date'),
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
            ->leftJoin('medical_services', 'medical_services.id', 'appointments.service_id')
            ->whereNull('appointments.deleted_at')
            ->select(
                'appointments.*',
                'patients.surname', 'patients.othername', 'patients.phone_no as p_phone',
                'patients.gender as p_gender',
                'users.surname as d_surname', 'users.othername as d_othername',
                'medical_services.name as service_name'
            );

        if ($start && $end) {
            $query->whereBetween('appointments.sort_by', [$start, $end]);
        }

        $statusColorMap = [
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
                'service_name' => $value->service_name ?? '',
                'start_time' => date_format($startDt, 'H:i'),
                'end_time' => date_format($endDt, 'H:i'),
                'appointment_no' => $value->appointment_no ?? '',
                'doctor_id' => $value->doctor_id,
            ];

            if ((bool) SystemSetting::get('clinic.show_appointment_notes', true)) {
                $extendedProps['notes'] = $value->notes ?? '';
            }

            $events[] = [
                'id' => $value->id,
                'title' => $patientName . ' - ' . $doctorName,
                'start' => date_format($startDt, "Y-m-d\TH:i:s"),
                'end' => date_format($endDt, "Y-m-d\TH:i:s"),
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
        $key = 'appointment.' . strtolower(str_replace(' ', '_', $status));
        $translated = __($key);
        return $translated !== $key ? $translated : $status;
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

    // ─── CUD operations ──────────────────────────────────────────

    /**
     * Create a new appointment.
     */
    public function createAppointment(array $data): ?Appointment
    {
        $time24 = date("H:i:s", strtotime($data['appointment_time']));

        $appTime = ($data['visit_information'] == Appointment::VISIT_WALK_IN)
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
            'duration_minutes' => $data['duration_minutes'] ?? (int) SystemSetting::get('clinic.default_duration', 30),
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

        if ($status == "Created" && $record->visit_information != Appointment::VISIT_WALK_IN) {
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
     */
    public function getDoctorTimeSlots(int $doctorId, string $date): array
    {
        $schedule = DoctorSchedule::where('doctor_id', $doctorId)
            ->where('schedule_date', $date)
            ->first();

        $slotInterval = (int) SystemSetting::get('clinic.slot_interval', 30);
        $intervalSec = $slotInterval * 60;

        $slots = [];
        if ($schedule) {
            $startTime = strtotime($schedule->start_time);
            $endTime = strtotime($schedule->end_time);

            for ($time = $startTime; $time < $endTime; $time += $intervalSec) {
                $timeStr = date('H:i', $time);
                $period = $time < strtotime('12:00') ? 'morning' : 'afternoon';
                $slots[] = ['time' => $timeStr, 'period' => $period, 'is_rest' => false];
            }
        } else {
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
        foreach ($existingAppointments as $appt) {
            $timeKey = date('H:i', strtotime($appt->start_time));
            $booked[$timeKey] = [
                'patient_name' => NameHelper::join($appt->p_surname, $appt->p_othername),
            ];
        }

        return [
            'slots' => $slots,
            'booked' => $booked,
            'has_schedule' => $schedule !== null,
        ];
    }

    // ─── DataTable formatting ────────────────────────────────────

    /**
     * Build DataTables response for the appointments index page.
     */
    public function buildIndexDataTable($data)
    {
        return DataTables::of($data)
            ->addIndexColumn()
            ->filter(function ($instance) {
            })
            ->editColumn('sort_by', function ($row) {
                return $row->sort_by ? \Carbon\Carbon::parse($row->sort_by)->format('Y-m-d H:i') : '-';
            })
            ->editColumn('status', function ($row) {
                $key = 'appointment.' . strtolower(str_replace(' ', '_', $row->status));
                $translated = __($key);
                return $translated !== $key ? $translated : $row->status;
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
                return e($row->visit_information) . $action;
            })
            ->addColumn('invoice_status', function ($row) {
                if ($row->has_invoice_status === 'pending') {
                    return '<span class="text-danger">' . __('messages.no_invoice_yet') . '</span>';
                }
                return '<span class="text-primary">' . __('messages.invoice_already_generated') . '</span>';
            })
            ->addColumn('action', function ($row) {
                $invoice_action = $row->has_invoice_status === 'pending'
                    ? '<a href="#" onclick="RecordPayment(' . $row->id . ')" >' . __('invoices.generate_invoice') . '</a>'
                    : '<a href="' . url('invoices/' . $row->invoice_id) . '">' . __('invoices.view_invoice') . '</a>';

                return '
                  <div class="btn-group">
                    <button class="btn blue dropdown-toggle" type="button" data-toggle="dropdown"
                            aria-expanded="false"> ' . __('common.action') . '
                        <i class="fa fa-angle-down"></i>
                    </button>
                    <ul class="dropdown-menu" role="menu">
                          <li>
                            <a href="#" onclick="RescheduleAppointment(' . $row->id . ')" >' . __('appointment.reschedule') . '</a>
                        </li>
                         <li>
                          ' . $invoice_action . '
                        </li>
                          <li>
                            <a href="#" onclick="editRecord(' . $row->id . ')" >' . __('common.edit') . '</a>
                        </li>
                          <li>
                            <a href="' . url('medical-treatment/' . $row->id) . '" >' . __('medical_treatment.treatment_history') . '</a>
                        </li>
                         <li>
                           <a href="#" onclick="deleteRecord(' . $row->id . ')">' . __('common.delete') . '</a>
                        </li>
                    </ul>
                </div>
                ';
            })
            ->rawColumns(['visit_information', 'invoice_status', 'action'])
            ->make(true);
    }
}
