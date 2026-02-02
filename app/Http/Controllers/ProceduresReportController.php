<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use App\Exports\ProceduresExport;
use Maatwebsite\Excel\Facades\Excel;

class ProceduresReportController extends Controller
{

    /**
     * @param Request $request
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            if (!empty($_GET['start_date']) && !empty($_GET['end_date']) && !empty($_GET['search'])) {
                FunctionsHelper::storeDateFilter($request);
                //first get
                $data = DB::table('invoice_items')
                    ->join('medical_services', 'medical_services.id', 'invoice_items.medical_service_id')
                    ->whereNull('invoice_items.deleted_at')
                    ->where('medical_services.name', 'like', '%' . $request->get('search') . '%')
                    ->whereBetween(DB::raw('DATE_FORMAT(invoice_items.created_at, \'%Y-%m-%d\')'), array
                    ($request->start_date, $request->end_date))
                    ->select('medical_services.name', DB::raw('sum(invoice_items.amount*invoice_items.qty) as procedure_income'))
                    ->groupBy('invoice_items.medical_service_id')
                    ->orderBy('procedure_income', 'DESC')
                    ->get();
            } else if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
                FunctionsHelper::storeDateFilter($request);
                //first get
                $data = DB::table('invoice_items')
                    ->join('medical_services', 'medical_services.id', 'invoice_items.medical_service_id')
                    ->whereNull('invoice_items.deleted_at')
                    ->whereBetween(DB::raw('DATE_FORMAT(invoice_items.created_at, \'%Y-%m-%d\')'), array
                    ($request->start_date, $request->end_date))
                    ->select('medical_services.name', DB::raw('sum(invoice_items.amount*invoice_items.qty) as procedure_income'))
                    ->groupBy('invoice_items.medical_service_id')
                    ->orderBy('procedure_income', 'DESC')
                    ->get();
            }


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
        $data = collect();
        if ($request->session()->get('from') != '' && $request->session()->get('to') != '') {
            $data = DB::table('invoice_items')
                ->join('medical_services', 'medical_services.id', 'invoice_items.medical_service_id')
                ->whereNull('invoice_items.deleted_at')
                ->whereBetween(DB::raw('DATE_FORMAT(invoice_items.created_at, \'%Y-%m-%d\')'),
                    array($request->session()->get('from'),
                        $request->session()->get('to')))
                ->select('medical_services.name', DB::raw('sum(invoice_items.amount*invoice_items.qty) as procedure_income'))
                ->groupBy('invoice_items.medical_service_id')
                ->orderBy('procedure_income', 'DESC')
                ->get();
        }

        $sheet_title = "From " . date('d-m-Y', strtotime($request->session()->get('from'))) . " To " .
            date('d-m-Y', strtotime($request->session()->get('to')));

        return Excel::download(new ProceduresExport($data, $sheet_title), 'procedures-sales-report-' . date('Y-m-d') . '.xlsx');
    }
}
