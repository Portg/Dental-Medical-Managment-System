<?php

namespace App\Http\Controllers;

use App\Services\DoctorWorkloadReportService;
use Illuminate\Http\Request;

class DoctorWorkloadReportController extends Controller
{
    private DoctorWorkloadReportService $reportService;

    public function __construct(DoctorWorkloadReportService $reportService)
    {
        $this->reportService = $reportService;
        $this->middleware('can:view-reports');
    }

    public function index(Request $request)
    {
        $data = $this->reportService->getReportData(
            $request->input('start_date'),
            $request->input('end_date')
        );

        return view('reports.doctor_workload_report', $data);
    }
}
