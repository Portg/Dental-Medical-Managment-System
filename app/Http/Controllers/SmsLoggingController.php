<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Http\Helper\NameHelper;
use App\Services\SmsLoggingService;
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

            $data = $this->smsLoggingService->getList($request->only([
                'search', 'start_date', 'end_date',
            ]));

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('created_at', function ($row) {
                    return $row->created_at ? date('Y-m-d', strtotime($row->created_at)) : '-';
                })
                ->addColumn('message_receiver', function ($row) {
                    return NameHelper::join($row->surname, $row->othername);
                })
                ->addColumn('type', function ($row) {
                    $type = '';
                    if ($row->type == "Reminder") {
                        $type = '<span class="label label-sm label-danger">' . e($row->type) . '</span>';
                    } else {
                        $type = '<span class="label label-sm label-success">' . e($row->type) . '</span>';
                    }
                    return $type;
                })
                ->rawColumns(['type'])
                ->make(true);
        }
        return view('outbox_sms.index');
    }

    public function exportReport(Request $request)
    {
        $from = $request->session()->get('from') ?: null;
        $to = $request->session()->get('to') ?: null;

        $data = $this->smsLoggingService->getExportData($from, $to);

        \App\OperationLog::log('export', '短信管理', 'SmsLog');
        \App\OperationLog::checkExportFrequency();

        return Excel::download(new SmsLoggingExport($data), 'sms-logging-report-' . date('Y-m-d') . '.xlsx');
    }

}
