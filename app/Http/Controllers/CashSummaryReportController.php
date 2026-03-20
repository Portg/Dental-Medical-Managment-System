<?php

namespace App\Http\Controllers;

use App\Services\CashSummaryReportService;
use App\Exports\CashSummaryExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class CashSummaryReportController extends Controller
{
    private CashSummaryReportService $service;

    public function __construct(CashSummaryReportService $service)
    {
        $this->service = $service;
        $this->middleware('can:view-reports');
    }

    public function index(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate   = $request->input('end_date', date('Y-m-d'));
        $tab       = $request->input('tab', 'payment_method');

        // AG-044: 日期范围最大 12 个月
        if (Carbon::parse($startDate)->diffInMonths(Carbon::parse($endDate)) > 12) {
            return back()->withErrors(['date_range' => __('report.date_range_too_large')]);
        }

        $data = $this->service->getData($tab, $startDate, $endDate);

        return view('reports.cash_summary_report', array_merge($data, [
            'startDate' => $startDate,
            'endDate'   => $endDate,
            'activeTab' => $tab,
        ]));
    }

    public function export(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate   = $request->input('end_date', date('Y-m-d'));
        $tab       = $request->input('tab', 'payment_method');

        $data = $this->service->getData($tab, $startDate, $endDate);

        \App\OperationLog::log('export', '现金汇总报表', 'CashSummary');
        \App\OperationLog::checkExportFrequency();

        return Excel::download(
            new CashSummaryExport($data, $tab, $startDate, $endDate),
            'cash-summary-report-' . date('Y-m-d') . '.xlsx'
        );
    }
}
