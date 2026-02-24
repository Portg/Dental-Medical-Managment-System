<?php

namespace App\Http\Controllers;

use App\Services\ReceptionistDashboardService;

class ReceptionistDashboardController extends Controller
{
    private ReceptionistDashboardService $service;

    public function __construct(ReceptionistDashboardService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $data = $this->service->getDashboardData();
        return view('dashboards.receptionist')->with($data);
    }
}
