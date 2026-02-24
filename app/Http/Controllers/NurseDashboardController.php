<?php

namespace App\Http\Controllers;

use App\Services\NurseDashboardService;

class NurseDashboardController extends Controller
{
    private NurseDashboardService $service;

    public function __construct(NurseDashboardService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $data = $this->service->getDashboardData();
        return view('dashboards.nurse')->with($data);
    }
}
