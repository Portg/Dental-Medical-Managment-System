<?php

namespace App\Http\Controllers;

use App\Services\PatientSourceReportService;
use Illuminate\Http\Request;

class PatientSourceReportController extends Controller
{
    private PatientSourceReportService $patientSourceReportService;

    public function __construct(PatientSourceReportService $patientSourceReportService)
    {
        $this->patientSourceReportService = $patientSourceReportService;
        $this->middleware('can:view-reports');
    }

    /**
     * 患者来源分析报表
     */
    public function index(Request $request)
    {
        $reportData = $this->patientSourceReportService->getReportData(
            $request->start_date,
            $request->end_date
        );

        return view('reports.patient_source_report', $reportData);
    }

    /**
     * 导出报表
     */
    public function export(Request $request)
    {
        \App\OperationLog::log('export', '来源报表', 'PatientSource');
        \App\OperationLog::checkExportFrequency();

        // TODO: Implement Excel export
    }
}
