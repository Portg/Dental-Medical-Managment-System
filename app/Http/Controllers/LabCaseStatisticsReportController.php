<?php

namespace App\Http\Controllers;

use App\Services\LabCaseStatisticsReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LabCaseStatisticsReportController extends Controller
{
    private LabCaseStatisticsReportService $service;

    public function __construct(LabCaseStatisticsReportService $service)
    {
        $this->service = $service;
        $this->middleware('can:view-reports');
    }

    public function index(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate   = $request->input('end_date', date('Y-m-d'));

        // AG-044: 日期范围最大 12 个月
        if (Carbon::parse($startDate)->diffInMonths(Carbon::parse($endDate)) > 12) {
            return back()->withErrors(['date_range' => __('report.date_range_too_large')]);
        }

        $data = $this->service->getReportData($startDate, $endDate);

        return view('reports.lab_case_statistics_report', array_merge($data, [
            'startDate' => $startDate,
            'endDate'   => $endDate,
        ]));
    }
}
