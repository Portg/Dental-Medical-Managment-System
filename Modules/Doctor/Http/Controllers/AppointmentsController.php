<?php

namespace Modules\Doctor\Http\Controllers;

use App\Appointment;
use App\DoctorClaim;
use App\Http\Helper\FunctionsHelper;
use App\Http\Helper\NameHelper;
use App\Invoice;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class AppointmentsController extends Controller
{
    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {

            if (!empty($_GET['search'])) {
                $data = DB::table('appointments')
                    ->join('patients', 'patients.id', 'appointments.patient_id')
                    ->whereNull('appointments.deleted_at')
                    ->where('appointments.doctor_id', Auth::User()->id)
                    ->where(function($q) use ($request) {
                        NameHelper::addNameSearch($q, $request->get('search'), 'patients');
                    })
                    ->select('appointments.*', 'patients.surname', 'patients.othername')
                    ->orderBy('appointments.sort_by', 'desc')
                    ->get();
            } else if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
                FunctionsHelper::storeDateFilter($request);
                $data = DB::table('appointments')
                    ->join('patients', 'patients.id', 'appointments.patient_id')
                    ->whereNull('appointments.deleted_at')
                    ->where('appointments.doctor_id', Auth::User()->id)
                    ->whereBetween(DB::raw('DATE_FORMAT(appointments.sort_by, \'%Y-%m-%d\')'), array($request->start_date,
                        $request->end_date))
                    ->select('appointments.*', 'patients.surname', 'patients.othername', DB::raw('DATE_FORMAT(appointments.start_date, "%d-%b-%Y") as start_date'))
                    ->orderBy('appointments.sort_by', 'desc')
                    ->get();

            } else {
                $data = DB::table('appointments')
                    ->join('patients', 'patients.id', 'appointments.patient_id')
                    ->whereNull('appointments.deleted_at')
                    ->where('appointments.doctor_id', Auth::User()->id)
                    ->select('appointments.*', 'patients.surname', 'patients.othername')
                    ->orderBy('appointments.sort_by', 'desc')
                    ->get();
            }


            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('patient', function ($row) {
                    return \App\Http\Helper\NameHelper::join($row->surname, $row->othername);
                })
                ->addColumn('Medical_History', function ($row) {
                    //medical history goes with the patient ID
                    $btn = '<a href="' . url('medical-history/' . $row->patient_id) . '"  class="btn btn-info">' . __('medical_treatment.medical_history') . '</a>';
                    return $btn;
                })
                ->addColumn('treatment', function ($row) {
                    //use the appointment ID
                    $btn = '<a href="' . url('medical-treatment/' . $row->id) . '"  class="btn btn-info">' . __('medical_treatment.treatment') . '</a>';
                    return $btn;
                })
                ->addColumn('doctor_claim', function ($row) {
                    //check if the appointment already has a claim
                    $claim = DoctorClaim::where('appointment_id', $row->id)->first();
                    $btn = '';
                    if ($claim == "") {
                        $btn = '<a href="#" onclick="CreateClaim(' . $row->id . ')"  class="btn green-meadow">' . __('doctor_claims.create_claim') . '</a>';
                    } else {
                        $btn = '<span class="text-primary">' . __('doctor_claims.claim_already_generated') . '</span>';
                    }
                    return $btn;
                })
                ->
                rawColumns(['Medical_History', 'treatment', 'doctor_claim'])
                ->make(true);
        }
        return view('doctor::appointments.index');
    }


    public function calendarEvents(Request $request)
    {
        $query = DB::table('appointments')
            ->join('patients', 'patients.id', 'appointments.patient_id')
            ->whereNull('appointments.deleted_at')
            ->where('appointments.doctor_id', Auth::User()->id)
            ->select('appointments.*', 'patients.surname', 'patients.othername');

        if ($request->start && $request->end) {
            $query->whereBetween('appointments.sort_by', [$request->start, $request->end]);
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

        return response()->json($events);
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public
    function create()
    {
        return view('doctor::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public
    function store(Request $request)
    {

        Validator::make($request->all(), [
            'patient_id' => 'required',
        ])->validate();
        $status = Appointment::create([
            'appointment_no' => Appointment::AppointmentNo(),
            'patient_id' => $request->patient_id,
            'doctor_id' => Auth::User()->id,
            'notes' => $request->notes,
            '_who_added' => Auth::User()->id
        ]);
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
    public
    function show($id)
    {
        return view('doctor::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
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
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public
    function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'patient_id' => 'required'
        ])->validate();
        $status = Appointment::where('id', $id)->update([
            'patient_id' => $request->patient_id,
            'notes' => $request->notes,
            '_who_added' => Auth::User()->id
        ]);
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
        //now update the appointment as done
        $success = Appointment::where('id', $request->appointment_id)->update(['status' => $request->appointment_status]);
        return FunctionsHelper::messageResponse(__('messages.appointment_status_updated', ['status' => $request->appointment_status]), $success);
    }


    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public
    function destroy($id)
    {
        $status = Appointment::where('id', $id)->delete();
        if ($status) {
            return response()->json(['message' => __('messages.appointment_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_try_again'), 'status' => false]);


    }
}
