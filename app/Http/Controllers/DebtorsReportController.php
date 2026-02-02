<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Yajra\DataTables\DataTables;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use App\Exports\DebtorsExport;
use Maatwebsite\Excel\Facades\Excel;

class DebtorsReportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Application|Factory|Response|View
     * @throws \Exception
     */
    public function index(Request $request)
    {
        $output_array = [];
        if ($request->ajax()) {
            $data = DB::table('invoice_items')
                ->whereNull('invoice_items.deleted_at')
                ->select('invoice_items.invoice_id', DB::raw('sum(invoice_items.amount*invoice_items.qty) as invoice_amount'))
                ->groupBy('invoice_items.invoice_id')
                ->get();
            foreach ($data as $item) {
                $payment_info = DB::table('invoice_payments')
                    ->whereNull('deleted_at')
                    ->where('invoice_id', $item->invoice_id)
                    ->select(DB::raw('sum(amount) as amount_paid'))
                    ->first();

                $invoice_info = DB::table('invoices') //patient & invoice Info
                ->leftJoin('appointments', 'appointments.id', 'invoices.appointment_id')
                    ->leftJoin('patients', 'patients.id', 'appointments.patient_id')
                    ->where('invoices.id', $item->invoice_id)
                    ->select('patients.*', 'invoices.invoice_no', DB::raw('DATE_FORMAT(invoices.created_at, "%d-%b-%Y") as invoice_date'))
                    ->first();
                $outstanding_balance = $item->invoice_amount - $payment_info->amount_paid;
                if ($outstanding_balance > 0) {
                    $output_array[] = array(
                        'invoice_date' => $invoice_info->invoice_date,
                        'invoice_no' => $invoice_info->invoice_no,
                        'surname' => $invoice_info->surname,
                        'othername' => $invoice_info->othername,
                        'phone_no' => $invoice_info->phone_no,
                        'invoice_amount' => $item->invoice_amount,
                        'amount_paid' => $payment_info->amount_paid == null ? 0 : $payment_info->amount_paid,
                        'outstanding_balance' => $outstanding_balance,
                    );
                }

            }
            return Datatables::of($output_array)
                ->addIndexColumn()
                ->filter(function ($instance) use ($request) {
                })->make(true);
        }

        return view('reports.debtors_report');
    }

    public function exportReport()
    {
        $output_array = [];
        $data = DB::table('invoice_items')
            ->whereNull('invoice_items.deleted_at')
            ->select('invoice_items.invoice_id', DB::raw('sum(invoice_items.amount*invoice_items.qty) as invoice_amount'))
            ->groupBy('invoice_items.invoice_id')
            ->get();
        foreach ($data as $item) {
            $payment_info = DB::table('invoice_payments')
                ->whereNull('deleted_at')
                ->where('invoice_id', $item->invoice_id)
                ->select(DB::raw('sum(amount) as amount_paid'))
                ->first();

            $invoice_info = DB::table('invoices')
                ->leftJoin('appointments', 'appointments.id', 'invoices.appointment_id')
                ->leftJoin('patients', 'patients.id', 'appointments.patient_id')
                ->leftJoin('insurance_companies', 'insurance_companies.id', 'patients.insurance_company_id')
                ->where('invoices.id', $item->invoice_id)
                ->select('patients.*', 'insurance_companies.name as insurance_company', 'invoices.invoice_no', DB::raw('DATE_FORMAT(invoices.created_at, "%d-%b-%Y") as invoice_date'))
                ->first();
            $outstanding_balance = $item->invoice_amount - $payment_info->amount_paid;
            if ($outstanding_balance > 0) {
                $output_array[] = array(
                    'invoice_date' => $invoice_info->invoice_date,
                    'invoice_no' => $invoice_info->invoice_no,
                    'surname' => $invoice_info->surname,
                    'othername' => $invoice_info->othername,
                    'insurance_company' => $invoice_info->insurance_company,
                    'phone_no' => $invoice_info->phone_no,
                    'invoice_amount' => $item->invoice_amount,
                    'amount_paid' => $payment_info->amount_paid == null ? 0 : $payment_info->amount_paid,
                    'outstanding_balance' => $outstanding_balance,
                );
            }
        }

        return Excel::download(new DebtorsExport($output_array), 'debtors-report-' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}
