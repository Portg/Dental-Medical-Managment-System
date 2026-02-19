<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Services\AppointmentService;
use App\Exports\AppointmentExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $status = $this->appointmentService->updateAppointment($id, $request->only([
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
        $success = $this->appointmentService->rescheduleAppointment($request->id, $request->only([
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
