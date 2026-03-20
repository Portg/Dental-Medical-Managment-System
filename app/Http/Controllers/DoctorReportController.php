<?php

namespace App\Http\Controllers;

use App\Http\Helper\FunctionsHelper;
use App\Services\DoctorPerformanceReportService;
use App\Services\DoctorWorkloadReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\DataTables;
use App\Exports\DoctorPerformanceExport;
use Maatwebsite\Excel\Facades\Excel;

class DoctorReportController extends Controller
{
    private DoctorPerformanceReportService $performanceService;
    private DoctorWorkloadReportService $workloadService;

    public function __construct(
        DoctorPerformanceReportService $performanceService,
        DoctorWorkloadReportService $workloadService
    ) {
        $this->performanceService = $performanceService;
        $this->workloadService = $workloadService;
        // Admin/SuperAdmin 通过 view-reports，医生通过 view-own-doctor-report
        $this->middleware(function ($request, $next) {
            if (Gate::allows('view-reports') || Gate::allows('view-own-doctor-report')) {
                return $next($request);
            }
            abort(403);
        });
    }

    public function index(Request $request)
    {
        $tab = $request->input('tab', 'performance');
        $currentUser = Auth::user();
        $isDoctorUser = (bool) $currentUser->is_doctor;

        // Doctors can only see their own data
        $restrictedDoctorId = $isDoctorUser ? $currentUser->id : null;

        // AJAX for performance DataTable
        if ($request->ajax() && $tab === 'performance') {
            $doctorId = $isDoctorUser ? $currentUser->id : (int) $request->doctor_id;

            if (!empty($request->start_date) && !empty($request->end_date)) {
                $request->merge(['doctor_id' => $doctorId]);
                FunctionsHelper::storeDateFilter($request);
            }
            $data = collect();
            if (!empty($request->start_date) && !empty($request->end_date)) {
                $data = $this->performanceService->getPerformanceData(
                    $doctorId,
                    $request->start_date,
                    $request->end_date
                );
            }
            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('created_at', fn($row) => $row->created_at ? date('Y-m-d', strtotime($row->created_at)) : '-')
                ->addColumn('patient', fn($row) => \App\Http\Helper\NameHelper::join($row->surname, $row->othername))
                ->addColumn('done_procedures_amount', fn($row) => number_format($row->amount))
                ->addColumn('invoice_amount', fn($row) => number_format($row->invoice_total_amount))
                ->addColumn('paid_amount', fn($row) => number_format($row->invoice_paid_amount))
                ->addColumn('outstanding', fn($row) => number_format($row->invoice_total_amount - $row->invoice_paid_amount))
                ->make(true);
        }

        // Workload tab data
        $workloadData = [];
        if ($tab === 'workload' || !$request->ajax()) {
            $workloadData = $this->workloadService->getReportData(
                $request->input('start_date'),
                $request->input('end_date'),
                $restrictedDoctorId
            );
        }

        $data = array_merge($workloadData, [
            'doctors'       => $isDoctorUser ? collect([$currentUser]) : $this->performanceService->getDoctors(),
            'activeTab'     => $tab,
            'isDoctorUser'  => $isDoctorUser,
            'currentUserId' => $currentUser->id,
        ]);

        return view('reports.doctor_report', $data);
    }

    public function downloadPerformanceReport(Request $request)
    {
        $from = $request->session()->get('from');
        $to = $request->session()->get('to');

        $currentUser = Auth::user();
        // Doctor users can only download their own report
        $doctorId = $currentUser->is_doctor
            ? $currentUser->id
            : $request->session()->get('doctor_id');

        $queryBuilder = collect();
        if ($from && $to && $doctorId) {
            $queryBuilder = $this->performanceService->getExportData($doctorId, $from, $to);
        }

        $user = $this->performanceService->findDoctor($doctorId);
        $excel_file_name = \App\Http\Helper\NameHelper::join($user->surname, $user->othername) . '-performance-report-' . date('Y-m-d') . '.xlsx';
        $sheet_title = "From " . date('d-m-Y', strtotime($from)) . " To " . date('d-m-Y', strtotime($to));

        return Excel::download(new DoctorPerformanceExport($queryBuilder, $sheet_title), $excel_file_name);
    }
}
