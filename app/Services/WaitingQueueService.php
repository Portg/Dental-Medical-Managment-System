<?php

namespace App\Services;

use App\Appointment;
use App\Branch;
use App\Chair;
use App\WaitingQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

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
}
