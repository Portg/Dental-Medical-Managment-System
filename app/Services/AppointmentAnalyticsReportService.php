<?php

namespace App\Services;

use App\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AppointmentAnalyticsReportService
{
    public function getReportData(
        ?string $startDateStr,
        ?string $endDateStr,
        ?int    $sourceId = null,
        ?array  $tagIds   = null
    ): array {
        $startDate = $startDateStr ? Carbon::parse($startDateStr) : Carbon::now()->startOfMonth();
        $endDate   = $endDateStr   ? Carbon::parse($endDateStr)   : Carbon::now()->endOfMonth();

        $summary           = $this->getSummary($startDate, $endDate, $sourceId, $tagIds);
        $peakHours         = $this->getPeakHoursDistribution($startDate, $endDate, $sourceId, $tagIds);
        $dailyTrend        = $this->getDailyTrend($startDate, $endDate, $sourceId, $tagIds);
        $doctorStats       = $this->getDoctorStats($startDate, $endDate, $sourceId, $tagIds);
        $sourceDistribution = $this->getSourceDistribution($startDate, $endDate, $tagIds);
        $chairUtilization  = $this->getChairUtilization($startDate, $endDate, $sourceId, $tagIds);

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

    // -------------------------------------------------------------------------
    // Patient filter helper
    // -------------------------------------------------------------------------

    /**
     * Add patient-level filters (source_id, tag_ids) to a DB query builder
     * that already has an 'appointments' table in scope.
     *
     * For DB::table queries we join patients once with a unique alias.
     * The caller must pass the table alias used for the appointments table
     * (default "appointments").
     */
    private function applyPatientFilters(
        $query,
        ?int   $sourceId,
        ?array $tagIds,
        string $appointmentsAlias = 'appointments'
    ) {
        if ($sourceId === null && empty($tagIds)) {
            return $query;
        }

        $query->join(
            'patients as pf',
            'pf.id',
            '=',
            $appointmentsAlias . '.patient_id'
        );

        if ($sourceId !== null) {
            $query->where('pf.source_id', $sourceId);
        }

        if (!empty($tagIds)) {
            $query->whereExists(function ($sub) use ($tagIds) {
                $sub->select(DB::raw(1))
                    ->from('patient_tag_pivot')
                    ->whereColumn('patient_tag_pivot.patient_id', 'pf.id')
                    ->whereIn('patient_tag_pivot.tag_id', $tagIds);
            });
        }

        return $query;
    }

    // -------------------------------------------------------------------------
    // Sub-queries
    // -------------------------------------------------------------------------

    private function getSummary(
        Carbon $start,
        Carbon $end,
        ?int   $sourceId,
        ?array $tagIds
    ): array {
        $base = DB::table('appointments')
            ->whereNull('appointments.deleted_at')
            ->whereBetween('appointments.start_date', [$start, $end]);

        $this->applyPatientFilters($base, $sourceId, $tagIds);

        $total = (clone $base)->count();

        $completed = (clone $base)
            ->whereIn('appointments.status', [Appointment::STATUS_TREATMENT_COMPLETE, Appointment::STATUS_COMPLETED])
            ->count();

        $cancelled = (clone $base)
            ->whereIn('appointments.status', [Appointment::STATUS_CANCELLED, Appointment::STATUS_RESCHEDULED])
            ->count();

        $noShow = (clone $base)
            ->where(function ($q) {
                $q->where('appointments.status', Appointment::STATUS_NO_SHOW)
                  ->orWhere('appointments.no_show_count', '>', 0);
            })
            ->count();

        return [
            'totalAppointments' => $total,
            'completedCount'    => $completed,
            'cancelledCount'    => $cancelled,
            'noShowCount'       => $noShow,
            'completionRate'    => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
            'cancellationRate'  => $total > 0 ? round(($cancelled / $total) * 100, 1) : 0,
            'noShowRate'        => $total > 0 ? round(($noShow / $total) * 100, 1) : 0,
        ];
    }

    private function getPeakHoursDistribution(
        Carbon $start,
        Carbon $end,
        ?int   $sourceId,
        ?array $tagIds
    ): array {
        $query = DB::table('appointments')
            ->selectRaw('HOUR(appointments.start_time) as hour, COUNT(*) as count')
            ->whereNull('appointments.deleted_at')
            ->whereBetween('appointments.start_date', [$start, $end])
            ->whereNotNull('appointments.start_time')
            ->groupByRaw('HOUR(appointments.start_time)')
            ->orderBy('hour');

        $this->applyPatientFilters($query, $sourceId, $tagIds);

        $rows = $query->get();

        $hours = array_fill(0, 24, 0);
        foreach ($rows as $row) {
            $hours[(int) $row->hour] = $row->count;
        }

        return $hours;
    }

    private function getDailyTrend(
        Carbon $start,
        Carbon $end,
        ?int   $sourceId,
        ?array $tagIds
    ): array {
        $query = DB::table('appointments')
            ->selectRaw('DATE(appointments.start_date) as date, COUNT(*) as count')
            ->whereNull('appointments.deleted_at')
            ->whereBetween('appointments.start_date', [$start, $end])
            ->groupByRaw('DATE(appointments.start_date)')
            ->orderBy('date');

        $this->applyPatientFilters($query, $sourceId, $tagIds);

        return $query->get()
            ->map(fn ($row) => ['date' => $row->date, 'count' => $row->count])
            ->toArray();
    }

    private function getDoctorStats(
        Carbon $start,
        Carbon $end,
        ?int   $sourceId,
        ?array $tagIds
    ): Collection {
        $query = DB::table('appointments as a')
            ->join('users as u', 'a.doctor_id', '=', 'u.id')
            ->select(
                'u.id as doctor_id',
                DB::raw('CONCAT(u.surname, u.othername) as doctor_name'),
                DB::raw('COUNT(a.id) as total'),
                DB::raw('SUM(CASE WHEN a.status IN ("' . Appointment::STATUS_TREATMENT_COMPLETE . '","' . Appointment::STATUS_COMPLETED . '") THEN 1 ELSE 0 END) as completed'),
                DB::raw('SUM(CASE WHEN a.status IN ("' . Appointment::STATUS_CANCELLED . '","' . Appointment::STATUS_RESCHEDULED . '") THEN 1 ELSE 0 END) as cancelled'),
                DB::raw('SUM(CASE WHEN a.status = "' . Appointment::STATUS_NO_SHOW . '" OR a.no_show_count > 0 THEN 1 ELSE 0 END) as no_show')
            )
            ->whereNull('a.deleted_at')
            ->whereBetween('a.start_date', [$start, $end])
            ->groupBy('u.id', 'u.surname', 'u.othername')
            ->orderByDesc('total');

        // Apply patient filters using alias 'a' for the appointments table
        if ($sourceId !== null || !empty($tagIds)) {
            $query->join('patients as pf', 'pf.id', '=', 'a.patient_id');

            if ($sourceId !== null) {
                $query->where('pf.source_id', $sourceId);
            }

            if (!empty($tagIds)) {
                $query->whereExists(function ($sub) use ($tagIds) {
                    $sub->select(DB::raw(1))
                        ->from('patient_tag_pivot')
                        ->whereColumn('patient_tag_pivot.patient_id', 'pf.id')
                        ->whereIn('patient_tag_pivot.tag_id', $tagIds);
                });
            }
        }

        return $query->get()
            ->map(function ($row) {
                $row->completion_rate = $row->total > 0 ? round(($row->completed / $row->total) * 100, 1) : 0;
                $row->no_show_rate    = $row->total > 0 ? round(($row->no_show / $row->total) * 100, 1) : 0;
                return $row;
            });
    }

    private function getSourceDistribution(
        Carbon $start,
        Carbon $end,
        ?array $tagIds
    ): array {
        $isZhCN = app()->getLocale() === 'zh-CN';

        $sourceMap = [
            'front_desk'   => $isZhCN ? '前台' : 'Front Desk',
            'phone'        => $isZhCN ? '电话' : 'Phone',
            'mini_program' => $isZhCN ? '小程序' : 'Mini Program',
            'meituan'      => $isZhCN ? '美团' : 'Meituan',
            'dianping'     => $isZhCN ? '大众点评' : 'Dianping',
            'walk_in'      => $isZhCN ? '到店' : 'Walk-in',
            'online'       => $isZhCN ? '线上' : 'Online',
        ];

        $query = DB::table('appointments')
            ->selectRaw('appointments.source, COUNT(*) as count')
            ->whereNull('appointments.deleted_at')
            ->whereBetween('appointments.start_date', [$start, $end])
            ->whereNotNull('appointments.source')
            ->groupBy('appointments.source')
            ->orderByDesc('count');

        // Source distribution: source_id filter is already implicit (we're grouping by source),
        // but tag filter still applies.
        if (!empty($tagIds)) {
            $query->join('patients as pf', 'pf.id', '=', 'appointments.patient_id');
            $query->whereExists(function ($sub) use ($tagIds) {
                $sub->select(DB::raw(1))
                    ->from('patient_tag_pivot')
                    ->whereColumn('patient_tag_pivot.patient_id', 'pf.id')
                    ->whereIn('patient_tag_pivot.tag_id', $tagIds);
            });
        }

        return $query->get()
            ->map(fn ($row) => [
                'source' => $sourceMap[$row->source] ?? $row->source,
                'count'  => $row->count,
            ])
            ->toArray();
    }

    private function getChairUtilization(
        Carbon $start,
        Carbon $end,
        ?int   $sourceId,
        ?array $tagIds
    ): array {
        $query = DB::table('appointments as a')
            ->join('chairs as c', 'a.chair_id', '=', 'c.id')
            ->select('c.chair_name', DB::raw('COUNT(a.id) as count'))
            ->whereNull('a.deleted_at')
            ->whereBetween('a.start_date', [$start, $end])
            ->groupBy('c.id', 'c.chair_name')
            ->orderByDesc('count');

        // Apply patient filters using alias 'a'
        if ($sourceId !== null || !empty($tagIds)) {
            $query->join('patients as pf', 'pf.id', '=', 'a.patient_id');

            if ($sourceId !== null) {
                $query->where('pf.source_id', $sourceId);
            }

            if (!empty($tagIds)) {
                $query->whereExists(function ($sub) use ($tagIds) {
                    $sub->select(DB::raw(1))
                        ->from('patient_tag_pivot')
                        ->whereColumn('patient_tag_pivot.patient_id', 'pf.id')
                        ->whereIn('patient_tag_pivot.tag_id', $tagIds);
                });
            }
        }

        return $query->get()
            ->map(fn ($row) => ['chair' => $row->chair_name, 'count' => $row->count])
            ->toArray();
    }
}
