<?php

namespace Modules\Doctor\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Http\Helper\NameHelper;
use App\Services\DoctorAppointmentService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class AppointmentsController extends Controller
{
    private DoctorAppointmentService $service;

    public function __construct(DoctorAppointmentService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            if (!empty($request->start_date) && !empty($request->end_date)) {
                FunctionsHelper::storeDateFilter($request);
            }

            $data = $this->service->getAppointmentList($request->all());

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('patient', function ($row) {
                    return NameHelper::join($row->surname, $row->othername);
                })
                ->addColumn('Medical_History', function ($row) {
                    $btn = '<a href="' . url('medical-history/' . $row->patient_id) . '"  class="btn btn-info">' . __('medical_treatment.medical_history') . '</a>';
                    return $btn;
                })
                ->addColumn('treatment', function ($row) {
                    $btn = '<a href="' . url('medical-treatment/' . $row->id) . '"  class="btn btn-info">' . __('medical_treatment.treatment') . '</a>';
                    return $btn;
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
        return view('doctor::appointments.index');
    }


    public function calendarEvents(Request $request)
    {
        $events = $this->service->getCalendarEvents($request->start, $request->end);

        return response()->json($events);
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        return view('doctor::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
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

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return view('doctor::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        $appointment = $this->service->getAppointmentForEdit((int) $id);
        return response()->json($appointment);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
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


    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        $status = $this->service->deleteAppointment((int) $id);

        if ($status) {
            return response()->json(['message' => __('messages.appointment_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_try_again'), 'status' => false]);
    }
}
