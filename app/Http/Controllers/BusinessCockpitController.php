<?php

namespace App\Http\Controllers;

use App\Services\BusinessCockpitService;

class BusinessCockpitController extends Controller
{
    private BusinessCockpitService $service;

    public function __construct(BusinessCockpitService $service)
    {
        $this->service = $service;
        $this->middleware('can:view-reports');
    }

    public function index()
    {
        $data = $this->service->getCockpitData();

        return view('reports.business_cockpit', $data);
    }
}
