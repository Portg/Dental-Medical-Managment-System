<?php

namespace App\Http\Controllers;

use App\Services\DoctorClaimPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class DoctorClaimPaymentController extends Controller
{
    private DoctorClaimPaymentService $claimPaymentService;

    public function __construct(DoctorClaimPaymentService $claimPaymentService)
    {
        $this->claimPaymentService = $claimPaymentService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param $claim_id
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function index(Request $request, $claim_id)
    {
        if ($request->ajax()) {

            $data = $this->claimPaymentService->getPaymentsByClaim($claim_id);

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('editBtn', function ($row) {
                    $btn = '<a href="#" onclick="editRecord(' . $row->id . ')" class="btn btn-primary">' . __('common.edit') . '</a>';
                    return $btn;
                })
                ->addColumn('deleteBtn', function ($row) {

                    $btn = '<a href="#" onclick="deleteRecord(' . $row->id . ')" class="btn btn-danger">' . __('common.delete') . '</a>';
                    return $btn;
                })
                ->rawColumns(['editBtn', 'deleteBtn'])
                ->make(true);
        }
        $data['claim_id'] = $claim_id;
        $data['doctor'] = $this->claimPaymentService->getDoctorForClaim($claim_id);
        return view('doctor_claims.payments.index')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return void
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
            'amount' => 'required',
            'payment_date' => 'required'
        ])->validate();

        $status = $this->claimPaymentService->createPayment(
            $request->payment_date,
            $request->amount,
            $request->claim_id,
            Auth::User()->id
        );

        if ($status) {
            return response()->json(['message' => __('doctor_claims.payments.payment_added_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
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
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        return response()->json($this->claimPaymentService->findPayment($id));
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
            'amount' => 'required',
            'payment_date' => 'required'
        ])->validate();

        $status = $this->claimPaymentService->updatePayment(
            $id,
            $request->payment_date,
            $request->amount,
            Auth::User()->id
        );

        if ($status) {
            return response()->json(['message' => __('doctor_claims.payments.payment_updated_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $status = $this->claimPaymentService->deletePayment($id);
        if ($status) {
            return response()->json(['message' => __('doctor_claims.payments.payment_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);

    }
}
