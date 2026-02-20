<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Services\ProceduresReportService;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Exports\ProceduresExport;
use Maatwebsite\Excel\Facades\Excel;

class ProceduresReportController extends Controller
{
    private ProceduresReportService $proceduresReportService;

    public function __construct(ProceduresReportService $proceduresReportService)
    {
        $this->proceduresReportService = $proceduresReportService;
        $this->middleware('can:view-reports');
    }

    /**
     * @param Request $request
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            if (!empty($request->start_date) && !empty($request->end_date)) {
                FunctionsHelper::storeDateFilter($request);
            }

            $data = $this->proceduresReportService->getProceduresIncome(
                $request->input('start_date'),
                $request->input('end_date'),
                $request->input('search')
            );

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('procedure', function ($row) {
                    return $row->name;
                })
                ->addColumn('procedure_income', function ($row) {
                    return number_format($row->procedure_income);
                })
                ->make(true);
        }
        return view('reports.procedures_income_report');
    }

    public function downloadProcedureSalesReport(Request $request)
    {
        $from = $request->session()->get('from') ?: null;
        $to = $request->session()->get('to') ?: null;

        $data = $this->proceduresReportService->getExportData($from, $to);

        $sheet_title = "From " . date('d-m-Y', strtotime($from)) . " To " .
            date('d-m-Y', strtotime($to));

        return Excel::download(new ProceduresExport($data, $sheet_title), 'procedures-sales-report-' . date('Y-m-d') . '.xlsx');
    }
}
