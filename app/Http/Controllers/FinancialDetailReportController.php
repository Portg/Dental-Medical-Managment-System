<?php

namespace App\Http\Controllers;

use App\Services\FinancialDetailReportService;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class FinancialDetailReportController extends Controller
{
    private FinancialDetailReportService $service;

    public function __construct(FinancialDetailReportService $service)
    {
        $this->service = $service;
        $this->middleware('can:view-reports');
    }

    public function index(Request $request)
    {
        $tab = $request->input('tab', 'payments');

        if ($request->ajax() && $tab === 'payments') {
            $data = $this->service->getPayments(
                $request->input('start_date'),
                $request->input('end_date'),
                $request->input('payment_type')
            );
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('patient', fn($r) => \App\Http\Helper\NameHelper::join($r->surname, $r->othername))
                ->addColumn('amount_fmt', fn($r) => number_format($r->amount, 2))
                ->make(true);
        }

        if ($request->ajax() && $tab === 'refunds') {
            $data = $this->service->getRefunds(
                $request->input('start_date'),
                $request->input('end_date')
            );
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('patient', fn($r) => \App\Http\Helper\NameHelper::join($r->surname, $r->othername))
                ->addColumn('amount_fmt', fn($r) => number_format($r->amount, 2))
                ->make(true);
        }

        if ($request->ajax() && $tab === 'expenses') {
            $data = $this->service->getExpenses(
                $request->input('start_date'),
                $request->input('end_date')
            );
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('amount_fmt', fn($r) => number_format($r->amount, 2))
                ->make(true);
        }

        if ($request->ajax() && $tab === 'employee') {
            $data = $this->service->getEmployeeBilling(
                $request->input('start_date'),
                $request->input('end_date'),
                $request->input('cashier_id') ? (int) $request->input('cashier_id') : null
            );
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('patient', fn($r) => \App\Http\Helper\NameHelper::join($r->surname, $r->othername))
                ->addColumn('amount_fmt', fn($r) => number_format($r->amount, 2))
                ->make(true);
        }

        $paymentTypes = $this->service->getPaymentTypes();
        $cashiers     = $this->service->getCashiers();

        return view('reports.financial_detail_report', [
            'activeTab'    => $tab,
            'paymentTypes' => $paymentTypes,
            'cashiers'     => $cashiers,
        ]);
    }
}
