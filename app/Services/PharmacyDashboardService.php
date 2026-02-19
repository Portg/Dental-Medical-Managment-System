<?php

namespace App\Services;

use App\Appointment;
use App\InventoryItem;
use App\Prescription;

class PharmacyDashboardService
{
    /**
     * Get dashboard data for the pharmacy.
     */
    public function getDashboardData(): array
    {
        return [
            'pending_prescriptions' => Prescription::pending()->count(),
            'low_stock_items' => InventoryItem::lowStock()->count(),
            'today_appointments' => Appointment::today()->count(),
        ];
    }
}
