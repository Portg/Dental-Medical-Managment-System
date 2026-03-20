<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Services\InvoicingReportService;
use App\Services\ProceduresReportService;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Exports\InvoicingReportExport;
use App\Exports\ProceduresExport;
use Maatwebsite\Excel\Facades\Excel;

class BillingReportController extends Controller
{
    private InvoicingReportService $invoicingService;
    private ProceduresReportService $proceduresService;

    public function __construct(
        InvoicingReportService $invoicingService,
        ProceduresReportService $proceduresService
    ) {
        $this->invoicingService = $invoicingService;
        $this->proceduresService = $proceduresService;
        $this->middleware('can:view-reports');
    }

    public function index(Request $request)
    {
        $tab = $request->input('tab', 'payments');

        if ($request->ajax() && $tab === 'payments') {
            if (!empty($request->start_date) && !empty($request->end_date)) {
                FunctionsHelper::storeDateFilter($request);
            }
            $data = $this->invoicingService->getInvoicePayments([
                'search'             => $request->input('search.value', ''),
                'status'             => $request->input('status'),
                'start_date'         => $request->input('start_date'),
                'end_date'           => $request->input('end_date'),
                'insurance_provider' => $request->input('insurance_provider'),
                'payment_method'     => $request->input('payment_method'),
            ]);
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('patient', fn($row) => \App\Http\Helper\NameHelper::join($row->surname, $row->othername))
                ->addColumn('amount', fn($row) => number_format($row->amount))
                ->make(true);
        }

        if ($request->ajax() && $tab === 'procedures') {
            if (!empty($request->start_date) && !empty($request->end_date)) {
                FunctionsHelper::storeDateFilter($request);
            }
            $data = $this->proceduresService->getProceduresIncome(
                $request->input('start_date'),
                $request->input('end_date'),
                $request->input('search.value', '')
            );
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('procedure', fn($row) => $row->name)
                ->addColumn('procedure_income', fn($row) => number_format($row->procedure_income))
                ->make(true);
        }

        $data = [
            'insurance_providers' => $this->invoicingService->getInsuranceProviders(),
            'activeTab' => $tab,
        ];

        return view('reports.billing_report', $data);
    }

    public function exportPayments(Request $request)
    {
        $from = $request->session()->get('from') ?: null;
        $to = $request->session()->get('to') ?: null;
        $data = $this->invoicingService->getExportData($from, $to);
        $sheet_title = "From " . date('d-m-Y', strtotime($from)) . " To " . date('d-m-Y', strtotime($to));
        \App\OperationLog::log('export', '收费报表', 'InvoicePayment');
        \App\OperationLog::checkExportFrequency();
        return Excel::download(new InvoicingReportExport($data, $sheet_title), 'invoice-payments-report-' . date('Y-m-d') . '.xlsx');
    }

    public function exportProcedures(Request $request)
    {
        $from = $request->session()->get('from') ?: null;
        $to = $request->session()->get('to') ?: null;
        $data = $this->proceduresService->getExportData($from, $to);
        $sheet_title = "From " . date('d-m-Y', strtotime($from)) . " To " . date('d-m-Y', strtotime($to));
        return Excel::download(new ProceduresExport($data, $sheet_title), 'procedures-sales-report-' . date('Y-m-d') . '.xlsx');
    }
}
