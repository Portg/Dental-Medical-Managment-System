<?php

namespace App\Http\Controllers;

use App\InvoiceItem;
use App\Services\InsuranceReportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Yajra\DataTables\DataTables;

class InsuranceReportsController extends Controller
{
    private InsuranceReportService $insuranceReportService;

    public function __construct(InsuranceReportService $insuranceReportService)
    {
        $this->insuranceReportService = $insuranceReportService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->insuranceReportService->getInsurancePayments($request->all());

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('services_provided', function ($row) {
                    //now get the services provided
                    $invoice_items = InvoiceItem::where('invoice_id', $row->invoice_id)->get();
                    $item = '';
                    foreach ($invoice_items as $items) {
                        $item .= $items->medical_service->name . ",";
                    }
                    return $item;
                })
                ->addColumn('patient', function ($row) {
                    return \App\Http\Helper\NameHelper::join($row->surname, $row->othername);
                })
                ->addColumn('amount', function ($row) {
                    return number_format($row->amount);
                })
                ->addColumn('deleteBtn', function ($row) {

                    $btn = '<a href="#" onclick="deleteRecord(' . $row->id . ')" class="btn btn-danger">' . __('common.delete') . ' </a>';
                    return $btn;
                })
                ->rawColumns(['editBtn', 'deleteBtn'])
                ->make(true);
        }
        return view('insurance_report.index');
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
        //
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
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Claim the specified payment.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function claims(Request $request)
    {
        $status = $this->insuranceReportService->claimPayment($request->invoice_id);
        if ($status) {
            return response()->json(['message' => __('insurance_reports.data_loaded_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }
}
