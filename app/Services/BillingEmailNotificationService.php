<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BillingEmailNotificationService
{
    /**
     * Get billing email notifications with optional filters.
     */
    public function getList(array $filters): Collection
    {
        $query = DB::table('billing_email_notifications')
            ->select(
                'billing_email_notifications.*',
                DB::raw('DATE_FORMAT(billing_email_notifications.created_at, "%d-%b-%Y") as created_at')
            );

        if (!empty($filters['search'])) {
            $query->where('billing_email_notifications.message', 'like', '%' . $filters['search'] . '%');
        } elseif (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween(
                DB::raw('DATE(billing_email_notifications.created_at)'),
                [$filters['start_date'], $filters['end_date']]
            );
        }

        return $query->orderBy('billing_email_notifications.id', 'desc')->get();
    }
}
