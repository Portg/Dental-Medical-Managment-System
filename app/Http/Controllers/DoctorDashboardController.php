<?php

namespace App\Http\Controllers;

use App\Services\DoctorDashboardService;

class DoctorDashboardController extends Controller
{
    private DoctorDashboardService $service;

    public function __construct(DoctorDashboardService $service)
    {
        $this->service = $service;
        // 该页数据已按 Auth::User()->id 限定本人，按预约权限保护即可
        $this->middleware('can:view-appointments');
    }

    public function index()
    {
        $data = $this->service->getDashboardData();
        return view('dashboards.doctor')->with($data);
    }
}
