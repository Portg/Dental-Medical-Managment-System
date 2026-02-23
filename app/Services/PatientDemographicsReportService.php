<?php

namespace App\Services;

use App\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PatientDemographicsReportService
{
    /**
     * Get patient demographics report data.
     */
    public function getReportData(): array
    {
        $ageDistribution = $this->getAgeDistribution();
        $genderDistribution = $this->getGenderDistribution();
        $newPatientTrend = $this->getNewPatientMonthlyTrend(12);
        $activeVsLost = $this->getActiveVsLost(90);
        $topSpenders = $this->getTopSpenders(20);
        $sourceDistribution = $this->getSourceDistribution();
        $totalPatients = DB::table('patients')->whereNull('deleted_at')->count();

        return compact(
            'totalPatients',
            'ageDistribution',
            'genderDistribution',
            'newPatientTrend',
            'activeVsLost',
            'topSpenders',
            'sourceDistribution'
        );
    }

    private function getAgeDistribution(): array
    {
        $ranges = [
            ['label' => '0-17', 'min' => 0, 'max' => 17],
            ['label' => '18-25', 'min' => 18, 'max' => 25],
            ['label' => '26-35', 'min' => 26, 'max' => 35],
            ['label' => '36-45', 'min' => 36, 'max' => 45],
            ['label' => '46-55', 'min' => 46, 'max' => 55],
            ['label' => '56-65', 'min' => 56, 'max' => 65],
            ['label' => '65+', 'min' => 66, 'max' => 200],
        ];

        $result = [];
        foreach ($ranges as $range) {
            $count = DB::table('patients')
                ->whereNull('deleted_at')
                ->whereNotNull('date_of_birth')
                ->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN ? AND ?', [$range['min'], $range['max']])
                ->count();

            $result[] = [
                'label' => $range['label'],
                'count' => $count,
            ];
        }

        // Unknown age
        $unknownCount = DB::table('patients')
            ->whereNull('deleted_at')
            ->whereNull('date_of_birth')
            ->count();

        if ($unknownCount > 0) {
            $result[] = ['label' => __('report.unknown'), 'count' => $unknownCount];
        }

        return $result;
    }

    private function getGenderDistribution(): array
    {
        return DB::table('patients')
            ->whereNull('deleted_at')
            ->select('gender', DB::raw('COUNT(*) as count'))
            ->groupBy('gender')
            ->get()
            ->map(function ($row) {
                $label = match (strtolower($row->gender ?? '')) {
                    'male', 'm' => __('report.male'),
                    'female', 'f' => __('report.female'),
                    default => __('report.unknown'),
                };
                return ['label' => $label, 'count' => $row->count];
            })
            ->toArray();
    }

    private function getNewPatientMonthlyTrend(int $months): array
    {
        $trend = [];
        $now = Carbon::now();

        for ($i = $months - 1; $i >= 0; $i--) {
            $monthStart = $now->copy()->subMonths($i)->startOfMonth();
            $monthEnd = $now->copy()->subMonths($i)->endOfMonth();

            $count = DB::table('patients')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->whereNull('deleted_at')
                ->count();

            $trend[] = [
                'month' => $monthStart->format('Y-m'),
                'count' => $count,
            ];
        }

        return $trend;
    }

    private function getActiveVsLost(int $lostDays): array
    {
        $cutoff = Carbon::now()->subDays($lostDays);

        $activePatients = DB::table('patients as p')
            ->whereExists(function ($query) use ($cutoff) {
                $query->select(DB::raw(1))
                    ->from('appointments as a')
                    ->whereColumn('a.patient_id', 'p.id')
                    ->where('a.start_date', '>=', $cutoff)
                    ->whereIn('a.status', [Appointment::STATUS_COMPLETED, Appointment::STATUS_CHECKED_IN, Appointment::STATUS_IN_PROGRESS])
                    ->whereNull('a.deleted_at');
            })
            ->whereNull('p.deleted_at')
            ->count();

        $totalWithAppointments = DB::table('patients as p')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('appointments as a')
                    ->whereColumn('a.patient_id', 'p.id')
                    ->whereIn('a.status', [Appointment::STATUS_COMPLETED, Appointment::STATUS_CHECKED_IN, Appointment::STATUS_IN_PROGRESS])
                    ->whereNull('a.deleted_at');
            })
            ->whereNull('p.deleted_at')
            ->count();

        $lostPatients = $totalWithAppointments - $activePatients;

        return [
            'active' => $activePatients,
            'lost' => $lostPatients,
            'total_with_visits' => $totalWithAppointments,
            'active_rate' => $totalWithAppointments > 0 ? round(($activePatients / $totalWithAppointments) * 100, 1) : 0,
        ];
    }

    private function getTopSpenders(int $limit): Collection
    {
        return DB::table('patients as p')
            ->join('invoices as inv', 'p.id', '=', 'inv.patient_id')
            ->whereNull('p.deleted_at')
            ->whereNull('inv.deleted_at')
            ->select(
                'p.id',
                'p.surname as patient_name',
                'p.gender',
                DB::raw('SUM(inv.total_amount) as total_spent'),
                DB::raw('COUNT(inv.id) as invoice_count'),
                DB::raw('MAX(inv.created_at) as last_invoice_date')
            )
            ->groupBy('p.id', 'p.surname', 'p.gender')
            ->orderByDesc('total_spent')
            ->limit($limit)
            ->get();
    }

    private function getSourceDistribution(): Collection
    {
        return DB::table('patients as p')
            ->leftJoin('patient_sources as ps', 'p.source_id', '=', 'ps.id')
            ->whereNull('p.deleted_at')
            ->select(
                DB::raw("COALESCE(ps.name, '" . __('report.unknown') . "') as source"),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('ps.name')
            ->orderByDesc('count')
            ->get();
    }
}
