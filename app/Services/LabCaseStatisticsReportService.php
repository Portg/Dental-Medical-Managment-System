<?php

namespace App\Services;

use App\LabCase;
use Illuminate\Support\Facades\DB;

class LabCaseStatisticsReportService
{
    /**
     * 获取技工单统计报表数据。
     */
    public function getReportData(string $startDate, string $endDate): array
    {
        return [
            'summary'     => $this->getSummary($startDate, $endDate),
            'byStatus'    => $this->byStatus($startDate, $endDate),
            'byLab'       => $this->byLab($startDate, $endDate),
            'byDoctor'    => $this->byDoctor($startDate, $endDate),
            'monthlyTrend'=> $this->monthlyTrend($startDate, $endDate),
        ];
    }

    private function getSummary(string $start, string $end): array
    {
        $completed  = LabCase::STATUS_COMPLETED;
        $rework     = LabCase::STATUS_REWORK;

        $row = DB::table('lab_cases')
            ->whereNull('deleted_at')
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as rework_count,
                AVG(CASE WHEN actual_return_date IS NOT NULL THEN DATEDIFF(actual_return_date, sent_date) ELSE NULL END) as avg_days,
                SUM(lab_fee) as total_lab_fee
            ", [$completed, $rework])
            ->first();

        $completionRate = $row->total > 0 ? round($row->completed / $row->total * 100, 1) : 0;

        return [
            'total'           => $row->total,
            'completed'       => $row->completed,
            'rework_count'    => $row->rework_count,
            'completion_rate' => $completionRate,
            'avg_days'        => $row->avg_days ? round($row->avg_days, 1) : '-',
            'total_lab_fee'   => $row->total_lab_fee ?? 0,
        ];
    }

    private function byStatus(string $start, string $end)
    {
        return DB::table('lab_cases')
            ->whereNull('deleted_at')
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->orderByDesc('count')
            ->get();
    }

    private function byLab(string $start, string $end)
    {
        $completed    = LabCase::STATUS_COMPLETED;
        $unknownLabel = __('lab_cases.unknown_lab');

        return DB::table('lab_cases')
            ->whereNull('lab_cases.deleted_at')
            ->whereDate('lab_cases.created_at', '>=', $start)
            ->whereDate('lab_cases.created_at', '<=', $end)
            ->leftJoin('labs', 'labs.id', '=', 'lab_cases.lab_id')
            ->select(
                DB::raw("COALESCE(labs.name, ?) as lab_name"),
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN lab_cases.status = ? THEN 1 ELSE 0 END) as completed"),
                DB::raw('AVG(lab_cases.lab_fee) as avg_fee'),
                DB::raw('SUM(lab_cases.lab_fee) as total_fee')
            )
            ->addBinding([$unknownLabel, $completed], 'select')
            ->groupBy('lab_cases.lab_id', 'labs.name')
            ->orderByDesc('total')
            ->get();
    }

    private function byDoctor(string $start, string $end)
    {
        $completed       = LabCase::STATUS_COMPLETED;
        $rework          = LabCase::STATUS_REWORK;
        $unassignedLabel = __('lab_cases.unassigned_doctor');

        return DB::table('lab_cases')
            ->whereNull('lab_cases.deleted_at')
            ->whereDate('lab_cases.created_at', '>=', $start)
            ->whereDate('lab_cases.created_at', '<=', $end)
            ->leftJoin('users as doctors', 'doctors.id', '=', 'lab_cases.doctor_id')
            ->select(
                DB::raw("COALESCE(CONCAT(doctors.surname, ' ', doctors.othername), ?) as doctor_name"),
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN lab_cases.status = ? THEN 1 ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN lab_cases.status = ? THEN 1 ELSE 0 END) as rework_count")
            )
            ->addBinding([$unassignedLabel, $completed, $rework], 'select')
            ->groupBy('lab_cases.doctor_id', 'doctors.surname', 'doctors.othername')
            ->orderByDesc('total')
            ->get();
    }

    private function monthlyTrend(string $start, string $end)
    {
        $completed = LabCase::STATUS_COMPLETED;

        return DB::table('lab_cases')
            ->whereNull('deleted_at')
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed")
            )
            ->addBinding([$completed], 'select')
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }
}
