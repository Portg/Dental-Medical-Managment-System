<?php

namespace App\Http\Controllers;

use App\Services\PatientSourceReportService;
use App\Services\PatientDemographicsReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PatientReportController extends Controller
{
    private PatientSourceReportService $sourceService;
    private PatientDemographicsReportService $demographicsService;

    public function __construct(
        PatientSourceReportService $sourceService,
        PatientDemographicsReportService $demographicsService
    ) {
        $this->sourceService = $sourceService;
        $this->demographicsService = $demographicsService;
        $this->middleware('can:view-reports');
    }

    public function index(Request $request)
    {
        $tab       = $request->input('tab', 'source');
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        // AG-044: 日期范围最大 12 个月
        if ($startDate && $endDate) {
            if (Carbon::parse($startDate)->diffInMonths(Carbon::parse($endDate)) > 12) {
                return back()->withErrors(['date_range' => __('report.date_range_too_large')]);
            }
        }

        $sourceData = $this->sourceService->getReportData($startDate, $endDate);

        $demographicsData = [];
        if ($tab === 'demographics') {
            $demographicsData = $this->demographicsService->getReportData();
        }

        $data = array_merge($sourceData, $demographicsData, ['activeTab' => $tab]);

        return view('reports.patient_report', $data);
    }
}
