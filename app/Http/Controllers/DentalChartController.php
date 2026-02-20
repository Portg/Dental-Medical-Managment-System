<?php

namespace App\Http\Controllers;

use App\DentalChart;
use App\Services\DentalChartService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class DentalChartController extends Controller
{
    private DentalChartService $service;

    public function __construct(DentalChartService $service)
    {
        $this->service = $service;
        $this->middleware('can:edit-patients');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->service->getPatientChartList();

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('last_updated', function($row) {
                    return $row->last_updated ? date('Y-m-d H:i', strtotime($row->last_updated)) : '-';
                })
                ->addColumn('action', function($row) {
                    $latestAppointment = $this->service->getLatestAppointment($row->patient_id);

                    if ($latestAppointment) {
                        return '<a href="' . url('medical-treatment/' . $latestAppointment->id) . '" class="btn btn-sm btn-primary">
                            <i class="fa fa-eye"></i> ' . __('odontogram.view_chart') . '
                        </a>';
                    }
                    return '<span class="text-muted">' . __('odontogram.no_chart_data') . '</span>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('dental_chart.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param Request $request
     * @param $appointment_id
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, $appointment_id)
    {
        $data['patient'] = $this->service->getPatientForChart((int) $appointment_id);
        $data['appointment_id'] = $appointment_id;
        return view('dental_chart.create')->with($data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->service->replaceChartData((int) $request->appointment_id, $request->data);

        return response()->json(['message' => __('odontogram.chart_saved_success'), 'success' => true]);
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $data = $this->service->getChartByAppointment((int) $id);
        return response()->json($data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\DentalChart $dentalChart
     * @return \Illuminate\Http\Response
     */
    public function edit(DentalChart $dentalChart)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\DentalChart $dentalChart
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, DentalChart $dentalChart)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\DentalChart $dentalChart
     * @return \Illuminate\Http\Response
     */
    public function destroy(DentalChart $dentalChart)
    {
        //
    }
}
