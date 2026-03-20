<?php

namespace App\Http\Controllers;

use App\PatientSource;
use App\PatientTag;
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
        $sourceId = $request->input('source_id') ? (int) $request->input('source_id') : null;
        $tagIds   = $request->input('tag_ids')
            ? array_map('intval', (array) $request->input('tag_ids'))
            : null;

        $data = $this->service->getReportData(
            $request->start_date,
            $request->end_date,
            $sourceId,
            $tagIds
        );

        $data['sources']          = PatientSource::where('is_active', true)->whereNull('deleted_at')->orderBy('name')->get();
        $data['patientTags']      = PatientTag::where('is_active', true)->whereNull('deleted_at')->orderBy('sort_order')->get();
        $data['selectedSourceId'] = $request->input('source_id');
        $data['selectedTagIds']   = $request->input('tag_ids', []);

        return view('reports.appointment_analytics_report', $data);
    }
}
