<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Http\Helper\NameHelper;
use App\Services\DoctorAppointmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class DoctorAppointmentController extends Controller
{
    private DoctorAppointmentService $service;

    public function __construct(DoctorAppointmentService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            if (!empty($request->start_date) && !empty($request->end_date)) {
                FunctionsHelper::storeDateFilter($request);
            }
            $data = $this->service->getAppointmentList($request->all());
            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {})
                ->addColumn('patient', function ($row) {
                    return NameHelper::join($row->surname, $row->othername);
                })
                ->addColumn('Medical_History', function ($row) {
                    return '<a href="' . url('medical-history/' . $row->patient_id) . '"  class="btn btn-info">' . __('medical_treatment.medical_history') . '</a>';
                })
                ->addColumn('treatment', function ($row) {
                    return '<a href="' . url('medical-treatment/' . $row->id) . '"  class="btn btn-info">' . __('medical_treatment.treatment') . '</a>';
                })
                ->addColumn('doctor_claim', function ($row) {
                    if (!$this->service->appointmentHasClaim($row->id)) {
                        return '<a href="#" onclick="CreateClaim(' . $row->id . ')"  class="btn green-meadow">' . __('doctor_claims.create_claim') . '</a>';
                    }
                    return '<span class="text-primary">' . __('doctor_claims.claim_already_generated') . '</span>';
                })
                ->rawColumns(['Medical_History', 'treatment', 'doctor_claim'])
                ->make(true);
        }
        return view('doctor_appointments.index');
    }

    public function calendarEvents(Request $request)
    {
        $events = $this->service->getCalendarEvents($request->start, $request->end);
        return response()->json($events);
    }

    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'patient_id' => 'required',
        ])->validate();
        $status = $this->service->createAppointment((int) $request->patient_id, $request->notes);
        if ($status) {
            return response()->json(['message' => __('messages.appointment_created_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_try_again'), 'status' => false]);
    }

    public function edit($id)
    {
        $appointment = $this->service->getAppointmentForEdit((int) $id);
        return response()->json($appointment);
    }

    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'patient_id' => 'required'
        ])->validate();
        $status = $this->service->updateAppointment((int) $id, (int) $request->patient_id, $request->notes);
        if ($status) {
            return response()->json(['message' => __('messages.appointment_updated_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_try_again'), 'status' => false]);
    }

    public function updateAppointmentStatus(Request $request)
    {
        Validator::make($request->all(), [
            'appointment_id' => 'required',
            'appointment_status' => 'required'
        ])->validate();
        $success = $this->service->updateStatus((int) $request->appointment_id, $request->appointment_status);
        return FunctionsHelper::messageResponse(__('messages.appointment_status_updated', ['status' => $request->appointment_status]), $success);
    }

    public function destroy($id)
    {
        $status = $this->service->deleteAppointment((int) $id);
        if ($status) {
            return response()->json(['message' => __('messages.appointment_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_try_again'), 'status' => false]);
    }
}
