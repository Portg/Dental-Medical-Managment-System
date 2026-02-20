<?php

namespace App\Http\Controllers;

use App\Http\Helper\NameHelper;
use App\Services\DoctorClaimService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class DoctorClaimController extends Controller
{
    private DoctorClaimService $service;

    public function __construct(DoctorClaimService $service)
    {
        $this->service = $service;
        $this->middleware('can:manage-doctor-claims');
    }

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
                ->addColumn('claim_amount', function ($row) {
                    return number_format($row->claim_amount);
                })
                ->addColumn('insurance_amount', function ($row) {
                    return number_format($row->insurance_amount);
                })
                ->addColumn('cash_amount', function ($row) {
                    return number_format($row->cash_amount);
                })
                ->addColumn('total_claim_amount', function ($row) {
                    return '<span class="text-primary">' . number_format($this->service->getTotalClaims($row)) . '</span>';
                })
                ->addColumn('payment_balance', function ($row) {
                    $claims_amount = $this->service->getTotalClaims($row);
                    $remaining_balance = $this->service->getPaymentBalance($row->id, $claims_amount);
                    $action_balance = '';
                    if ($remaining_balance > 0) {
                        $action_balance = '<br>(<a href="#" class="text-danger" onclick="record_payment(' . (int) $row->id . ',' . (float) $remaining_balance . ')">' . __('doctor_claims.make_payment') . '</a>)';
                    }
                    return '<span class="text-primary">' . number_format($remaining_balance) . '</span>' . $action_balance;
                })
                ->addColumn('action', function ($row) {
                    $action = '';
                    $claim = '';
                    if ($row->status == "Pending") {
                        $claim = ' <a href="#" onclick="Approve_Claim(' . (int) $row->id . ',' . (float) $row->claim_amount .
                            ')"> ' . __('doctor_claims.approve_claim') . ' </a>';

                    } else {
                        $action = '
                             <li>
                                <a  href="#" onclick="editRecord(' . $row->id . ')"> ' . __('doctor_claims.edit_claim') . '  </a>
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
                           <li>
                               ' . $claim . '
                            </li>
                              ' . $action . '
                                <li>
                                <a  href="' . url('/claims-payment/' . $row->id) . '"> ' . __('doctor_claims.view_payment') . ' </a>
                            </li>
                        </ul>
                    </div>
                    ';
                    return $btn;
                })
                ->rawColumns(['total_claim_amount', 'payment_balance', 'action'])
                ->make(true);
        }
        return view('doctor_claims.index');
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
            'insurance_amount' => 'required',
            'cash_amount' => 'required'
        ])->validate();

        $status = $this->service->approveClaim((int) $request->id, $request->insurance_amount, $request->cash_amount);

        if ($status) {
            return response()->json(['message' => __('doctor_claims.claim_approved_successfully'), 'status' => true]);
        }
        return response()->json(['message' =>  __('messages.error_occurred_later'), 'status' => false]);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param $id
     * @return void
     */
    public function edit($id)
    {
        $claim = $this->service->getClaim((int) $id);
        return response()->json($claim);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'insurance_amount' => 'required',
            'cash_amount' => 'required'
        ])->validate();

        $status = $this->service->updateClaim((int) $id, $request->insurance_amount, $request->cash_amount);

        if ($status) {
            return response()->json(['message' => __('doctor_claims.claim_updated_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $status = $this->service->deleteClaim((int) $id);
        if ($status) {
            return response()->json(['message' => __('doctor_claims.claim_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }
}
