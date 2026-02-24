<?php

namespace App\Http\Controllers;

use App\Services\DebtorsReportService;
use App\Exports\DebtorsExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\DataTables;

class DebtorsReportController extends Controller
{
    private DebtorsReportService $debtorsReportService;

    public function __construct(DebtorsReportService $debtorsReportService)
    {
        $this->debtorsReportService = $debtorsReportService;
        $this->middleware('can:view-reports');
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
            $data = $this->debtorsReportService->getDebtorsData(
                $request->input('start_date'),
                $request->input('end_date')
            );

            return Datatables::of($data)
                ->addIndexColumn()
                ->make(true);
        }

        return view('reports.debtors_report');
    }

    public function exportReport()
    {
        $data = $this->debtorsReportService->getDebtorsExportData();

        \App\OperationLog::log('export', '欠费报表', 'Debtor');
        \App\OperationLog::checkExportFrequency();

        return Excel::download(new DebtorsExport($data), 'debtors-report-' . date('Y-m-d') . '.xlsx');
    }
}
