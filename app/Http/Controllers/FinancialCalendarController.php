<?php

namespace App\Http\Controllers;

use App\Services\FinancialCalendarService;
use Illuminate\Http\Request;

class FinancialCalendarController extends Controller
{
    private FinancialCalendarService $calendarService;

    public function __construct(FinancialCalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
        $this->middleware('can:view-reports');
    }

    public function index()
    {
        return view('reports.financial_calendar');
    }

    /**
     * Return FullCalendar-compatible events for the given month.
     * ?year=2026&month=3
     */
    public function getData(Request $request)
    {
        $year  = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $events = $this->calendarService->getMonthEvents($year, $month);

        return response()->json($events);
    }
}
