<?php

namespace App\Services;

use App\Appointment;
use App\Branch;
use App\Chair;
use App\WaitingQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

class WaitingQueueService
{
    /**
     * Get chairs for the current branch.
     */
    public function getBranchChairs(int $branchId): Collection
    {
        return Chair::where('branch_id', $branchId)
            ->where('status', 'active')
            ->get();
    }

    /**
     * Get waiting queue query for DataTables.
     */
    public function getQueueQuery(int $branchId, ?string $status = null): Builder
    {
        $query = WaitingQueue::forBranch($branchId)
            ->today()
            ->with(['patient', 'doctor', 'chair', 'appointment'])
            ->orderBy('queue_number');

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        return $query;
    }

    /**
     * Check in a patient for an appointment.
     */
    public function checkIn(int $appointmentId, int $branchId, int $userId): WaitingQueue
    {
        return WaitingQueue::checkIn($appointmentId, $branchId, $userId);
    }

    /**
     * Call a patient from the waiting queue.
     */
    public function callPatient(int $queueId, int $calledBy, ?int $chairId = null): array
    {
        $queue = WaitingQueue::findOrFail($queueId);

        if ($queue->status !== WaitingQueue::STATUS_WAITING) {
            return ['success' => false, 'message' => __('waiting_queue.invalid_status_for_call')];
        }

        $queue->callPatient($calledBy, $chairId);

        return [
            'success' => true,
            'queue_number' => $queue->queue_number,
            'patient_name' => $queue->masked_patient_name,
            'chair_name' => $queue->chair->chair_name ?? '',
        ];
    }

    /**
     * Start treatment for a queue entry.
     */
    public function startTreatment(int $queueId): array
    {
        $queue = WaitingQueue::findOrFail($queueId);

        if ($queue->status !== WaitingQueue::STATUS_CALLED) {
            return ['success' => false, 'message' => __('waiting_queue.invalid_status_for_start')];
        }

        $queue->startTreatment();

        return ['success' => true];
    }

    /**
     * Complete treatment for a queue entry.
     */
    public function completeTreatment(int $queueId): array
    {
        $queue = WaitingQueue::findOrFail($queueId);

        if ($queue->status !== WaitingQueue::STATUS_IN_TREATMENT) {
            return ['success' => false, 'message' => __('waiting_queue.invalid_status_for_complete')];
        }

        $queue->completeTreatment();

        return ['success' => true];
    }

    /**
     * Cancel a queue entry.
     */
    public function cancelQueue(int $queueId, ?string $reason = null): array
    {
        $queue = WaitingQueue::findOrFail($queueId);

        if (!in_array($queue->status, [WaitingQueue::STATUS_WAITING, WaitingQueue::STATUS_CALLED])) {
            return ['success' => false, 'message' => __('waiting_queue.cannot_cancel')];
        }

        $queue->cancel($reason);

        return ['success' => true];
    }

    /**
     * Get branch for display screen.
     */
    public function getBranch(int $branchId): ?Branch
    {
        return Branch::find($branchId);
    }

    /**
     * Get display screen data.
     */
    public function getDisplayData(int $branchId): array
    {
        $currentCalling = WaitingQueue::getCurrentCalling($branchId);
        $waitingList = WaitingQueue::getWaitingList($branchId, 8);
        $inTreatmentList = WaitingQueue::getInTreatmentList($branchId);

        $stats = [
            'waiting_count' => WaitingQueue::forBranch($branchId)->today()->waiting()->count(),
            'in_treatment_count' => WaitingQueue::forBranch($branchId)->today()->inTreatment()->count(),
            'completed_count' => WaitingQueue::forBranch($branchId)->today()
                ->where('status', WaitingQueue::STATUS_COMPLETED)->count(),
        ];

        return [
            'current_calling' => $currentCalling ? [
                'queue_number' => $currentCalling->queue_number,
                'patient_name' => $currentCalling->masked_patient_name,
                'doctor_name' => $currentCalling->doctor->surname ?? '',
                'chair_name' => $currentCalling->chair->chair_name ?? '',
                'called_time' => $currentCalling->called_time->format('H:i'),
            ] : null,
            'waiting_list' => $waitingList->map(function ($item) {
                return [
                    'queue_number' => $item->queue_number,
                    'patient_name' => $item->masked_patient_name,
                    'doctor_name' => $item->doctor->surname ?? '',
                    'check_in_time' => $item->check_in_time->format('H:i'),
                    'estimated_wait' => $item->estimated_wait_minutes,
                ];
            }),
            'in_treatment_list' => $inTreatmentList->map(function ($item) {
                return [
                    'patient_name' => $item->masked_patient_name,
                    'doctor_name' => $item->doctor->surname ?? '',
                    'chair_name' => $item->chair->chair_name ?? '',
                ];
            }),
            'stats' => $stats,
            'current_time' => now()->format('H:i:s'),
        ];
    }

    /**
     * Get today's appointments eligible for check-in.
     */
    public function getTodayAppointments(): Collection
    {
        return Appointment::where('appointment_date', today())
            ->whereIn('status', ['confirmed', 'pending', 'scheduled'])
            ->whereDoesntHave('waitingQueue', function ($query) {
                $query->today()->whereNotIn('status', [
                    WaitingQueue::STATUS_CANCELLED,
                    WaitingQueue::STATUS_NO_SHOW,
                ]);
            })
            ->with(['patients', 'doctors'])
            ->orderBy('appointment_time')
            ->get();
    }

    /**
     * Get doctor's waiting patients and current in-treatment.
     */
    public function getDoctorQueueData(int $doctorId, int $branchId): array
    {
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

        return compact('waitingPatients', 'inTreatment');
    }

    /**
     * Call next patient for a doctor.
     */
    public function callNextForDoctor(int $doctorId, int $branchId, ?int $chairId = null): array
    {
        $nextPatient = WaitingQueue::forBranch($branchId)
            ->forDoctor($doctorId)
            ->today()
            ->waiting()
            ->orderByQueue()
            ->first();

        if (!$nextPatient) {
            return ['success' => false, 'no_patients' => true];
        }

        $nextPatient->callPatient($doctorId, $chairId);

        return [
            'success' => true,
            'queue_number' => $nextPatient->queue_number,
            'patient_name' => $nextPatient->patient->name ?? '',
            'patient_id' => $nextPatient->patient_id,
        ];
    }

    // ─── DataTable formatting ────────────────────────────────────

    /**
     * Build DataTables response for the waiting queue list.
     */
    public function buildQueueDataTable($query)
    {
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
                return '<span class="label label-' . $badge . '">' . e($row->status_text) . '</span>';
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
}
