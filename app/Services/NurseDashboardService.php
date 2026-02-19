<?php

namespace App\Services;

use App\Appointment;
use App\PatientFollowup;
use App\WaitingQueue;

class NurseDashboardService
{
    /**
     * Get dashboard data for the nurse.
     */
    public function getDashboardData(): array
    {
        return [
            'waiting_queue_count' => WaitingQueue::today()->count(),
            'today_appointments' => Appointment::today()->count(),
            'overdue_followups' => PatientFollowup::overdue()->count(),
        ];
    }
}
