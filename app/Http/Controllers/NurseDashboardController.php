<?php

namespace App\Http\Controllers;

use App\Services\NurseDashboardService;

class NurseDashboardController extends Controller
{
    private NurseDashboardService $service;

    public function __construct(NurseDashboardService $service)
    {
        $this->service = $service;
        // 该页展示候诊、今日预约与逾期回访，按预约权限保护
        $this->middleware('can:view-appointments');
    }

    public function index()
    {
        $data = $this->service->getDashboardData();
        return view('dashboards.nurse')->with($data);
    }
}
