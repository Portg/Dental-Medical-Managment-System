<?php

namespace Modules\Doctor\Http\Controllers;

use App\DoctorClaim;
use App\Http\Helper\NameHelper;
use App\Services\DoctorModuleClaimService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class DoctorClaimController extends Controller
{
    private DoctorModuleClaimService $service;

    public function __construct(DoctorModuleClaimService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->service->getClaimsList();

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('created_at', function ($row) {
                    return $row->created_at ? date('Y-m-d', strtotime($row->created_at)) : '-';
                })
                ->addColumn('patient', function ($row) {
                    return NameHelper::join($row->surname, $row->othername);
                })
                ->addColumn('insurance_amount', function ($row) {
                    return $this->service->calculateInsuranceClaim($row->insurance_amount);
                })
                ->addColumn('cash_amount', function ($row) {
                    return $this->service->calculateCashClaim($row->cash_amount);
                })
                ->addColumn('total_claim_amount', function ($row) {
                    return number_format($this->service->calculateTotalClaim($row->insurance_amount, $row->cash_amount));
                })
                ->addColumn('action', function ($row) {
                    $action_btn='';
                    if ($row->status == DoctorClaim::STATUS_PENDING) {
                        $action_btn = '
                       <li>
                                <a href="#" onclick="editRecord(' . $row->id . ')"> ' . __('common.edit') . '</a>
                            </li>
                             <li>
                                <a  href="#" onclick="deleteRecord(' . $row->id . ')"  >' . __('common.delete') . '</a>
                            </li>
                    ';
                    }

                    $btn = '
                      <div class="btn-group">
                        <button class="btn blue dropdown-toggle" type="button" data-toggle="dropdown"
                                aria-expanded="false"> ' . __('common.action') . '
                            <i class="fa fa-angle-down"></i>
                        </button>
                        <ul class="dropdown-menu" role="menu">
                             ' . $action_btn . '
                        </ul>
                    </div>
                    ';
                    return $btn;
                })
                ->rawColumns(['amount', 'action'])
                ->make(true);
        }
        return view('doctor::claims.index');
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
            'appointment_id' => 'required',
            'amount' => 'required'
        ])->validate();

        $claim = $this->service->createClaim($request->appointment_id, $request->amount);

        if ($claim === null) {
            return response()->json(['message' => __('doctor_claims.no_claim_rate_in_system'), 'status' => false]);
        }
        return response()->json(['message' => __('doctor_claims.claim_submitted_successfully'), 'status' => true]);
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
        $claim = $this->service->getClaimForEdit($id);
        return response()->json($claim);
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
            'amount' => 'required'
        ])->validate();

        $status = $this->service->updateClaim($id, $request->amount);

        if ($status) {
            return response()->json(['message' => __('doctor_claims.claim_updated_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_try_again'), 'status' => false]);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        $status = $this->service->deleteClaim($id);

        if ($status) {
            return response()->json(['message' => __('doctor_claims.claim_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_try_again'), 'status' => false]);
    }
}
