<?php

namespace App\Http\Controllers;

use App\Services\TodayWorkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TodayWorkController extends Controller
{
    private TodayWorkService $service;

    public function __construct(TodayWorkService $service)
    {
        $this->service = $service;
        $this->middleware('can:view-appointments');
    }

    /**
     * 今日工作主页面
     */
    public function index()
    {
        $branchId = Auth::user()->branch_id;

        $kpi     = $this->service->getKpi($branchId);
        $stats   = $this->service->getStats($branchId);
        $doctors = $this->service->getDoctors();

        return view('today_work.index', compact('kpi', 'stats', 'doctors'));
    }

    /**
     * DataTable 数据（AJAX）
     */
    public function getData(Request $request)
    {
        $branchId = Auth::user()->branch_id;
        $status = $request->input('status');
        $search = $request->input('search_patient');
        $date = $request->input('date');
        $doctorId = $request->input('doctor_id') ? (int) $request->input('doctor_id') : null;

        $query = $this->service->getTodayWorkQuery($branchId, $status, $search, $date, $doctorId);

        return $this->service->buildDataTable($query);
    }

    /**
     * 状态计数 + KPI（AJAX 刷新）
     */
    public function getStats(Request $request)
    {
        $branchId = Auth::user()->branch_id;
        $date = $request->input('date');
        $doctorId = $request->input('doctor_id') ? (int) $request->input('doctor_id') : null;

        return response()->json([
            'kpi'   => $this->service->getKpi($branchId, $date),
            'stats' => $this->service->getStats($branchId, $date, $doctorId),
        ]);
    }

    /**
     * 看板视图数据（AJAX）
     */
    public function getKanbanData(Request $request)
    {
        $branchId = Auth::user()->branch_id;
        $date = $request->input('date');
        $doctorId = $request->input('doctor_id') ? (int) $request->input('doctor_id') : null;

        return response()->json($this->service->getKanbanData($branchId, $date, $doctorId));
    }

    /**
     * 患者摘要（AJAX — 侧栏用）
     */
    public function getPatientSummary($patientId)
    {
        $patient = \App\Patient::with(['appointments' => function ($q) {
            $q->orderByDesc('start_date')->limit(10);
        }, 'appointments.doctor', 'appointments.service', 'invoices' => function ($q) {
            $q->orderByDesc('created_at')->limit(10);
        }])->findOrFail($patientId);

        return response()->json([
            'id'            => $patient->id,
            'full_name'     => $patient->full_name,
            'patient_no'    => $patient->patient_no,
            'gender'        => $patient->gender,
            'dob'           => $patient->dob,
            'phone_no'      => $patient->phone_no ? substr($patient->phone_no, 0, 3) . '****' . substr($patient->phone_no, -4) : '',
            'member_status' => $patient->member_status,
            'allergies'     => $patient->allergies,
            'appointments'  => $patient->appointments->map(function ($a) {
                return [
                    'id'      => $a->id,
                    'date'    => $a->start_date,
                    'time'    => $a->start_time ? date('H:i', strtotime($a->start_time)) : '',
                    'doctor'  => $a->doctor ? ($a->doctor->surname . $a->doctor->othername) : '',
                    'service' => $a->service->name ?? '',
                    'status'  => $a->status,
                ];
            }),
            'invoices' => $patient->invoices->map(function ($inv) {
                return [
                    'id'           => $inv->id,
                    'invoice_no'   => $inv->invoice_no ?? '',
                    'total_amount' => $inv->total_amount,
                    'paid_amount'  => $inv->paid_amount ?? 0,
                    'created_at'   => $inv->created_at ? $inv->created_at->format('Y-m-d') : '',
                ];
            }),
        ]);
    }

    /**
     * 今日对账数据（AJAX）
     */
    public function getBilling(Request $request)
    {
        $branchId = Auth::user()->branch_id;
        $date = $request->input('date');
        return response()->json($this->service->getTodayBilling($branchId, $date));
    }

    /**
     * 今日回访数据（AJAX）
     */
    public function getFollowups(Request $request)
    {
        $branchId = Auth::user()->branch_id;
        $date = $request->input('date');
        $status = $request->input('status');
        $search = $request->input('search');
        $doctorId = $request->input('doctor_id') ? (int) $request->input('doctor_id') : null;
        $followupType = $request->input('followup_type');
        return response()->json($this->service->getTodayFollowups($branchId, $date, $status, $search, $doctorId, $followupType));
    }

    /**
     * 明日预约数据（AJAX）
     */
    public function getTomorrow(Request $request)
    {
        $branchId = Auth::user()->branch_id;
        $date = $request->input('date');
        $doctorId = $request->input('doctor_id') ? (int) $request->input('doctor_id') : null;
        $search = $request->input('search');
        return response()->json($this->service->getTomorrowAppointments($branchId, $date, $doctorId, $search));
    }

    /**
     * 一周失约数据（AJAX）
     */
    public function getWeekMissed(Request $request)
    {
        $branchId = Auth::user()->branch_id;
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        return response()->json($this->service->getWeekMissed($branchId, $startDate, $endDate));
    }

    /**
     * 今日生日数据（AJAX）
     */
    public function getBirthdays(Request $request)
    {
        $branchId = Auth::user()->branch_id;
        $date = $request->input('date');
        return response()->json($this->service->getTodayBirthdays($branchId, $date));
    }

    /**
     * 医生表视图数据（AJAX）
     */
    public function getDoctorTable(Request $request)
    {
        $branchId = Auth::user()->branch_id;
        $date = $request->input('date');
        $doctorId = $request->input('doctor_id') ? (int) $request->input('doctor_id') : null;
        return response()->json($this->service->getDoctorTable($branchId, $date, $doctorId));
    }

    /**
     * 今日已收款数据（AJAX）
     */
    public function getPaid(Request $request)
    {
        $branchId = Auth::user()->branch_id;
        $date = $request->input('date');
        return response()->json($this->service->getTodayPayments($branchId, $date));
    }

    /**
     * 今日待收款数据（AJAX）
     */
    public function getUnpaid(Request $request)
    {
        $branchId = Auth::user()->branch_id;
        $date = $request->input('date');
        return response()->json($this->service->getTodayUnpaid($branchId, $date));
    }

    /**
     * 外加工查询数据（AJAX）
     */
    public function getLabCases(Request $request)
    {
        $branchId = Auth::user()->branch_id;
        $date = $request->input('date');
        return response()->json($this->service->getLabCases($branchId, $date));
    }

    /**
     * 信息标签页徽章计数（AJAX）
     */
    public function getTabCounts(Request $request)
    {
        $branchId = Auth::user()->branch_id;
        $date = $request->input('date');
        return response()->json($this->service->getTabCounts($branchId, $date));
    }

    /**
     * 标记未到诊
     */
    public function markNoShow($appointmentId)
    {
        $success = $this->service->markNoShow((int) $appointmentId);

        return response()->json([
            'status'  => $success ? 'success' : 'error',
            'message' => $success ? __('today_work.mark_no_show_success') : __('common.error'),
        ]);
    }
}
