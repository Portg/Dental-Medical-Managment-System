<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Services\SmsTransactionService;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class SmsTransactionController extends Controller
{
    private SmsTransactionService $smsTransactionService;

    public function __construct(SmsTransactionService $smsTransactionService)
    {
        $this->smsTransactionService = $smsTransactionService;
        $this->middleware('can:manage-sms');
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
            if (!empty($request->start_date) && !empty($request->end_date)) {
                FunctionsHelper::storeDateFilter($request);
            }

            $data = $this->smsTransactionService->getList($request->only(['start_date', 'end_date']));

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('created_at', function ($row) {
                    return $row->created_at ? date('Y-m-d', strtotime($row->created_at)) : '-';
                })
                ->addColumn('amount', function ($row) {
                    return number_format($row->amount);
                })
                ->addColumn('addedBy', function ($row) {
                    return $row->othername;
                })
                ->rawColumns([])
                ->make(true);
        }

        $current_balance = $this->smsTransactionService->getCurrentBalance();
        $data['current_balance'] = number_format($current_balance);
        return view('sms_transactions.index')->with($data);
    }

}
