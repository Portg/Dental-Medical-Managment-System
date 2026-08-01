<?php

namespace App\Http\Controllers;

use App\Exports\RevisitRateExport;
use App\Services\RevisitRateReportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

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
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate   = $request->input('end_date', date('Y-m-d'));

        $data = $this->revisitRateReportService->getReportData($startDate, $endDate);

        \App\OperationLog::log('export', '回访报表', 'RevisitRate');
        \App\OperationLog::checkExportFrequency();

        return Excel::download(
            new RevisitRateExport($data, $startDate, $endDate),
            'revisit-rate-report-' . date('Y-m-d') . '.xlsx'
        );
    }
}
