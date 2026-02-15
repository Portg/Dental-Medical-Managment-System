<?php

namespace App\Http\Controllers;

use App\Services\WaitingQueueService;
use App\WaitingQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class WaitingQueueController extends Controller
{
    private WaitingQueueService $waitingQueueService;

    public function __construct(WaitingQueueService $waitingQueueService)
    {
        $this->waitingQueueService = $waitingQueueService;
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

        return DataTables::of($query)
            ->addColumn('patient_name', function ($row) {
                return $row->patient->name ?? '-';
            })
            ->addColumn('patient_phone', function ($row) {
                $phone = $row->patient->telephone ?? '';
                if (strlen($phone) >= 11) {
                    return substr($phone, 0, 3) . '****' . substr($phone, -4);
                }
                return $phone;
            })
            ->addColumn('doctor_name', function ($row) {
                return $row->doctor->surname ?? '-';
            })
            ->addColumn('chair_name', function ($row) {
                return $row->chair->chair_name ?? '-';
            })
            ->addColumn('check_in_time_formatted', function ($row) {
                return $row->check_in_time ? $row->check_in_time->format('H:i') : '-';
            })
            ->addColumn('waited_minutes', function ($row) {
                return $row->waited_minutes;
            })
            ->addColumn('status_text', function ($row) {
                return $row->status_text;
            })
            ->addColumn('status_badge', function ($row) {
                $badges = [
                    'waiting' => 'warning',
                    'called' => 'info',
                    'in_treatment' => 'primary',
                    'completed' => 'success',
                    'cancelled' => 'default',
                    'no_show' => 'danger'
                ];
                $badge = $badges[$row->status] ?? 'default';
                return '<span class="label label-' . $badge . '">' . $row->status_text . '</span>';
            })
            ->addColumn('action', function ($row) {
                $actions = '';

                if ($row->status === WaitingQueue::STATUS_WAITING) {
                    $actions .= '<button class="btn btn-xs btn-info" onclick="callPatient(' . $row->id . ')">
                        <i class="icon-volume-2"></i> ' . __('waiting_queue.call') . '
                    </button> ';
                }

                if ($row->status === WaitingQueue::STATUS_CALLED) {
                    $actions .= '<button class="btn btn-xs btn-primary" onclick="startTreatment(' . $row->id . ')">
                        <i class="icon-control-play"></i> ' . __('waiting_queue.start_treatment') . '
                    </button> ';
                    $actions .= '<button class="btn btn-xs btn-warning" onclick="recallPatient(' . $row->id . ')">
                        <i class="icon-volume-2"></i> ' . __('waiting_queue.recall') . '
                    </button> ';
                }

                if ($row->status === WaitingQueue::STATUS_IN_TREATMENT) {
                    $actions .= '<button class="btn btn-xs btn-success" onclick="completeTreatment(' . $row->id . ')">
                        <i class="icon-check"></i> ' . __('waiting_queue.complete') . '
                    </button> ';
                }

                if (in_array($row->status, [WaitingQueue::STATUS_WAITING, WaitingQueue::STATUS_CALLED])) {
                    $actions .= '<button class="btn btn-xs btn-danger" onclick="cancelQueue(' . $row->id . ')">
                        <i class="icon-close"></i> ' . __('common.cancel') . '
                    </button>';
                }

                return $actions;
            })
            ->rawColumns(['status_badge', 'action'])
            ->make(true);
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
