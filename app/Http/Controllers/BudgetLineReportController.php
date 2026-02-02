<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use App\Exports\BudgetLineExport;
use Maatwebsite\Excel\Facades\Excel;

class BudgetLineReportController extends Controller
{
    protected $budget_line_arry;

    /**
     * BudgetLineReportController constructor.
     */
    public function __construct()
    {
        $this->budget_line_arry = [];
    }

    public function index(Request $request)
    {

        if ($request->ajax()) {
            if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
                FunctionsHelper::storeDateFilter($request);

                $data = DB::table('expense_items')
                    ->join('expense_categories', 'expense_categories.id', 'expense_items.expense_category_id')
                    ->join('chart_of_account_items', 'chart_of_account_items.id', 'expense_categories.chart_of_account_item_id')
                    ->whereNull('expense_items.deleted_at')
                    ->whereBetween(DB::raw('DATE_FORMAT(expense_items.created_at, \'%Y-%m-%d\')'), array
                    ($request->start_date, $request->end_date))
                    ->select('expense_items.*', DB::raw('sum(price*qty) as product_price'),
                        DB::raw('sum(qty) as total_qty'), 'chart_of_account_items.name as budget_line')
                    ->groupBy('expense_categories.chart_of_account_item_id')
                    ->get();
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
        $data = collect();
        if ($request->session()->get('from') != '' && $request->session()->get('to') != '') {
            $data = DB::table('expense_items')
                ->join('expense_categories', 'expense_categories.id', 'expense_items.expense_category_id')
                ->join('chart_of_account_items', 'chart_of_account_items.id', 'expense_categories.chart_of_account_item_id')
                ->whereNull('expense_items.deleted_at')
                ->whereBetween(DB::raw('DATE_FORMAT(expense_items.created_at, \'%Y-%m-%d\')'), array
                ($request->session()->get('from'),
                    $request->session()->get('to')))
                ->select('expense_items.*', DB::raw('sum(price*qty) as product_price'),
                    DB::raw('sum(qty) as total_qty'), 'chart_of_account_items.name as budget_line',
                    'expense_categories.chart_of_account_item_id')
                ->groupBy('expense_categories.chart_of_account_item_id')
                ->get();
        }

        $sheet_title = "From " . date('d-m-Y', strtotime($request->session()->get('from'))) . " To " .
            date('d-m-Y', strtotime($request->session()->get('to')));

        return Excel::download(
            new BudgetLineExport($data, $sheet_title, $request->session()->get('from'), $request->session()->get('to')),
            'budget-lines-report-' . date('Y-m-d') . '.xlsx'
        );
    }
}
