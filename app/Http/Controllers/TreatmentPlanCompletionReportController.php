<?php

namespace App\Http\Controllers;

use App\Services\TreatmentPlanCompletionReportService;
use Illuminate\Http\Request;

class TreatmentPlanCompletionReportController extends Controller
{
    private TreatmentPlanCompletionReportService $reportService;

    public function __construct(TreatmentPlanCompletionReportService $reportService)
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

        return view('reports.treatment_plan_completion_report', $data);
    }
}
