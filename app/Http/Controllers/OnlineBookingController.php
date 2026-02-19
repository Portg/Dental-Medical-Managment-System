<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Jobs\SendAppointmentSms;
use App\OnlineBooking;
use App\Services\OnlineBookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class OnlineBookingController extends Controller
{
    private OnlineBookingService $onlineBookingService;

    public function __construct(OnlineBookingService $onlineBookingService)
    {
        $this->onlineBookingService = $onlineBookingService;
        $this->middleware('can:view-appointments');
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function frontend()
    {
        $data['insurance_providers'] = $this->onlineBookingService->getInsuranceProviders();
        return view('frontend.index')->with($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return void
     * @throws \Exception
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            if (!empty($request->start_date) && !empty($request->end_date)) {
                FunctionsHelper::storeDateFilter($request);
            }

            $data = $this->onlineBookingService->getBookingList($request->only([
                'search', 'start_date', 'end_date',
            ]));

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('status', function ($row) {
                    if ($row->status == OnlineBooking::STATUS_REJECTED) {
                        $btn = '<span class="label label-sm label-danger"> ' . __('common.rejected') . ' </span>';
                    } else if ($row->status == OnlineBooking::STATUS_WAITING) {
                        $btn = '<span class="label label-sm label-info"> ' . __('common.waiting') . ' </span>';
                    } else {
                        $btn = '<span class="label label-sm label-success"> ' . __('common.approved') . ' </span>';
                    }
                    return $btn;
                })
                ->addColumn('action', function ($row) {
                    $btn = '
                      <div class="btn-group">
                        <button class="btn blue dropdown-toggle" type="button" data-toggle="dropdown"
                                aria-expanded="false"> ' . __('common.action') . '
                            <i class="fa fa-angle-down"></i>
                        </button>
                        <ul class="dropdown-menu" role="menu">
                            <li>
                                <a href="#" onclick="ViewMessage(' . $row->id . ')"> ' . __('online_bookings.view_message') . ' </a>
                            </li>
                        </ul>
                    </div>
                    ';
                    return $btn;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }
        return view('online_bookings.index');
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
        $honeypot = FALSE;
        if (!empty($request->contact_me_by_fax_only) && (bool)$request->contact_me_by_fax_only == TRUE) {
            $honeypot = TRUE;
            return $this->formResponse();
        }
        Validator::make($request->all(), [
            'full_name' => 'required',
            'phone_number' => 'required',
            'appointment_date' => 'required',
            'appointment_time' => 'required',
            'visit_history' => 'required',
            'visit_reason' => 'required'
        ])->validate();

        $success = $this->onlineBookingService->createBooking($request->only([
            'full_name', 'phone_number', 'email', 'appointment_date',
            'appointment_time', 'visit_history', 'visit_reason', 'insurance_provider',
        ]));
        if ($success) {
            return $this->formResponse();
        }
    }

    protected function formResponse()
    {
        return response()->json(['message' => __('online_bookings.appointment_submitted_success')]);
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return response()->json($this->onlineBookingService->getBookingDetail($id));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\OnlineBooking $onlineBooking
     * @return \Illuminate\Http\Response
     */
    public function edit($onlineBooking)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param $id
     * @return void
     */
    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'full_name' => 'required',
            'phone_number' => 'required',
            'appointment_date' => 'required',
            'appointment_time' => 'required',
            'doctor_id' => 'required'
        ])->validate();

        $result = $this->onlineBookingService->acceptBooking(
            $request->only([
                'full_name', 'phone_number', 'email', 'appointment_date',
                'appointment_time', 'doctor_id', 'insurance_company_id',
            ]),
            $id,
            Auth::User()->id,
            Auth::User()->branch_id
        );

        if ($result['success']) {
            if ($result['phone']) {
                $sendJob = new SendAppointmentSms($result['phone'], $result['message'], "Appointment");
                $this->dispatch($sendJob);
            }
            return FunctionsHelper::messageResponse(
                __('messages.booking_approved_successfully'),
                true
            );
        }
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return void
     */
    public function destroy($id)
    {
        $status = $this->onlineBookingService->rejectBooking($id);
        return FunctionsHelper::messageResponse(__('messages.booking_rejected_successfully'), $status);
    }
}
