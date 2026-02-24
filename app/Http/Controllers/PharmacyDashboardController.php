<?php

namespace App\Http\Controllers;

use App\Services\PharmacyDashboardService;

class PharmacyDashboardController extends Controller
{
    private PharmacyDashboardService $service;

    public function __construct(PharmacyDashboardService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $data = $this->service->getDashboardData();
        return view('dashboards.pharmacy')->with($data);
    }
}
