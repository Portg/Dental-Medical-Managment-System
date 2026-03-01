<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Jobs\SendAppointmentSms;
use App\Services\AppointmentService;
use App\SystemSetting;
use App\Exports\AppointmentExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AppointmentsController extends Controller
{
    private AppointmentService $appointmentService;

    public function __construct(AppointmentService $appointmentService)
    {
        $this->appointmentService = $appointmentService;

        $this->middleware('can:view-appointments')->only(['index', 'show', 'calendarEvents', 'exportAppointmentReport', 'getChairs', 'getDoctorTimeSlots']);
        $this->middleware('can:create-appointments')->only(['create', 'store']);
        $this->middleware('can:edit-appointments')->only(['edit', 'update', 'sendReschedule']);
        $this->middleware('can:delete-appointments')->only(['destroy']);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            if (!empty($request->get('start_date')) && !empty($request->get('end_date'))) {
                FunctionsHelper::storeDateFilter($request);
            }

            $data = $this->appointmentService->getAppointmentList($request->only([
                'quick_search', 'appointment_no', 'start_date', 'end_date',
                'filter_doctor', 'filter_invoice_status', 'search',
            ]));

            return $this->appointmentService->buildIndexDataTable($data);
        }
        return view('appointments.index');
    }

    public function calendarEvents(Request $request)
    {
        return response()->json(
            $this->appointmentService->getCalendarEvents($request->start, $request->end)
        );
    }

    public function exportAppointmentReport(Request $request)
    {
        $from = $request->session()->get('from') ?: null;
        $to = $request->session()->get('to') ?: null;

        $data = $this->appointmentService->getExportData($from, $to);

        \App\OperationLog::log('export', '预约管理', 'Appointment');
        \App\OperationLog::checkExportFrequency();

        return Excel::download(new AppointmentExport($data), 'appointments-report-' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'visit_information' => 'required',
            'appointment_date' => 'required',
            'appointment_time' => 'required',
            'patient_id' => 'required',
            'doctor_id' => 'required',
        ])->validate();

        $advanceError = $this->validateAdvanceBooking($request->appointment_date, $request->appointment_time);
        if ($advanceError) {
            return response()->json(['message' => $advanceError, 'status' => false]);
        }

        $duration = (int) ($request->duration_minutes ?: SystemSetting::get('clinic.default_duration', 30));
        $conflict = $this->appointmentService->checkOverbooking(
            (int) $request->doctor_id, $request->appointment_date, $request->appointment_time, $duration
        );
        if ($conflict) {
            return response()->json([
                'message' => __('appointment.overbooking_conflict'),
                'status' => false,
            ]);
        }

        $appointment = $this->appointmentService->createAppointment($request->only([
            'visit_information', 'appointment_date', 'appointment_time',
            'patient_id', 'doctor_id', 'notes', 'chair_id', 'service_id',
            'appointment_type', 'duration_minutes',
        ]));

        if ($appointment) {
            return response()->json(['message' => __('messages.appointment_created_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return response()->json($this->appointmentService->getAppointmentForEdit((int) $id));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'visit_information' => 'required',
            'patient_id' => 'required',
            'appointment_date' => 'required',
            'appointment_time' => 'required',
            'doctor_id' => 'required',
        ])->validate();

        $duration = (int) ($request->duration_minutes ?: SystemSetting::get('clinic.default_duration', 30));
        $conflict = $this->appointmentService->checkOverbooking(
            (int) $request->doctor_id, $request->appointment_date, $request->appointment_time, $duration, (int) $id
        );
        if ($conflict) {
            return response()->json([
                'message' => __('appointment.overbooking_conflict'),
                'status' => false,
            ]);
        }

        $status = $this->appointmentService->updateAppointment((int) $id, $request->only([
            'visit_information', 'patient_id', 'appointment_date',
            'appointment_time', 'doctor_id', 'notes',
        ]));

        if ($status) {
            return response()->json(['message' => __('messages.appointment_updated_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }

    /**
     * Reschedule an appointment.
     */
    public function sendReschedule(Request $request): JsonResponse
    {
        $advanceError = $this->validateAdvanceBooking($request->appointment_date, $request->appointment_time);
        if ($advanceError) {
            return response()->json(['message' => $advanceError, 'status' => false]);
        }

        $success = $this->appointmentService->rescheduleAppointment((int) $request->id, $request->only([
            'appointment_date', 'appointment_time',
        ]));

        if ($success) {
            return FunctionsHelper::messageResponse(__('messages.appointment_rescheduled_successfully'), $success);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $status = $this->appointmentService->deleteAppointment((int) $id);

        if ($status) {
            return response()->json(['message' => __('messages.appointment_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }

    /**
     * Get available chairs for appointment form.
     */
    public function getChairs(): JsonResponse
    {
        return response()->json(
            $this->appointmentService->getChairs(Auth::user()->branch_id)
        );
    }

    /**
     * Get doctor time slots for a specific date.
     */
    public function getDoctorTimeSlots(Request $request): JsonResponse
    {
        if (!$request->doctor_id || !$request->date) {
            return response()->json(['slots' => [], 'booked' => []]);
        }

        return response()->json(
            $this->appointmentService->getDoctorTimeSlots((int) $request->doctor_id, $request->date)
        );
    }

    /**
     * Send an SMS reminder for a specific appointment.
     */
    public function sendReminder(int $id): JsonResponse
    {
        $record = DB::table('appointments')
            ->join('patients', 'patients.id', 'appointments.patient_id')
            ->where('appointments.id', $id)
            ->whereNull('appointments.deleted_at')
            ->select('patients.othername', 'patients.phone_no',
                'appointments.start_date', 'appointments.start_time',
                DB::raw('DATE_FORMAT(appointments.start_date, "%d-%b-%Y") as formatted_date'))
            ->first();

        if (!$record || !$record->phone_no) {
            return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
        }

        $message = __('sms.appointment_scheduled', [
            'name' => $record->othername,
            'company' => config('app.name', 'Laravel'),
            'date' => $record->formatted_date,
            'time' => $record->start_time,
        ]);

        dispatch(new SendAppointmentSms($record->phone_no, $message, 'Appointment'));

        return response()->json(['message' => __('appointment.reminder_sent'), 'status' => true]);
    }

    /**
     * Get active doctors for calendar resource view.
     * When ?date= is provided, also returns each doctor's schedule for that date.
     */
    public function doctors(Request $request): JsonResponse
    {
        $date = $request->query('date');

        $doctors = DB::table('users')
            ->where('is_doctor', 1)
            ->whereNull('deleted_at')
            ->select('id', 'surname', 'othername')
            ->orderBy('surname')
            ->get();

        $scheduleMap = [];
        if ($date) {
            $schedules = DB::table('doctor_schedules')
                ->whereNull('deleted_at')
                ->where('schedule_date', $date)
                ->select('doctor_id', 'start_time', 'end_time')
                ->get();

            foreach ($schedules as $s) {
                $scheduleMap[$s->doctor_id] = [
                    'start_time' => substr($s->start_time, 0, 5),
                    'end_time'   => substr($s->end_time, 0, 5),
                ];
            }
        }

        $hideOffDuty = (bool) SystemSetting::get('clinic.hide_off_duty_doctors', false);

        $result = $doctors->map(function ($row) use ($scheduleMap) {
            $item = [
                'id'    => $row->id,
                'title' => \App\Http\Helper\NameHelper::join($row->surname, $row->othername),
            ];
            if (isset($scheduleMap[$row->id])) {
                $item['schedule'] = $scheduleMap[$row->id];
            }
            return $item;
        });

        if ($hideOffDuty && $date && !empty($scheduleMap)) {
            $result = $result->filter(fn ($doc) => isset($scheduleMap[$doc['id']]));
        }

        return response()->json($result->values());
    }

    /**
     * Get a single doctor's basic info (for Select2 prefill).
     */
    public function doctorInfo(int $id): JsonResponse
    {
        $doctor = DB::table('users')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->select('id', 'surname', 'othername')
            ->first();

        if (!$doctor) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($doctor);
    }

    /**
     * Validate appointment date/time against max_advance_days and min_advance_hours.
     * Returns error message string on failure, or null on success.
     */
    private function validateAdvanceBooking(string $date, string $time): ?string
    {
        $appointmentDt = \Carbon\Carbon::parse($date . ' ' . date('H:i:s', strtotime($time)));
        $now = \Carbon\Carbon::now();

        $maxDays = (int) SystemSetting::get('clinic.max_advance_days', 0);
        if ($maxDays > 0) {
            $maxDate = $now->copy()->addDays($maxDays)->endOfDay();
            if ($appointmentDt->gt($maxDate)) {
                return __('appointment.max_advance_days_exceeded', ['days' => $maxDays]);
            }
        }

        $minHours = (int) SystemSetting::get('clinic.min_advance_hours', 0);
        if ($minHours > 0) {
            $minDt = $now->copy()->addHours($minHours);
            if ($appointmentDt->lt($minDt)) {
                return __('appointment.min_advance_hours_not_met', ['hours' => $minHours]);
            }
        }

        return null;
    }
}
