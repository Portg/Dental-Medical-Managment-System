<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\InsuranceCompany;
use App\InvoicePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
use App\Exports\InvoicingReportExport;
use Maatwebsite\Excel\Facades\Excel;

class InvoicingReportsController extends Controller
{
    protected $payment_methods = [];


    public function invoicePaymentReport(Request $request)
    {
        if ($request->ajax()) {
            if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
                FunctionsHelper::storeDateFilter($request);

                $data = DB::table('invoice_payments')
                    ->leftJoin('invoices', 'invoices.id', 'invoice_payments.invoice_id')
                    ->leftJoin('appointments', 'appointments.id', 'invoices.appointment_id')
                    ->leftJoin('patients', 'patients.id', 'appointments.patient_id')
                    ->whereNull('invoice_payments.deleted_at')
                    ->whereBetween(DB::raw('DATE_FORMAT(invoice_payments.payment_date, \'%Y-%m-%d\')'), array
                    ($request->start_date, $request->end_date))
                    ->select('invoice_payments.*', DB::raw('DATE_FORMAT(invoice_payments.payment_date, "%d-%b-%Y") as payment_date'), 'patients.surname', 'patients.othername')
                    ->get();
            } else {
                $data = DB::table('invoice_payments')
                    ->leftJoin('invoices', 'invoices.id', 'invoice_payments.invoice_id')
                    ->leftJoin('appointments', 'appointments.id', 'invoices.appointment_id')
                    ->leftJoin('patients', 'patients.id', 'appointments.patient_id')
                    ->whereNull('invoice_payments.deleted_at')
                    ->select('invoice_payments.*', DB::raw('DATE_FORMAT(invoice_payments.payment_date, "%d-%b-%Y") as payment_date'), 'patients.surname', 'patients.othername')
                    ->get();
            }

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
        $data['insurance_providers'] = InsuranceCompany::Orderby('id', 'DESC')->get();
        return view('reports.invoice_payments_report')->with($data);
    }

    public function exportInvoicePayments(Request $request)
    {
        $data = DB::table('invoice_payments')
            ->leftJoin('insurance_companies', 'insurance_companies.id', 'invoice_payments.insurance_company_id')
            ->leftJoin('invoices', 'invoices.id', 'invoice_payments.invoice_id')
            ->leftJoin('appointments', 'appointments.id', 'invoices.appointment_id')
            ->leftJoin('patients', 'patients.id', 'appointments.patient_id')
            ->whereNull('invoice_payments.deleted_at')
            ->whereBetween(DB::raw('DATE_FORMAT(invoice_payments.payment_date, \'%Y-%m-%d\')'), array
            ($request->session()->get('from'), $request->session()->get('to')))
            ->select('invoice_payments.*', 'invoices.invoice_no', DB::raw('DATE_FORMAT(invoice_payments.payment_date, "%d-%b-%Y") as payment_date'), 'patients.surname', 'patients.othername', 'insurance_companies.name as insurance')
            ->get();

        $sheet_title = "From " . date('d-m-Y', strtotime($request->session()->get('from'))) . " To " .
            date('d-m-Y', strtotime($request->session()->get('to')));

        return Excel::download(new InvoicingReportExport($data, $sheet_title), 'invoice-payments-report-' . date('Y-m-d') . '.xlsx');
    }

    public function todaysCash(Request $request)
    {

        if ($request->ajax()) {

            $data = DB::table('invoice_payments')
                ->leftJoin('invoices', 'invoices.id', 'invoice_payments.invoice_id')
                ->leftJoin('appointments', 'appointments.id', 'invoices.appointment_id')
                ->leftJoin('patients', 'patients.id', 'appointments.patient_id')
                ->leftJoin('users', 'users.id', 'invoice_payments._who_added')
                ->whereNull('invoice_payments.deleted_at')
                ->where('payment_method', 'Cash')
                ->whereDate('payment_date', date('Y-m-d'))
                ->select('invoice_payments.*', 'patients.surname', 'patients.othername',
                    DB::raw('TIME(invoice_payments.updated_at) AS created_date'), 'users.othername as added_by')
                ->get();

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

            $data = DB::table('expense_items')
                ->leftJoin('users', 'users.id', 'expense_items._who_added')
                ->whereNull('expense_items.deleted_at')
                ->whereDate('expense_items.updated_at', date('Y-m-d'))
                ->select('expense_items.*', DB::raw('TIME(expense_items.updated_at) AS created_date'),
                    'users.othername as added_by')
                ->get();

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

            $data = DB::table('invoice_payments')
                ->leftJoin('invoices', 'invoices.id', 'invoice_payments.invoice_id')
                ->leftJoin('appointments', 'appointments.id', 'invoices.appointment_id')
                ->leftJoin('patients', 'patients.id', 'appointments.patient_id')
                ->leftJoin('users', 'users.id', 'invoice_payments._who_added')
                ->whereNull('invoice_payments.deleted_at')
                ->where('payment_method', 'Insurance')
                ->whereDate('payment_date', date('Y-m-d'))
                ->select('invoice_payments.*', 'patients.surname', 'patients.othername',
                    DB::raw('TIME(invoice_payments.updated_at) AS created_date'), 'users.othername as added_by')
                ->get();

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
