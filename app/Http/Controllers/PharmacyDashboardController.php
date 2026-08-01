<?php

namespace App\Http\Controllers;

use App\Services\PharmacyDashboardService;

class PharmacyDashboardController extends Controller
{
    private PharmacyDashboardService $service;

    public function __construct(PharmacyDashboardService $service)
    {
        $this->service = $service;
        // 该页展示低库存物品与待发处方，按库存权限保护（其中最敏感的一项）
        $this->middleware('can:manage-inventory');
    }

    public function index()
    {
        $data = $this->service->getDashboardData();
        return view('dashboards.pharmacy')->with($data);
    }
}
