<?php

namespace App\Services;

use App\Appointment;
use App\Patient;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RevisitRateReportService
{
    /**
     * Get all revisit rate report data.
     */
    public function getReportData(?string $startDateStr, ?string $endDateStr): array
    {
        $startDate = $startDateStr ? Carbon::parse($startDateStr) : Carbon::now()->startOfMonth();
        $endDate = $endDateStr ? Carbon::parse($endDateStr) : Carbon::now()->endOfMonth();

        $currentPeriodPatients = Appointment::whereBetween('start_date', [$startDate, $endDate])
            ->whereIn('status', ['completed', 'checked_in', 'in_progress'])
            ->distinct('patient_id')
            ->count('patient_id');

        $firstVisitPatients = $this->getFirstVisitPatients($startDate, $endDate);

        $revisitPatients = $currentPeriodPatients - $firstVisitPatients;

        $revisitRate = $currentPeriodPatients > 0
            ? round(($revisitPatients / $currentPeriodPatients) * 100, 1)
            : 0;

        $monthlyTrend = $this->getMonthlyRevisitTrend(6);

        $doctorStats = $this->getDoctorRevisitStats($startDate, $endDate);

        $intervalDistribution = $this->getRevisitIntervalDistribution($startDate, $endDate);

        $lostPatients = $this->getLostPatients(90);

        return compact(
            'currentPeriodPatients',
            'firstVisitPatients',
            'revisitPatients',
            'revisitRate',
            'monthlyTrend',
            'doctorStats',
            'intervalDistribution',
            'lostPatients',
            'startDate',
            'endDate'
        );
    }

    /**
     * Get first-visit patient count for the given period.
     */
    private function getFirstVisitPatients(Carbon $startDate, Carbon $endDate): int
    {
        return Patient::whereHas('appointments', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->whereIn('status', ['completed', 'checked_in', 'in_progress']);
            })
            ->whereDoesntHave('appointments', function ($query) use ($startDate) {
                $query->where('start_date', '<', $startDate)
                    ->whereIn('status', ['completed', 'checked_in', 'in_progress']);
            })
            ->count();
    }

    /**
     * Get monthly revisit trend for the past N months.
     */
    private function getMonthlyRevisitTrend(int $months): array
    {
        $trend = [];
        $now = Carbon::now();

        for ($i = $months - 1; $i >= 0; $i--) {
            $monthStart = $now->copy()->subMonths($i)->startOfMonth();
            $monthEnd = $now->copy()->subMonths($i)->endOfMonth();

            $totalPatients = Appointment::whereBetween('start_date', [$monthStart, $monthEnd])
                ->whereIn('status', ['completed', 'checked_in', 'in_progress'])
                ->distinct('patient_id')
                ->count('patient_id');

            $firstVisitPatients = $this->getFirstVisitPatients($monthStart, $monthEnd);
            $revisitPatients = $totalPatients - $firstVisitPatients;
            $revisitRate = $totalPatients > 0 ? round(($revisitPatients / $totalPatients) * 100, 1) : 0;

            $monthLabel = $monthStart->format('Y-m');
            if (app()->getLocale() == 'zh-CN') {
                $monthLabel = $monthStart->format('Y') . '年' . $monthStart->format('n') . '月';
            } else {
                $monthLabel = $monthStart->format('M Y');
            }

            $trend[] = [
                'month' => $monthStart->format('Y-m'),
                'month_label' => $monthLabel,
                'total_patients' => $totalPatients,
                'first_visit' => $firstVisitPatients,
                'revisit' => $revisitPatients,
                'revisit_rate' => $revisitRate,
            ];
        }

        return $trend;
    }

    /**
     * Get per-doctor revisit stats.
     */
    private function getDoctorRevisitStats(Carbon $startDate, Carbon $endDate): Collection
    {
        return DB::table('appointments as a')
            ->join('users as u', 'a.doctor_id', '=', 'u.id')
            ->select(
                'u.id as doctor_id',
                'u.surname as doctor_name',
                DB::raw('COUNT(DISTINCT a.patient_id) as total_patients'),
                DB::raw('COUNT(a.id) as total_appointments')
            )
            ->whereBetween('a.start_date', [$startDate, $endDate])
            ->whereIn('a.status', ['completed', 'checked_in', 'in_progress'])
            ->whereNull('a.deleted_at')
            ->groupBy('u.id', 'u.surname')
            ->orderByDesc('total_patients')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $avgVisits = $row->total_patients > 0
                    ? round($row->total_appointments / $row->total_patients, 2)
                    : 0;

                $row->avg_visits_per_patient = $avgVisits;
                return $row;
            });
    }

    /**
     * Get revisit interval distribution.
     */
    private function getRevisitIntervalDistribution(Carbon $startDate, Carbon $endDate): array
    {
        return [
            ['label' => __('report.interval_within_7_days'), 'range' => '0-7', 'count' => $this->countRevisitsByInterval(0, 7, $startDate, $endDate)],
            ['label' => __('report.interval_8_14_days'), 'range' => '8-14', 'count' => $this->countRevisitsByInterval(8, 14, $startDate, $endDate)],
            ['label' => __('report.interval_15_30_days'), 'range' => '15-30', 'count' => $this->countRevisitsByInterval(15, 30, $startDate, $endDate)],
            ['label' => __('report.interval_31_60_days'), 'range' => '31-60', 'count' => $this->countRevisitsByInterval(31, 60, $startDate, $endDate)],
            ['label' => __('report.interval_61_90_days'), 'range' => '61-90', 'count' => $this->countRevisitsByInterval(61, 90, $startDate, $endDate)],
            ['label' => __('report.interval_over_90_days'), 'range' => '90+', 'count' => $this->countRevisitsByInterval(91, 365, $startDate, $endDate)],
        ];
    }

    /**
     * Count revisits by interval range (placeholder implementation).
     */
    private function countRevisitsByInterval(int $minDays, int $maxDays, Carbon $startDate, Carbon $endDate): int
    {
        return 0; // Placeholder - needs actual implementation
    }

    /**
     * Get lost patients (no visit in N days).
     */
    private function getLostPatients(int $days): Collection
    {
        $cutoffDate = Carbon::now()->subDays($days);

        return Patient::select('patients.*')
            ->join(DB::raw('(SELECT patient_id, MAX(start_date) as last_visit FROM appointments WHERE status IN ("completed", "checked_in", "in_progress") AND deleted_at IS NULL GROUP BY patient_id) as last_appointments'),
                'patients.id', '=', 'last_appointments.patient_id')
            ->where('last_appointments.last_visit', '<', $cutoffDate)
            ->orderBy('last_appointments.last_visit', 'asc')
            ->limit(20)
            ->get()
            ->map(function ($patient) {
                $lastAppointment = Appointment::where('patient_id', $patient->id)
                    ->whereIn('status', ['completed', 'checked_in', 'in_progress'])
                    ->orderBy('start_date', 'desc')
                    ->first();

                $patient->last_visit_date = $lastAppointment ? $lastAppointment->start_date : null;
                $patient->days_since_visit = $lastAppointment
                    ? Carbon::parse($lastAppointment->start_date)->diffInDays(now())
                    : null;

                return $patient;
            });
    }
}
