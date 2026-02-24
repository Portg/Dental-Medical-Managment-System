<?php

namespace App\Http\Controllers;

use App\Services\DoctorDashboardService;

class DoctorDashboardController extends Controller
{
    private DoctorDashboardService $service;

    public function __construct(DoctorDashboardService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $data = $this->service->getDashboardData();
        return view('dashboards.doctor')->with($data);
    }
}
