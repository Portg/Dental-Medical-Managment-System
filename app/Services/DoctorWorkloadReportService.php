<?php

namespace App\Services;

use App\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DoctorWorkloadReportService
{
    /**
     * Get doctor workload report data.
     */
    public function getReportData(?string $startDateStr, ?string $endDateStr): array
    {
        $startDate = $startDateStr ? Carbon::parse($startDateStr) : Carbon::now()->startOfMonth();
        $endDate = $endDateStr ? Carbon::parse($endDateStr) : Carbon::now()->endOfMonth();

        $doctorStats = $this->getDoctorStats($startDate, $endDate);
        $dailyTrend = $this->getDailyTrendByDoctor($startDate, $endDate);
        $totalAppointments = $doctorStats->sum('total_appointments');
        $totalCompleted = $doctorStats->sum('completed');
        $overallCompletionRate = $totalAppointments > 0
            ? round(($totalCompleted / $totalAppointments) * 100, 1)
            : 0;

        return compact(
            'doctorStats',
            'dailyTrend',
            'totalAppointments',
            'totalCompleted',
            'overallCompletionRate',
            'startDate',
            'endDate'
        );
    }

    private function getDoctorStats(Carbon $startDate, Carbon $endDate): Collection
    {
        $days = max($startDate->diffInDays($endDate), 1);

        return DB::table('appointments as a')
            ->join('users as u', 'a.doctor_id', '=', 'u.id')
            ->whereBetween('a.start_date', [$startDate, $endDate])
            ->whereNull('a.deleted_at')
            ->select(
                'u.id as doctor_id',
                'u.surname as doctor_name',
                DB::raw('COUNT(a.id) as total_appointments'),
                DB::raw('SUM(CASE WHEN a.status IN ("' . Appointment::STATUS_COMPLETED . '","' . Appointment::STATUS_CHECKED_IN . '","' . Appointment::STATUS_IN_PROGRESS . '") THEN 1 ELSE 0 END) as completed'),
                DB::raw('SUM(CASE WHEN a.status IN ("' . Appointment::STATUS_CANCELLED . '","Cancelled") THEN 1 ELSE 0 END) as cancelled'),
                DB::raw('SUM(CASE WHEN a.status IN ("' . Appointment::STATUS_NO_SHOW . '","No-Show","no_show") THEN 1 ELSE 0 END) as no_show')
            )
            ->groupBy('u.id', 'u.surname')
            ->orderByDesc('total_appointments')
            ->get()
            ->map(function ($row) use ($days) {
                $row->completion_rate = $row->total_appointments > 0
                    ? round(($row->completed / $row->total_appointments) * 100, 1) : 0;
                $row->no_show_rate = $row->total_appointments > 0
                    ? round(($row->no_show / $row->total_appointments) * 100, 1) : 0;
                $row->daily_avg = round($row->total_appointments / $days, 1);
                return $row;
            });
    }

    private function getDailyTrendByDoctor(Carbon $startDate, Carbon $endDate): array
    {
        $rows = DB::table('appointments as a')
            ->join('users as u', 'a.doctor_id', '=', 'u.id')
            ->whereBetween('a.start_date', [$startDate, $endDate])
            ->whereNull('a.deleted_at')
            ->select(
                DB::raw('DATE(a.start_date) as date'),
                'u.surname as doctor_name',
                DB::raw('COUNT(a.id) as count')
            )
            ->groupBy(DB::raw('DATE(a.start_date)'), 'u.surname')
            ->orderBy('date')
            ->get();

        // Build date→doctor→count structure
        $doctors = $rows->pluck('doctor_name')->unique()->values()->toArray();
        $dateMap = [];
        foreach ($rows as $row) {
            $dateMap[$row->date][$row->doctor_name] = $row->count;
        }

        // Fill all dates
        $dates = [];
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $dates[] = $current->toDateString();
            $current->addDay();
        }

        return [
            'dates' => $dates,
            'doctors' => $doctors,
            'data' => $dateMap,
        ];
    }
}
