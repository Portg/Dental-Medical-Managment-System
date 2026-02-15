<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Services\BudgetLineReportService;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Exports\BudgetLineExport;
use Maatwebsite\Excel\Facades\Excel;

class BudgetLineReportController extends Controller
{
    private BudgetLineReportService $service;

    public function __construct(BudgetLineReportService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
                FunctionsHelper::storeDateFilter($request);

                $data = $this->service->getBudgetLineData($request->start_date, $request->end_date);
            }

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('total_qty', function ($row) {
                    return $row->total_qty . '<span class="text-primary"> (Items)</span>';
                })
                ->addColumn('product_price', function ($row) {
                    return number_format($row->product_price);
                })
                ->rawColumns(['total_qty'])
                ->make(true);
        }
        return view('reports.budget_line_report.index');
    }


    public function exportBudgetLIneReport(Request $request)
    {
        $from = $request->session()->get('from');
        $to = $request->session()->get('to');

        $data = collect();
        if ($from != '' && $to != '') {
            $data = $this->service->getExportData($from, $to);
        }

        $sheet_title = "From " . date('d-m-Y', strtotime($from)) . " To " .
            date('d-m-Y', strtotime($to));

        return Excel::download(
            new BudgetLineExport($data, $sheet_title, $from, $to),
            'budget-lines-report-' . date('Y-m-d') . '.xlsx'
        );
    }
}
