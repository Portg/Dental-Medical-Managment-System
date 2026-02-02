<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\InsuranceCompany;
use App\InvoiceItem;
use App\InvoicePayment;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use App\Exports\DoctorPerformanceExport;
use Maatwebsite\Excel\Facades\Excel;

class DoctorPerformanceReport extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            if (!empty($_GET['start_date']) && !empty($_GET['end_date'])) {
                FunctionsHelper::storeDateFilter($request);
                //first get
                $data = DB::table('invoice_items')
                    ->join('invoices', 'invoices.id', 'invoice_items.invoice_id')
                    ->join('appointments', 'appointments.id', 'invoices.appointment_id')
                    ->join('patients', 'patients.id', 'appointments.patient_id')
                    ->whereNull('invoice_items.deleted_at')
                    ->whereNull('invoices.deleted_at')
                    ->where('invoice_items.doctor_id', $request->doctor_id)
                    ->whereBetween(DB::raw('DATE_FORMAT(invoices.created_at, \'%Y-%m-%d\')'), array
                    ($request->start_date, $request->end_date))
                    ->select('invoice_items.*', 'patients.surname', 'patients.othername',
                        DB::raw('sum(price*qty) as amount'))
                    ->groupBy('invoice_items.invoice_id')
                    ->get();
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
                    //get the sum invoice items
                    $amount = $this->TotalInvoiceAmount($row->invoice_id);
                    return number_format($amount);
                })
                ->addColumn('paid_amount', function ($row) {
                    //get the sum invoice items
                    $paid = $this->TotalInvoicePaidAmount($row->invoice_id);
                    return number_format($paid);
                })
                ->addColumn('outstanding', function ($row) {
                    return number_format($this->InvoiceBalance($row->invoice_id));
                })
                ->rawColumns(['amount'])
                ->make(true);
        }
        $data['doctors'] = User::where('is_doctor', 'Yes')->Orderby('id', 'DESC')->get();
        return view('reports.doctor_performance_report')->with($data);
    }


    public function downloadPerformanceReport(Request $request)
    {
        $queryBuilder = collect();
        if ($request->session()->get('from') != '' && $request->session()->get('to') != ''
            && $request->session()->get('doctor_id') != '') {
            $queryBuilder =
                DB::table('invoice_items')
                    ->leftJoin('invoices', 'invoices.id', 'invoice_items.invoice_id')
                    ->leftJoin('appointments', 'appointments.id', 'invoices.appointment_id')
                    ->leftJoin('patients', 'patients.id', 'appointments.patient_id')
                    ->whereNull('invoice_items.deleted_at')
                    ->where('invoice_items.doctor_id', $request->session()->get('doctor_id'))
                    ->whereBetween(DB::raw('DATE_FORMAT(invoice_items.created_at, \'%Y-%m-%d\')'),
                        array($request->session()->get('from'),
                            $request->session()->get('to')))
                    ->select('invoice_no', 'invoices.created_at', 'invoice_items.invoice_id', 'invoice_items.doctor_id', 'patients.surname', 'patients.othername', DB::raw("sum(qty*price) as total_amount"))
                    ->groupBy('invoice_items.invoice_id')
                    ->get();
        }

        $user = User::where('id', $request->session()->get('doctor_id'))->first();
        $excel_file_name = \App\Http\Helper\NameHelper::join($user->surname, $user->othername) . '-performance-report-' . date('Y-m-d') . '.xlsx';
        $sheet_title = "From " . date('d-m-Y', strtotime($request->session()->get('from'))) . " To " .
            date('d-m-Y', strtotime($request->session()->get('to')));

        return Excel::download(new DoctorPerformanceExport($queryBuilder, $sheet_title), $excel_file_name);
    }

    private function TotalInvoiceAmount($id)
    {
        return InvoiceItem::where('invoice_id', $id)->sum(DB::raw('qty*price'));
    }

    /**
     * @param $invoice_id
     * @param $doctor_id
     * @return string
     */
    private function invoiceProcedures($invoice_id, $doctor_id)
    {
        //get the list procedure done by the doctor
        $procedures = DB::table("invoice_items")
            ->leftjoin("medical_services", "medical_services.id", "invoice_items.medical_service_id")
            ->whereNull("invoice_items.deleted_at")
            ->where(["invoice_items.invoice_id" => $invoice_id, 'invoice_items.doctor_id' => $doctor_id])
            ->select("medical_services.name", "invoice_items.*")
            ->get();
        $procedure = "";
        foreach ($procedures as $value) {
            $procedure .= $value->name;
        }
        return $procedure;
    }

    private function TotalInvoicePaidAmount($id)
    {
        return InvoicePayment::where('invoice_id', $id)->sum('amount');
    }

    private function InvoiceBalance($invoice_id)
    {

        $balance = $this->TotalInvoiceAmount($invoice_id) - $this->TotalInvoicePaidAmount($invoice_id);
        return $balance;
    }
}
