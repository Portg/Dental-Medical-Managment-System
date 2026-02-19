<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Services\InvoicingReportService;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Exports\InvoicingReportExport;
use Maatwebsite\Excel\Facades\Excel;

class InvoicingReportsController extends Controller
{
    private InvoicingReportService $invoicingReportService;

    public function __construct(InvoicingReportService $invoicingReportService)
    {
        $this->invoicingReportService = $invoicingReportService;
        $this->middleware('can:view-reports');
    }

    public function invoicePaymentReport(Request $request)
    {
        if ($request->ajax()) {
            if (!empty($request->start_date) && !empty($request->end_date)) {
                FunctionsHelper::storeDateFilter($request);
            }

            $data = $this->invoicingReportService->getInvoicePayments([
                'search'             => $request->input('search.value', ''),
                'status'             => $request->input('status'),
                'start_date'         => $request->input('start_date'),
                'end_date'           => $request->input('end_date'),
                'insurance_provider' => $request->input('insurance_provider'),
                'payment_method'     => $request->input('payment_method'),
                'page'               => $request->input('page'),
                'per_page'           => $request->input('per_page'),
            ]);

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('patient', function ($row) {
                    return \App\Http\Helper\NameHelper::join($row->surname, $row->othername);
                })
                ->addColumn('amount', function ($row) {
                    return number_format($row->amount);
                })
                ->rawColumns(['amount'])
                ->make(true);
        }
        $data['insurance_providers'] = $this->invoicingReportService->getInsuranceProviders();
        return view('reports.invoice_payments_report')->with($data);
    }

    public function exportInvoicePayments(Request $request)
    {
        $from = $request->session()->get('from') ?: null;
        $to = $request->session()->get('to') ?: null;

        $data = $this->invoicingReportService->getExportData($from, $to);

        $sheet_title = "From " . date('d-m-Y', strtotime($from)) . " To " .
            date('d-m-Y', strtotime($to));

        return Excel::download(new InvoicingReportExport($data, $sheet_title), 'invoice-payments-report-' . date('Y-m-d') . '.xlsx');
    }

    public function todaysCash(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->invoicingReportService->getTodaysCash();

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('amount', function ($row) {
                    return number_format($row->amount);
                })
                ->rawColumns(['amount'])
                ->make(true);
        }
        return view('reports.daily_cash');
    }


    public function todaysExpenses(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->invoicingReportService->getTodaysExpenses();

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('amount', function ($row) {
                    return number_format($row->price * $row->qty);
                })
                ->rawColumns(['amount'])
                ->make(true);
        }
        return view('reports.daily_expenses');
    }

    public function todaysInsurance(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->invoicingReportService->getTodaysInsurance();

            return Datatables::of($data)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })
                ->addColumn('amount', function ($row) {
                    return number_format($row->amount);
                })
                ->rawColumns(['amount'])
                ->make(true);
        }
        return view('reports.daily_insurance');
    }
}
