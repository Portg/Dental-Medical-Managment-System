<?php

namespace App\Http\Controllers;

use App\Services\QuotationConversionReportService;
use Illuminate\Http\Request;

class QuotationConversionReportController extends Controller
{
    private QuotationConversionReportService $reportService;

    public function __construct(QuotationConversionReportService $reportService)
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

        return view('reports.quotation_conversion_report', $data);
    }
}
