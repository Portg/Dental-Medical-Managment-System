<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class UnpaidInvoicesReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view-reports');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $status = $request->input('payment_status', '');
            $start  = $request->input('start_date');
            $end    = $request->input('end_date');

            // AG-044: 日期范围最大 12 个月
            if ($start && $end) {
                $startCarbon = Carbon::parse($start);
                $endCarbon   = Carbon::parse($end);
                if ($startCarbon->diffInMonths($endCarbon) > 12) {
                    return response()->json(['status' => 0, 'message' => __('report.date_range_too_large')], 400);
                }
            }

            $q = DB::table('invoices')
                ->whereNull('invoices.deleted_at')
                ->whereIn('invoices.payment_status', ['unpaid', 'partial', 'overdue'])
                ->leftJoin('patients', 'patients.id', '=', 'invoices.patient_id')
                ->leftJoin('appointments', 'appointments.id', '=', 'invoices.appointment_id')
                ->leftJoin('users as doctors', 'doctors.id', '=', 'appointments.doctor_id')
                ->select(
                    'invoices.id',
                    'invoices.invoice_no',
                    'invoices.invoice_date',
                    'invoices.due_date',
                    'invoices.total_amount',
                    'invoices.paid_amount',
                    'invoices.outstanding_amount',
                    'invoices.payment_status',
                    'patients.surname',
                    'patients.othername',
                    // AG-045: 手机号脱敏
                    DB::raw("CONCAT(LEFT(patients.phone_no,3), '****', RIGHT(patients.phone_no,4)) as phone_masked"),
                    DB::raw('CONCAT(doctors.surname, " ", doctors.othername) as doctor_name')
                );

            if ($status) {
                $q->where('invoices.payment_status', $status);
            }
            if ($start) {
                $q->whereDate('invoices.invoice_date', '>=', $start);
            }
            if ($end) {
                $q->whereDate('invoices.invoice_date', '<=', $end);
            }

            $q->orderByDesc('invoices.outstanding_amount');

            return Datatables::of($q)
                ->addIndexColumn()
                ->addColumn('patient_name', fn($r) => \App\Http\Helper\NameHelper::join($r->surname, $r->othername))
                ->addColumn('total_amount_fmt', fn($r) => number_format($r->total_amount, 2))
                ->addColumn('paid_amount_fmt', fn($r) => number_format($r->paid_amount, 2))
                ->addColumn('outstanding_fmt', fn($r) => number_format($r->outstanding_amount, 2))
                ->make(true);
        }

        return view('reports.unpaid_invoices_report');
    }
}
