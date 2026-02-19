<?php

namespace App\Http\Controllers;

use App\Services\WaitingQueueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WaitingQueueController extends Controller
{
    private WaitingQueueService $waitingQueueService;

    public function __construct(WaitingQueueService $waitingQueueService)
    {
        $this->waitingQueueService = $waitingQueueService;
        $this->middleware('can:manage-schedules');
    }

    /**
     * 候诊管理页面（前台使用）
     */
    public function index()
    {
        $branchId = Auth::user()->branch_id;
        $chairs = $this->waitingQueueService->getBranchChairs($branchId);

        return view('waiting_queue.index', compact('chairs'));
    }

    /**
     * 获取候诊队列数据（DataTable）
     */
    public function getData(Request $request)
    {
        $branchId = Auth::user()->branch_id;
        $status = ($request->has('status') && $request->status !== '') ? $request->status : null;
        $query = $this->waitingQueueService->getQueueQuery($branchId, $status);

        return $this->waitingQueueService->buildQueueDataTable($query);
    }

    /**
     * 患者签到
     */
    public function checkIn(Request $request)
    {
        $request->validate([
            'appointment_id' => 'required|exists:appointments,id'
        ]);

        try {
            $branchId = Auth::user()->branch_id;
            $queue = $this->waitingQueueService->checkIn(
                $request->appointment_id,
                $branchId,
                Auth::id()
            );

            return response()->json([
                'status' => 'success',
                'message' => __('waiting_queue.check_in_success'),
                'data' => [
                    'queue_number' => $queue->queue_number,
                    'estimated_wait' => $queue->estimated_wait_minutes
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * 叫号
     */
    public function callPatient(Request $request, $id)
    {
        $result = $this->waitingQueueService->callPatient($id, Auth::id(), $request->chair_id);

        if (!$result['success']) {
            return response()->json([
                'status' => 'error',
                'message' => $result['message']
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('waiting_queue.call_success'),
            'data' => [
                'queue_number' => $result['queue_number'],
                'patient_name' => $result['patient_name'],
                'chair_name' => $result['chair_name']
            ]
        ]);
    }

    /**
     * 开始就诊
     */
    public function startTreatment($id)
    {
        $result = $this->waitingQueueService->startTreatment($id);

        if (!$result['success']) {
            return response()->json([
                'status' => 'error',
                'message' => $result['message']
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('waiting_queue.treatment_started')
        ]);
    }

    /**
     * 完成就诊
     */
    public function completeTreatment($id)
    {
        $result = $this->waitingQueueService->completeTreatment($id);

        if (!$result['success']) {
            return response()->json([
                'status' => 'error',
                'message' => $result['message']
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('waiting_queue.treatment_completed')
        ]);
    }

    /**
     * 取消排队
     */
    public function cancel(Request $request, $id)
    {
        $result = $this->waitingQueueService->cancelQueue($id, $request->reason);

        if (!$result['success']) {
            return response()->json([
                'status' => 'error',
                'message' => $result['message']
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('waiting_queue.cancelled')
        ]);
    }

    /**
     * 叫号大屏显示页面
     */
    public function displayScreen()
    {
        $branchId = Auth::user()->branch_id ?? 1;
        $branch = $this->waitingQueueService->getBranch($branchId);

        return view('waiting_queue.display_screen', compact('branch'));
    }

    /**
     * 获取大屏显示数据（AJAX轮询）
     */
    public function getDisplayData(Request $request)
    {
        $branchId = $request->branch_id ?? Auth::user()->branch_id ?? 1;

        return response()->json($this->waitingQueueService->getDisplayData($branchId));
    }

    /**
     * 获取今日可签到的预约列表
     */
    public function getTodayAppointments(Request $request)
    {
        $appointments = $this->waitingQueueService->getTodayAppointments();

        return response()->json([
            'data' => $appointments->map(function ($apt) {
                return [
                    'id' => $apt->id,
                    'time' => $apt->appointment_time,
                    'patient_name' => $apt->patients->name ?? '-',
                    'patient_phone' => $apt->patients->telephone ?? '-',
                    'doctor_name' => $apt->doctors->surname ?? '-',
                    'category' => $apt->appointment_category ?? '-'
                ];
            })
        ]);
    }

    /**
     * 医生工作站 - 我的候诊患者
     */
    public function doctorQueue()
    {
        $doctorId = Auth::id();
        $branchId = Auth::user()->branch_id;

        $data = $this->waitingQueueService->getDoctorQueueData($doctorId, $branchId);

        return view('waiting_queue.doctor_queue', $data);
    }

    /**
     * 医生叫下一位患者
     */
    public function callNext(Request $request)
    {
        $doctorId = Auth::id();
        $branchId = Auth::user()->branch_id;

        $result = $this->waitingQueueService->callNextForDoctor($doctorId, $branchId, $request->chair_id);

        if (!empty($result['no_patients'])) {
            return response()->json([
                'status' => 'info',
                'message' => __('waiting_queue.no_waiting_patients')
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('waiting_queue.call_success'),
            'data' => [
                'queue_number' => $result['queue_number'],
                'patient_name' => $result['patient_name'],
                'patient_id' => $result['patient_id']
            ]
        ]);
    }
}
