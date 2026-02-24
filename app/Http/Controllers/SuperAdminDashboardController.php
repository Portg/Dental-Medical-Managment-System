<?php

namespace App\Http\Controllers;

use App\Services\SuperAdminDashboardService;

class SuperAdminDashboardController extends Controller
{
    private SuperAdminDashboardService $service;

    public function __construct(SuperAdminDashboardService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $data = $this->service->getDashboardData();
        return view('dashboards.superadmin')->with($data);
    }
}
