<?php

namespace App\Http\Controllers;

use App\Http\Helper\NameHelper;
use App\Services\SelfAccountBillPaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Yajra\DataTables\DataTables;

class SelfAccountBillPayment extends Controller
{
    private SelfAccountBillPaymentService $selfAccountBillPaymentService;

    public function __construct(SelfAccountBillPaymentService $selfAccountBillPaymentService)
    {
        $this->selfAccountBillPaymentService = $selfAccountBillPaymentService;
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
            $data = $this->selfAccountBillPaymentService->getList($self_account_id);

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('patient', function ($row) {
                    return NameHelper::join($row->surname, $row->othername);
                })
                ->addColumn('amount', function ($row) {
                    return number_format($row->amount);
                })
                ->rawColumns([])
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
     * @param \Illuminate\Http\Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
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
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}
