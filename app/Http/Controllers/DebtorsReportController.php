<?php

namespace App\Http\Controllers;

use App\Services\DebtorsReportService;
use App\Exports\DebtorsExport;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\DataTables;

class DebtorsReportController extends Controller
{
    private DebtorsReportService $debtorsReportService;

    public function __construct(DebtorsReportService $debtorsReportService)
    {
        $this->debtorsReportService = $debtorsReportService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->debtorsReportService->getDebtorsData();

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })->make(true);
        }

        return view('reports.debtors_report');
    }

    public function exportReport()
    {
        $data = $this->debtorsReportService->getDebtorsExportData();

        return Excel::download(new DebtorsExport($data), 'debtors-report-' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}
