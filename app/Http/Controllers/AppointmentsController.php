<?php

namespace App\Http\Controllers;

use App\Appointment;
use App\AppointmentHistory;
use App\Http\Helper\FunctionsHelper;
use App\Invoice;
use App\Jobs\SendAppointmentSms;
use App\Notifications\ReminderNotification;
use App\Patient;
use Carbon\Carbon;
use DateTime;
use ExcelReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use MaddHatter\LaravelFullcalendar\Facades\Calendar;
use Thomasjohnkane\Snooze\ScheduledNotification;
use Yajra\DataTables\DataTables;

class AppointmentsController extends Controller
{
    protected $dateTime;
    protected $app_time;

    /**
     * AppointmentsController constructor.
     */
    public function __construct()
    {
        $this->dateTime = new DateTime('now');
    }
    /**
     * AppointmentsController constructor.
     */

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */


    public function index(Request $request)
    {
        if ($request->ajax()) {
            // Build query with query builder approach for flexible filtering
            $query = DB::table('appointments')
                ->join('patients', 'patients.id', 'appointments.patient_id')
                ->join('users', 'users.id', 'appointments.doctor_id')
                ->leftJoin('invoices', 'invoices.appointment_id', 'appointments.id')
                ->whereNull('appointments.deleted_at')
                ->select(
                    'appointments.*',
                    'patients.surname',
                    'patients.othername',
                    'patients.phone_no',
                    'users.surname as d_surname',
                    'users.othername as d_othername',
                    DB::raw('DATE_FORMAT(appointments.start_date, "%d-%b-%Y") as start_date'),
                    DB::raw('CASE WHEN invoices.id IS NOT NULL THEN "invoiced" ELSE "pending" END as has_invoice_status')
                );

            // Quick search filter (searches patient name, phone, appointment_no)
            if (!empty($request->get('quick_search'))) {
                $search = $request->get('quick_search');
                $query->where(function($q) use ($search) {
                    $q->where('patients.surname', 'like', '%' . $search . '%')
                      ->orWhere('patients.othername', 'like', '%' . $search . '%')
                      ->orWhere('patients.phone_no', 'like', '%' . $search . '%')
                      ->orWhere('appointments.appointment_no', 'like', '%' . $search . '%');
                });
            }

            // Appointment No filter
            if (!empty($request->get('appointment_no'))) {
                $query->where('appointments.appointment_no', '=', $request->appointment_no);
            }

            // Date range filter
            if (!empty($request->get('start_date')) && !empty($request->get('end_date'))) {
                FunctionsHelper::storeDateFilter($request);
                $query->whereBetween(DB::raw('DATE_FORMAT(appointments.sort_by, \'%Y-%m-%d\')'),
                    array($request->start_date, $request->end_date));
            }

            // Doctor filter
            if (!empty($request->get('filter_doctor'))) {
                $query->where('appointments.doctor_id', $request->filter_doctor);
            }

            // Invoice status filter
            if (!empty($request->get('filter_invoice_status'))) {
                if ($request->filter_invoice_status == 'invoiced') {
                    $query->whereNotNull('invoices.id');
                } else if ($request->filter_invoice_status == 'pending') {
                    $query->whereNull('invoices.id');
                }
            }

            // DataTables default search
            if (!empty($request->get('search'))) {
                $search = $request->get('search');
                if (is_array($search) && !empty($search['value'])) {
                    $searchValue = $search['value'];
                    $query->where(function($q) use ($searchValue) {
                        $q->where('patients.surname', 'like', '%' . $searchValue . '%')
                          ->orWhere('patients.othername', 'like', '%' . $searchValue . '%');
                    });
                }
            }

            $data = $query->orderBy('appointments.sort_by', 'desc')->get();

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
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
                    return $row->surname . " " . $row->othername;
                })
                ->addColumn('doctor', function ($row) {
                    return $row->d_surname . " " . $row->d_othername;
                })
                ->addColumn('visit_information', function ($row) {
                    $action = '';
                    if ($row->visit_information == "Review Treatment" && $row->status != "Waiting") {
                        $action = '<br> <a href="#"  onclick="ReactivateAppointment(' .
                            $row->id . ')"  class="text-primary">Re-activate Appointment</a>';
                    }
                    return $row->visit_information . "" . $action;
                })
                ->addColumn('invoice_status', function ($row) {
                    $invoice_status = '';
                    $has_invoice = Invoice::where('appointment_id', $row->id)->first();
                    if ($has_invoice == null) {
                        $invoice_status = '<span class="text-danger">' . __('messages.no_invoice_yet') . '</span>';
                    } else {
                        $invoice_status = '<span class="text-primary">' . __('messages.invoice_already_generated') . '</span>';
                    }
                    return $invoice_status;
                })
                ->addColumn('action', function ($row) {
                    $invoice_action = '';
                    //check if the appointment has gotten any invoice

                    $has_invoice = Invoice::where('appointment_id', $row->id)->first();
                    if ($has_invoice == null) {
                        $invoice_action = '<a href="#" onclick="RecordPayment(' . $row->id . ')" >' . __('invoices.generate_invoice') . '</a>';
                    } else {
                        $invoice_action = '<a href="' . url('invoices/' . $has_invoice->id) . '">' . __('invoices.view_invoice') . '</a>';
                    }

                    $btn = '
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
                    return $btn;
                })
                ->rawColumns(['visit_information', 'invoice_status', 'action'])
                ->make(true);
        }
        $incoming = [];
        $appointment_data = DB::table('appointments')
            ->join('patients', 'patients.id', 'appointments.patient_id')
            ->join('users', 'users.id', 'appointments.doctor_id')
            ->whereNull('appointments.deleted_at')
            ->select('appointments.*', 'patients.surname', 'patients.othername', 'users.surname as 
                    d_surname', 'users.othername as d_othername')
            ->orderBy('appointments.sort_by', 'desc')
            ->get();
//        if ($appointment_data->count()) {
        foreach ($appointment_data as $key => $value) {
            $incoming[] = Calendar::event(
                $value->surname . " " . $value->othername, //event title
                false,
                date_format(date_create($value->sort_by), "Y-m-d H:i:s"),
                date_format(date_create($value->sort_by), "Y-m-d H:i:s"),
                null,
                // Add color
                [
//                        'color' => '#000000',
                    'textColor' => '#ffffff',
                ]
            );
        }
//        }
        $calendar = Calendar::addEvents($incoming)
            ->setOptions([ //set fullcalendar options;
                'locale' => app()->getLocale() === 'zh-CN' ? 'zh-cn' : 'en'
            ]);
        return view('appointments.index', compact('calendar'));
    }

    public function exportAppointmentReport(Request $request)
    {
        if ($request->session()->get('from') != '' && $request->session()->get('to') != '') {
            $query = DB::table('appointments')
                ->join('patients', 'patients.id', 'appointments.patient_id')
                ->join('users', 'users.id', 'appointments.doctor_id')
                ->whereNull('appointments.deleted_at')
                ->whereBetween(DB::raw('DATE(appointments.sort_by)'), array($request->session()->get('from'),
                    $request->session()->get('to')))
                ->orderBy('appointments.sort_by', 'DESC');

        } else {
            $query = DB::table('appointments')
                ->join('patients', 'patients.id', 'appointments.patient_id')
                ->join('users', 'users.id', 'appointments.doctor_id')
                ->whereNull('appointments.deleted_at')
                ->orderBy('appointments.sort_by', 'DESC');
        }


        $query->select('appointments.*', 'patients.surname', 'patients.othername', 'users.surname as 
                    d_surname', 'users.othername as d_othername');

        $columns = [
            'surname' => 'surname',
            'othername' => 'othername',
            __('appointment.appointment_date') => 'start_date',
            __('appointment.appointment_time') => 'start_time',
            __('appointment.visit_information') => 'visit_information',
            __('appointment.appointment_status') => 'status'
        ];

        return ExcelReport::of(null,
            [
                'Appointments Report ' => "From:   " . $request->session()->get('from') . "    To:    " .
                    $request->session()
                        ->get('to'),
            ], $query, $columns)
            ->simple()
            ->download('appointments-report' . date('Y-m-d H:m:s'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'visit_information' => 'required',
            'appointment_date' => 'required',
            'appointment_time' => 'required',
            'patient_id' => 'required',
            'doctor_id' => 'required'
        ])->validate();

        //time_order column
        $time_24_hours = date("H:i:s", strtotime($request->appointment_time));
        //check visit information
        if ($request->visit_information == 'walk_in') {
            $this->app_time = $this->dateTime->format("h:i A");
        } else {
            $this->app_time = $request->appointment_time;
        }

        $status = Appointment::create([
            'appointment_no' => Appointment::AppointmentNo(),
            'patient_id' => $request->patient_id,
            'doctor_id' => $request->doctor_id,
            'start_date' => $request->appointment_date,
            'end_date' => $request->appointment_date,
            'start_time' => $this->app_time,
            'visit_information' => $request->visit_information,
            'notes' => $request->notes,
            'branch_id' => Auth::User()->branch_id,
            'sort_by' => $request->appointment_date . " " . $time_24_hours,
            // New fields per design spec F-APT-001
            'chair_id' => $request->chair_id,
            'service_id' => $request->service_id,
            'appointment_type' => $request->appointment_type ?? 'revisit',
            'duration_minutes' => $request->duration_minutes ?? 30,
            '_who_added' => Auth::User()->id
        ]);
        if ($status) {
            //generate appointment history
            $this->CreateAppointmentHistory($status->id, "Created");
            return response()->json(['message' => __('messages.appointment_created_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }

    public function CreateAppointmentHistory($appointment_id, $status)
    {
        $message = '';
        $record = DB::table('appointments')
            ->leftJoin('patients', 'patients.id', 'appointments.patient_id')
            ->where('appointments.id', $appointment_id)
            ->select('patients.surname', 'patients.othername', 'patients.phone_no',
                'appointments.*', 'appointments.visit_information',
                DB::raw
                ('DATE_FORMAT(appointments.start_date, "%d-%b-%Y") as formatted_date'))
            ->first();

        if ($status == "Created") { //send out the message on appointment scheduling
            //check if the appointment is not walk_in
            if ($record->visit_information != "walk_in") {
                //now generate the message to send to the patient
                $message = __('sms.appointment_scheduled', [
                    'name' => $record->othername,
                    'company' => config('app.name', 'Laravel'),
                    'date' => $record->formatted_date,
                    'time' => $record->start_time
                ]);
                //sms/email to the patient
                $patient = Patient::where('id', $record->patient_id)->first();
                if ($record->phone_no != null) {
                    $sendJob = new SendAppointmentSms($record->phone_no, $message, "Appointment");
                    $this->dispatch($sendJob);
                    //set the notification reminder scheduler
                    //convert time to 24hrs
                    $converted_appointment_time = date("H:i:s", strtotime($record->start_time));

                    $appointment_time = $record->start_date . " " . $converted_appointment_time; //future appointment
                    // date
                    //day before the appointment
                    $reminder_date = date('Y-m-d H:i:s', (strtotime('-1 day', strtotime($appointment_time)
                    )));

                    //check when the remainder should be sent
                    $sendReminder = FunctionsHelper::getRangeDateString($reminder_date);

                    if ($sendReminder == "Tomorrow" || $sendReminder == "future days") { //now set the reminder
                        ScheduledNotification::create(
                            $patient, // Target
                            new ReminderNotification('Dear, ' . $patient->othername .
                                " This is a polite reminder about your appointment at ".env('CompanyName',null)." scheduled for "
                                . $record->formatted_date . " at " . $record->start_time), //
                            // Notification
                            Carbon::parse($reminder_date)// Send At
                        );
                    }
                }
            }
        }
        //now track appointment history from creation to reschedule
        AppointmentHistory::create([
            'start_date' => $record->start_date,
            'end_date' => $record->start_date,
            'start_time' => $record->start_time,
            'status' => $status,
            'message' => $message,
            'appointment_id' => $appointment_id
        ]);

    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public
    function edit($id)
    {
        $appointment = DB::table("appointments")
            ->join('users', 'users.id', 'appointments.doctor_id')
            ->join('patients', 'patients.id', 'appointments.patient_id')
            ->where('appointments.id', $id)
            ->whereNull('appointments.deleted_at')
            ->select('appointments.*', 'users.surname as d_surname', 'users.othername as d_othername', 'patients.surname', 'patients.othername')
            ->first();
        return response()->json($appointment);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public
    function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'visit_information' => 'required',
            'patient_id' => 'required',
            'appointment_date' => 'required',
            'appointment_time' => 'required',
            'doctor_id' => 'required',
        ])->validate();


        //time_order column
        $time_24_hours = date("H:i:s", strtotime($request->appointment_time));

        //reactivated_appointment
        $status = Appointment::where('id', $id)->update([
            'patient_id' => $request->patient_id,
            'doctor_id' => $request->doctor_id,
            'start_date' => $request->appointment_date,
            'end_date' => $request->appointment_date,
            'start_time' => $request->appointment_time,
            'visit_information' => $request->visit_information,
            'sort_by' => $request->appointment_date . " " . $time_24_hours,
            'notes' => $request->notes,
            '_who_added' => Auth::User()->id
        ]);
        if ($status) {
            return response()->json(['message' => __('messages.appointment_updated_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function sendReschedule(Request $request)
    {
        //time_order column
        $time_24_hours = date("H:i:s", strtotime($request->appointment_time));
        $success = Appointment::where('id', $request->id)->update([
            'start_date' => $request->appointment_date,
            'end_date' => $request->appointment_date,
            'start_time' => $request->appointment_time,
            'sort_by' => $request->appointment_date . " " . $time_24_hours,
            'visit_information' => 'appointment',
            'status' => 'Rescheduled']);
        if ($success) {
            //generate appointment history
            $this->CreateAppointmentHistory($request->id, "Rescheduled");
            return FunctionsHelper::messageResponse(__('messages.appointment_rescheduled_successfully'),
                $success);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $status = Appointment::where('id', $id)->delete();
        if ($status) {
            return response()->json(['message' => __('messages.appointment_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);

    }

    /**
     * Get available chairs for appointment form (design spec F-APT-001)
     *
     * @return JsonResponse
     */
    public function getChairs()
    {
        $chairs = DB::table('chairs')
            ->where('branch_id', Auth::User()->branch_id)
            ->where('is_active', true)
            ->select('id', 'name as text')
            ->get();

        return response()->json($chairs);
    }

    /**
     * Get doctor time slots for a specific date (design spec F-APT-001)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getDoctorTimeSlots(Request $request)
    {
        $doctorId = $request->doctor_id;
        $date = $request->date;

        if (!$doctorId || !$date) {
            return response()->json(['slots' => [], 'booked' => []]);
        }

        // Get doctor's schedule for this day of week
        $dayOfWeek = date('l', strtotime($date)); // Monday, Tuesday, etc.
        $schedule = DB::table('doctor_schedules')
            ->where('doctor_id', $doctorId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();

        // Generate time slots based on schedule or use defaults
        $slots = [];
        if ($schedule) {
            $startTime = strtotime($schedule->start_time);
            $endTime = strtotime($schedule->end_time);
            $lunchStart = $schedule->lunch_start ? strtotime($schedule->lunch_start) : strtotime('12:00');
            $lunchEnd = $schedule->lunch_end ? strtotime($schedule->lunch_end) : strtotime('14:00');
            $interval = 30 * 60; // 30 minutes

            for ($time = $startTime; $time < $endTime; $time += $interval) {
                $isRest = ($time >= $lunchStart && $time < $lunchEnd);
                $timeStr = date('H:i', $time);
                $period = $time < strtotime('12:00') ? 'morning' : 'afternoon';

                $slots[] = [
                    'time' => $timeStr,
                    'period' => $period,
                    'is_rest' => $isRest
                ];
            }
        } else {
            // Default slots if no schedule defined
            $defaultTimes = [
                ['09:00', 'morning'], ['09:30', 'morning'], ['10:00', 'morning'], ['10:30', 'morning'],
                ['11:00', 'morning'], ['11:30', 'morning'],
                ['14:00', 'afternoon'], ['14:30', 'afternoon'], ['15:00', 'afternoon'], ['15:30', 'afternoon'],
                ['16:00', 'afternoon'], ['16:30', 'afternoon'], ['17:00', 'afternoon'], ['17:30', 'afternoon']
            ];
            foreach ($defaultTimes as $dt) {
                $slots[] = ['time' => $dt[0], 'period' => $dt[1], 'is_rest' => false];
            }
        }

        // Get existing bookings for this doctor on this date
        $existingAppointments = DB::table('appointments')
            ->leftJoin('patients', 'patients.id', 'appointments.patient_id')
            ->where('appointments.doctor_id', $doctorId)
            ->where('appointments.start_date', $date)
            ->whereNull('appointments.deleted_at')
            ->whereNotIn('appointments.status', ['Cancelled', 'no_show'])
            ->select(
                'appointments.start_time',
                DB::raw("CONCAT(patients.surname, ' ', patients.othername) as patient_name")
            )
            ->get();

        $booked = [];
        foreach ($existingAppointments as $appt) {
            $timeKey = date('H:i', strtotime($appt->start_time));
            $booked[$timeKey] = [
                'patient_name' => $appt->patient_name
            ];
        }

        return response()->json([
            'slots' => $slots,
            'booked' => $booked
        ]);
    }
}
