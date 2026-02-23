<?php

namespace App\Http\Controllers;

use App\Services\AppointmentAnalyticsReportService;
use Illuminate\Http\Request;

class AppointmentAnalyticsReportController extends Controller
{
    private AppointmentAnalyticsReportService $service;

    public function __construct(AppointmentAnalyticsReportService $service)
    {
        $this->service = $service;
        $this->middleware('can:view-reports');
    }

    public function index(Request $request)
    {
        $data = $this->service->getReportData(
            $request->start_date,
            $request->end_date
        );

        return view('reports.appointment_analytics_report', $data);
    }
}
