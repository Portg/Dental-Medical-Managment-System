<?php

namespace App\Http\Controllers;

use App\Services\SelfAccountDepositService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class SelfAccountDepositController extends Controller
{
    private SelfAccountDepositService $selfAccountDepositService;

    public function __construct(SelfAccountDepositService $selfAccountDepositService)
    {
        $this->selfAccountDepositService = $selfAccountDepositService;
        $this->middleware('can:manage-accounting');
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param $self_account_id
     * @return Response
     * @throws \Exception
     */
    public function index(Request $request, $self_account_id)
    {
        if ($request->ajax()) {
            $data = $this->selfAccountDepositService->getList((int) $self_account_id);

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('amount', function ($row) {
                    return number_format($row->amount);
                })
                ->addColumn('editBtn', function ($row) {
                    return '<a href="#" onclick="editDeposit(' . $row->id . ')" class="btn btn-primary">' . __('common.edit') . '</a>';
                })
                ->addColumn('deleteBtn', function ($row) {
                    return '<a href="#" onclick="deleteDeposit(' . $row->id . ')" class="btn btn-danger">' . __('common.delete') . '</a>';
                })
                ->rawColumns(['editBtn', 'deleteBtn'])
                ->make(true);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'payment_date' => 'required',
            'amount' => 'required',
            'payment_method' => 'required',
        ])->validate();

        $status = $this->selfAccountDepositService->create($request->only(['payment_date', 'amount', 'payment_method']));

        if ($status) {
            return response()->json(['message' => __('deposits.deposit_success'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_try_again'), 'status' => false]);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return Response
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
        $record = $this->selfAccountDepositService->find((int) $id);
        return response()->json($record);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            'payment_date' => 'required',
            'amount' => 'required',
            'payment_method' => 'required',
        ])->validate();

        $status = $this->selfAccountDepositService->update((int) $id, $request->only(['payment_date', 'amount', 'payment_method']));

        if ($status) {
            return response()->json(['message' => __('messages.record_updated'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_try_again'), 'status' => false]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return Response
     */
    public function destroy($id)
    {
        $status = $this->selfAccountDepositService->delete((int) $id);
        if ($status) {
            return response()->json(['message' => __('messages.record_deleted'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_try_again'), 'status' => false]);
    }
}
