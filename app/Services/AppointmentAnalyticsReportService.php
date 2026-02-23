<?php

namespace App\Services;

use App\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AppointmentAnalyticsReportService
{
    public function getReportData(?string $startDateStr, ?string $endDateStr): array
    {
        $startDate = $startDateStr ? Carbon::parse($startDateStr) : Carbon::now()->startOfMonth();
        $endDate = $endDateStr ? Carbon::parse($endDateStr) : Carbon::now()->endOfMonth();

        $summary = $this->getSummary($startDate, $endDate);
        $peakHours = $this->getPeakHoursDistribution($startDate, $endDate);
        $dailyTrend = $this->getDailyTrend($startDate, $endDate);
        $doctorStats = $this->getDoctorStats($startDate, $endDate);
        $sourceDistribution = $this->getSourceDistribution($startDate, $endDate);
        $chairUtilization = $this->getChairUtilization($startDate, $endDate);

        return array_merge($summary, compact(
            'peakHours',
            'dailyTrend',
            'doctorStats',
            'sourceDistribution',
            'chairUtilization',
            'startDate',
            'endDate'
        ));
    }

    private function getSummary(Carbon $start, Carbon $end): array
    {
        $base = DB::table('appointments')
            ->whereNull('deleted_at')
            ->whereBetween('start_date', [$start, $end]);

        $total = (clone $base)->count();

        $completed = (clone $base)
            ->whereIn('status', [Appointment::STATUS_TREATMENT_COMPLETE, Appointment::STATUS_COMPLETED])
            ->count();

        $cancelled = (clone $base)
            ->whereIn('status', [Appointment::STATUS_CANCELLED, Appointment::STATUS_RESCHEDULED])
            ->count();

        $noShow = (clone $base)
            ->where(function ($q) {
                $q->where('status', Appointment::STATUS_NO_SHOW)
                  ->orWhere('no_show_count', '>', 0);
            })
            ->count();

        return [
            'totalAppointments' => $total,
            'completedCount' => $completed,
            'cancelledCount' => $cancelled,
            'noShowCount' => $noShow,
            'completionRate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
            'cancellationRate' => $total > 0 ? round(($cancelled / $total) * 100, 1) : 0,
            'noShowRate' => $total > 0 ? round(($noShow / $total) * 100, 1) : 0,
        ];
    }

    private function getPeakHoursDistribution(Carbon $start, Carbon $end): array
    {
        $rows = DB::table('appointments')
            ->selectRaw('HOUR(start_time) as hour, COUNT(*) as count')
            ->whereNull('deleted_at')
            ->whereBetween('start_date', [$start, $end])
            ->whereNotNull('start_time')
            ->groupByRaw('HOUR(start_time)')
            ->orderBy('hour')
            ->get();

        $hours = array_fill(0, 24, 0);
        foreach ($rows as $row) {
            $hours[(int) $row->hour] = $row->count;
        }

        return $hours;
    }

    private function getDailyTrend(Carbon $start, Carbon $end): array
    {
        return DB::table('appointments')
            ->selectRaw('DATE(start_date) as date, COUNT(*) as count')
            ->whereNull('deleted_at')
            ->whereBetween('start_date', [$start, $end])
            ->groupByRaw('DATE(start_date)')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => ['date' => $row->date, 'count' => $row->count])
            ->toArray();
    }

    private function getDoctorStats(Carbon $start, Carbon $end): Collection
    {
        return DB::table('appointments as a')
            ->join('users as u', 'a.doctor_id', '=', 'u.id')
            ->select(
                'u.id as doctor_id',
                'u.surname as doctor_name',
                DB::raw('COUNT(a.id) as total'),
                DB::raw('SUM(CASE WHEN a.status IN ("' . Appointment::STATUS_TREATMENT_COMPLETE . '","' . Appointment::STATUS_COMPLETED . '") THEN 1 ELSE 0 END) as completed'),
                DB::raw('SUM(CASE WHEN a.status IN ("' . Appointment::STATUS_CANCELLED . '","' . Appointment::STATUS_RESCHEDULED . '") THEN 1 ELSE 0 END) as cancelled'),
                DB::raw('SUM(CASE WHEN a.status = "' . Appointment::STATUS_NO_SHOW . '" OR a.no_show_count > 0 THEN 1 ELSE 0 END) as no_show')
            )
            ->whereNull('a.deleted_at')
            ->whereBetween('a.start_date', [$start, $end])
            ->groupBy('u.id', 'u.surname')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) {
                $row->completion_rate = $row->total > 0 ? round(($row->completed / $row->total) * 100, 1) : 0;
                $row->no_show_rate = $row->total > 0 ? round(($row->no_show / $row->total) * 100, 1) : 0;
                return $row;
            });
    }

    private function getSourceDistribution(Carbon $start, Carbon $end): array
    {
        $isZhCN = app()->getLocale() === 'zh-CN';

        $sourceMap = [
            'front_desk' => $isZhCN ? '前台' : 'Front Desk',
            'phone' => $isZhCN ? '电话' : 'Phone',
            'mini_program' => $isZhCN ? '小程序' : 'Mini Program',
            'meituan' => $isZhCN ? '美团' : 'Meituan',
            'dianping' => $isZhCN ? '大众点评' : 'Dianping',
            'walk_in' => $isZhCN ? '到店' : 'Walk-in',
            'online' => $isZhCN ? '线上' : 'Online',
        ];

        return DB::table('appointments')
            ->selectRaw('source, COUNT(*) as count')
            ->whereNull('deleted_at')
            ->whereBetween('start_date', [$start, $end])
            ->whereNotNull('source')
            ->groupBy('source')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'source' => $sourceMap[$row->source] ?? $row->source,
                'count' => $row->count,
            ])
            ->toArray();
    }

    private function getChairUtilization(Carbon $start, Carbon $end): array
    {
        return DB::table('appointments as a')
            ->join('chairs as c', 'a.chair_id', '=', 'c.id')
            ->select('c.chair_name', DB::raw('COUNT(a.id) as count'))
            ->whereNull('a.deleted_at')
            ->whereBetween('a.start_date', [$start, $end])
            ->groupBy('c.id', 'c.chair_name')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => ['chair' => $row->chair_name, 'count' => $row->count])
            ->toArray();
    }
}
