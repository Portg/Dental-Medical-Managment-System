<?php

namespace App\Http\Controllers;

use App\Services\PatientDemographicsReportService;

class PatientDemographicsReportController extends Controller
{
    private PatientDemographicsReportService $reportService;

    public function __construct(PatientDemographicsReportService $reportService)
    {
        $this->reportService = $reportService;
        $this->middleware('can:view-reports');
    }

    public function index()
    {
        $data = $this->reportService->getReportData();

        return view('reports.patient_demographics_report', $data);
    }
}
