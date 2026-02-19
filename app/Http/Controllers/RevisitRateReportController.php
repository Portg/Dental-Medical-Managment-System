<?php

namespace App\Http\Controllers;

use App\Services\RevisitRateReportService;
use Illuminate\Http\Request;

class RevisitRateReportController extends Controller
{
    private RevisitRateReportService $revisitRateReportService;

    public function __construct(RevisitRateReportService $revisitRateReportService)
    {
        $this->revisitRateReportService = $revisitRateReportService;
        $this->middleware('can:view-reports');
    }

    /**
     * Revisit rate statistics report.
     */
    public function index(Request $request)
    {
        $data = $this->revisitRateReportService->getReportData(
            $request->start_date,
            $request->end_date
        );

        return view('reports.revisit_rate_report', $data);
    }

    /**
     * Export report.
     */
    public function export(Request $request)
    {
        // TODO: Implement Excel export
    }
}
