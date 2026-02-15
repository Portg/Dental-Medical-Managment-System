<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Services\DoctorPerformanceReportService;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Exports\DoctorPerformanceExport;
use Maatwebsite\Excel\Facades\Excel;

class DoctorPerformanceReport extends Controller
{
    private DoctorPerformanceReportService $performanceService;

    public function __construct(DoctorPerformanceReportService $performanceService)
    {
        $this->performanceService = $performanceService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
                FunctionsHelper::storeDateFilter($request);
                //first get
                $data = $this->performanceService->getPerformanceData(
                    $request->doctor_id,
                    $request->start_date,
                    $request->end_date
                );
            }

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('patient', function ($row) {
                    return \App\Http\Helper\NameHelper::join($row->surname, $row->othername);
                })
                ->addColumn('done_procedures_amount', function ($row) {
                    return number_format($row->amount);
                })
                ->addColumn('invoice_amount', function ($row) {
                    $amount = $this->performanceService->totalInvoiceAmount($row->invoice_id);
                    return number_format($amount);
                })
                ->addColumn('paid_amount', function ($row) {
                    $paid = $this->performanceService->totalInvoicePaidAmount($row->invoice_id);
                    return number_format($paid);
                })
                ->addColumn('outstanding', function ($row) {
                    return number_format($this->performanceService->invoiceBalance($row->invoice_id));
                })
                ->rawColumns(['amount'])
                ->make(true);
        }
        $data['doctors'] = $this->performanceService->getDoctors();
        return view('reports.doctor_performance_report')->with($data);
    }


    public function downloadPerformanceReport(Request $request)
    {
        $from = $request->session()->get('from');
        $to = $request->session()->get('to');
        $doctorId = $request->session()->get('doctor_id');

        $queryBuilder = collect();
        if ($from != '' && $to != '' && $doctorId != '') {
            $queryBuilder = $this->performanceService->getExportData($doctorId, $from, $to);
        }

        $user = $this->performanceService->findDoctor($doctorId);
        $excel_file_name = \App\Http\Helper\NameHelper::join($user->surname, $user->othername) . '-performance-report-' . date('Y-m-d') . '.xlsx';
        $sheet_title = "From " . date('d-m-Y', strtotime($from)) . " To " .
            date('d-m-Y', strtotime($to));

        return Excel::download(new DoctorPerformanceExport($queryBuilder, $sheet_title), $excel_file_name);
    }
}
