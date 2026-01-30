<?php

namespace App\Http\Controllers;

use App\Appointment;
use App\Branch;
use App\Chair;
use App\WaitingQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class WaitingQueueController extends Controller
{
    /**
     * 候诊管理页面（前台使用）
     */
    public function index()
    {
        $branchId = Auth::user()->branch_id;
        $chairs = Chair::where('branch_id', $branchId)
            ->where('status', 'active')
            ->get();

        return view('waiting_queue.index', compact('chairs'));
    }

    /**
     * 获取候诊队列数据（DataTable）
     */
    public function getData(Request $request)
    {
        $branchId = Auth::user()->branch_id;

        $query = WaitingQueue::forBranch($branchId)
            ->today()
            ->with(['patient', 'doctor', 'chair', 'appointment'])
            ->orderBy('queue_number');

        // 状态筛选
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

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
            $queue = WaitingQueue::checkIn(
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
        $queue = WaitingQueue::findOrFail($id);

        if ($queue->status !== WaitingQueue::STATUS_WAITING) {
            return response()->json([
                'status' => 'error',
                'message' => __('waiting_queue.invalid_status_for_call')
            ], 400);
        }

        $queue->callPatient(Auth::id(), $request->chair_id);

        return response()->json([
            'status' => 'success',
            'message' => __('waiting_queue.call_success'),
            'data' => [
                'queue_number' => $queue->queue_number,
                'patient_name' => $queue->masked_patient_name,
                'chair_name' => $queue->chair->chair_name ?? ''
            ]
        ]);
    }

    /**
     * 开始就诊
     */
    public function startTreatment($id)
    {
        $queue = WaitingQueue::findOrFail($id);

        if ($queue->status !== WaitingQueue::STATUS_CALLED) {
            return response()->json([
                'status' => 'error',
                'message' => __('waiting_queue.invalid_status_for_start')
            ], 400);
        }

        $queue->startTreatment();

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
        $queue = WaitingQueue::findOrFail($id);

        if ($queue->status !== WaitingQueue::STATUS_IN_TREATMENT) {
            return response()->json([
                'status' => 'error',
                'message' => __('waiting_queue.invalid_status_for_complete')
            ], 400);
        }

        $queue->completeTreatment();

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
        $queue = WaitingQueue::findOrFail($id);

        if (!in_array($queue->status, [WaitingQueue::STATUS_WAITING, WaitingQueue::STATUS_CALLED])) {
            return response()->json([
                'status' => 'error',
                'message' => __('waiting_queue.cannot_cancel')
            ], 400);
        }

        $queue->cancel($request->reason);

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
        $branch = Branch::find($branchId);

        return view('waiting_queue.display_screen', compact('branch'));
    }

    /**
     * 获取大屏显示数据（AJAX轮询）
     */
    public function getDisplayData(Request $request)
    {
        $branchId = $request->branch_id ?? Auth::user()->branch_id ?? 1;

        // 当前叫号
        $currentCalling = WaitingQueue::getCurrentCalling($branchId);

        // 候诊队列
        $waitingList = WaitingQueue::getWaitingList($branchId, 8);

        // 就诊中
        $inTreatmentList = WaitingQueue::getInTreatmentList($branchId);

        // 统计数据
        $stats = [
            'waiting_count' => WaitingQueue::forBranch($branchId)->today()->waiting()->count(),
            'in_treatment_count' => WaitingQueue::forBranch($branchId)->today()->inTreatment()->count(),
            'completed_count' => WaitingQueue::forBranch($branchId)->today()
                ->where('status', WaitingQueue::STATUS_COMPLETED)->count(),
        ];

        return response()->json([
            'current_calling' => $currentCalling ? [
                'queue_number' => $currentCalling->queue_number,
                'patient_name' => $currentCalling->masked_patient_name,
                'doctor_name' => $currentCalling->doctor->surname ?? '',
                'chair_name' => $currentCalling->chair->chair_name ?? '',
                'called_time' => $currentCalling->called_time->format('H:i')
            ] : null,
            'waiting_list' => $waitingList->map(function ($item) {
                return [
                    'queue_number' => $item->queue_number,
                    'patient_name' => $item->masked_patient_name,
                    'doctor_name' => $item->doctor->surname ?? '',
                    'check_in_time' => $item->check_in_time->format('H:i'),
                    'estimated_wait' => $item->estimated_wait_minutes
                ];
            }),
            'in_treatment_list' => $inTreatmentList->map(function ($item) {
                return [
                    'patient_name' => $item->masked_patient_name,
                    'doctor_name' => $item->doctor->surname ?? '',
                    'chair_name' => $item->chair->chair_name ?? ''
                ];
            }),
            'stats' => $stats,
            'current_time' => now()->format('H:i:s')
        ]);
    }

    /**
     * 获取今日可签到的预约列表
     */
    public function getTodayAppointments(Request $request)
    {
        $branchId = Auth::user()->branch_id;

        // 今日预约，状态为已确认或待确认的
        $appointments = Appointment::where('appointment_date', today())
            ->whereIn('status', ['confirmed', 'pending', 'scheduled'])
            ->whereDoesntHave('waitingQueue', function ($query) {
                $query->today()->whereNotIn('status', [
                    WaitingQueue::STATUS_CANCELLED,
                    WaitingQueue::STATUS_NO_SHOW
                ]);
            })
            ->with(['patients', 'doctors'])
            ->orderBy('appointment_time')
            ->get();

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

        $waitingPatients = WaitingQueue::forBranch($branchId)
            ->forDoctor($doctorId)
            ->today()
            ->whereIn('status', [WaitingQueue::STATUS_WAITING, WaitingQueue::STATUS_CALLED])
            ->with(['patient', 'appointment'])
            ->orderByQueue()
            ->get();

        $inTreatment = WaitingQueue::forBranch($branchId)
            ->forDoctor($doctorId)
            ->today()
            ->inTreatment()
            ->with(['patient', 'chair'])
            ->first();

        return view('waiting_queue.doctor_queue', compact('waitingPatients', 'inTreatment'));
    }

    /**
     * 医生叫下一位患者
     */
    public function callNext(Request $request)
    {
        $doctorId = Auth::id();
        $branchId = Auth::user()->branch_id;

        // 获取该医生的下一位等待患者
        $nextPatient = WaitingQueue::forBranch($branchId)
            ->forDoctor($doctorId)
            ->today()
            ->waiting()
            ->orderByQueue()
            ->first();

        if (!$nextPatient) {
            return response()->json([
                'status' => 'info',
                'message' => __('waiting_queue.no_waiting_patients')
            ]);
        }

        $nextPatient->callPatient(Auth::id(), $request->chair_id);

        return response()->json([
            'status' => 'success',
            'message' => __('waiting_queue.call_success'),
            'data' => [
                'queue_number' => $nextPatient->queue_number,
                'patient_name' => $nextPatient->patient->name ?? '',
                'patient_id' => $nextPatient->patient_id
            ]
        ]);
    }
}
