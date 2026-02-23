<?php

namespace App\Http\Controllers;

use App\Services\MonthlyBusinessSummaryReportService;
use Illuminate\Http\Request;

class MonthlyBusinessSummaryReportController extends Controller
{
    private MonthlyBusinessSummaryReportService $reportService;

    public function __construct(MonthlyBusinessSummaryReportService $reportService)
    {
        $this->reportService = $reportService;
        $this->middleware('can:view-reports');
    }

    public function index(Request $request)
    {
        $data = $this->reportService->getReportData(
            $request->input('month')
        );

        return view('reports.monthly_business_summary_report', $data);
    }
}
