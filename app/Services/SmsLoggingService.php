<?php

namespace App\Services;

use App\Http\Helper\NameHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SmsLoggingService
{
    /**
     * Get SMS logging list for DataTables.
     */
    public function getList(array $filters): Collection
    {
        $query = DB::table('sms_loggings')
            ->leftJoin('patients', 'patients.id', 'sms_loggings.patient_id')
            ->select('sms_loggings.*', 'patients.surname', 'patients.othername')
            ->orderBy('sms_loggings.id', 'desc');

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                NameHelper::addNameSearch($q, $filters['search'], 'patients');
            });
        } elseif (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween(DB::raw('DATE(sms_loggings.created_at)'), [
                $filters['start_date'], $filters['end_date'],
            ]);
        }

        return $query->get();
    }

    /**
     * Get SMS logging data for export.
     */
    public function getExportData(?string $from, ?string $to): Collection
    {
        $query = DB::table('sms_loggings')
            ->select('sms_loggings.*')
            ->orderBy('sms_loggings.id', 'desc');

        if ($from && $to) {
            $query->whereBetween(DB::raw('DATE(sms_loggings.created_at)'), [$from, $to]);
        }

        return $query->get();
    }
}
