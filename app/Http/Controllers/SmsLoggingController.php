<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Http\Helper\NameHelper;
use App\Http\Helper\SmsLogger;
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
        // Outbox is operational (reminders log); allow clinic staff who view appointments,
        // not only roles with SMS credit/settings (manage-sms).
        $this->middleware('can:view-appointments');
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
                // status 此前直出原始字段，页面上就是一列英文 not_configured/pending。
                // 未配置服务商时用灰标，避免看起来像「排队中，稍后会发」。
                ->addColumn('status', function ($row) {
                    if ($row->status === SmsLogger::STATUS_NOT_CONFIGURED) {
                        return '<span class="label label-sm label-default">' . e(__('sms.not_configured')) . '</span>';
                    }

                    $label = __('sms.' . $row->status);
                    // 服务商回执里的未知状态原样显示，别把 key 名喷到页面上
                    if ($label === 'sms.' . $row->status) {
                        $label = $row->status;
                    }

                    return e($label);
                })
                ->rawColumns(['type', 'status'])
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
