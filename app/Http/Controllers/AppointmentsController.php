<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Http\Helper\NameHelper;
use App\Invoice;
use App\Services\AppointmentService;
use App\Exports\AppointmentExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class AppointmentsController extends Controller
{
    private AppointmentService $appointmentService;

    public function __construct(AppointmentService $appointmentService)
    {
        $this->appointmentService = $appointmentService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            if (!empty($request->get('start_date')) && !empty($request->get('end_date'))) {
                FunctionsHelper::storeDateFilter($request);
            }

            $data = $this->appointmentService->getAppointmentList($request->all());

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
                    return NameHelper::join($row->surname, $row->othername);
                })
                ->addColumn('doctor', function ($row) {
                    return NameHelper::join($row->d_surname, $row->d_othername);
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
                    $has_invoice = Invoice::where('appointment_id', $row->id)->first();
                    if ($has_invoice == null) {
                        return '<span class="text-danger">' . __('messages.no_invoice_yet') . '</span>';
                    }
                    return '<span class="text-primary">' . __('messages.invoice_already_generated') . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $has_invoice = Invoice::where('appointment_id', $row->id)->first();
                    $invoice_action = $has_invoice == null
                        ? '<a href="#" onclick="RecordPayment(' . $row->id . ')" >' . __('invoices.generate_invoice') . '</a>'
                        : '<a href="' . url('invoices/' . $has_invoice->id) . '">' . __('invoices.view_invoice') . '</a>';

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

        $appointment = $this->appointmentService->createAppointment($request->all());

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
        return response()->json($this->appointmentService->getAppointmentForEdit($id));
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

        $status = $this->appointmentService->updateAppointment($id, $request->all());

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
        $success = $this->appointmentService->rescheduleAppointment($request->id, $request->all());

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
        $status = $this->appointmentService->deleteAppointment($id);

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
            $this->appointmentService->getDoctorTimeSlots($request->doctor_id, $request->date)
        );
    }
}
