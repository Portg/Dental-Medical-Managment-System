<?php

namespace App\Http\Controllers;

use App\Services\SuperAdminDashboardService;

class SuperAdminDashboardController extends Controller
{
    private SuperAdminDashboardService $service;

    public function __construct(SuperAdminDashboardService $service)
    {
        $this->service = $service;
        // 该页展示全院现金流、应收账款总额与月度营收/支出图表，按报表权限保护
        $this->middleware('can:view-reports');
    }

    public function index()
    {
        $data = $this->service->getDashboardData();
        return view('dashboards.superadmin')->with($data);
    }
}
