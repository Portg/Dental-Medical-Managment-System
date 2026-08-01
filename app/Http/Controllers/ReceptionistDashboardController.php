<?php

namespace App\Http\Controllers;

use App\Services\ReceptionistDashboardService;

class ReceptionistDashboardController extends Controller
{
    private ReceptionistDashboardService $service;

    public function __construct(ReceptionistDashboardService $service)
    {
        $this->service = $service;
        // 该页展示今日现金与应收账款总额，按账单权限保护
        $this->middleware('can:view-invoices');
    }

    public function index()
    {
        $data = $this->service->getDashboardData();
        return view('dashboards.receptionist')->with($data);
    }
}
