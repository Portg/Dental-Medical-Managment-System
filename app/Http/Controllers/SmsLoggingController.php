<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Http\Helper\NameHelper;
use App\Services\SmsLoggingService;
use App\SmsLogging;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Exports\SmsLoggingExport;
use Maatwebsite\Excel\Facades\Excel;

class SmsLoggingController extends Controller
{
    private SmsLoggingService $smsLoggingService;

    public function __construct(SmsLoggingService $smsLoggingService)
    {
        $this->smsLoggingService = $smsLoggingService;
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

            $data = $this->smsLoggingService->getList($request->all());

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('message_receiver', function ($row) {
                    return NameHelper::join($row->surname, $row->othername);
                })
                ->addColumn('type', function ($row) {
                    $type = '';
                    if ($row->type == "Reminder") {
                        $type = '<span class="label label-sm label-danger">' . $row->type . '</span>';
                    } else {
                        $type = '<span class="label label-sm label-success">' . $row->type . '</span>';
                    }
                    return $type;
                })
                ->rawColumns(['message_receiver', 'type'])
                ->make(true);
        }
        return view('outbox_sms.index');
    }

    public function exportReport(Request $request)
    {
        $from = $request->session()->get('from') ?: null;
        $to = $request->session()->get('to') ?: null;

        $data = $this->smsLoggingService->getExportData($from, $to);

        return Excel::download(new SmsLoggingExport($data), 'sms-logging-report-' . date('Y-m-d') . '.xlsx');
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
     * @param \App\SmsLogging $smsLogging
     * @return \Illuminate\Http\Response
     */
    public function show(SmsLogging $smsLogging)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\SmsLogging $smsLogging
     * @return \Illuminate\Http\Response
     */
    public function edit(SmsLogging $smsLogging)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\SmsLogging $smsLogging
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SmsLogging $smsLogging)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\SmsLogging $smsLogging
     * @return \Illuminate\Http\Response
     */
    public function destroy(SmsLogging $smsLogging)
    {
        //
    }
}
