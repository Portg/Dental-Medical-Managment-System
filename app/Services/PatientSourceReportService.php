<?php

namespace App\Services;

use App\Patient;
use App\PatientSource;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PatientSourceReportService
{
    /**
     * Get complete patient source report data.
     */
    public function getReportData(?string $startDate, ?string $endDate): array
    {
        $start = $startDate ? Carbon::parse($startDate) : Carbon::now()->startOfMonth();
        $end = $endDate ? Carbon::parse($endDate) : Carbon::now()->endOfMonth();

        $sources = PatientSource::where('is_active', true)->get();

        $sourceStats = Patient::select('source_id', DB::raw('COUNT(*) as patient_count'))
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('source_id')
            ->groupBy('source_id')
            ->get()
            ->keyBy('source_id');

        $unknownSourceCount = Patient::whereBetween('created_at', [$start, $end])
            ->whereNull('source_id')
            ->count();

        $totalPatients = Patient::whereBetween('created_at', [$start, $end])->count();

        $monthlyTrend = Patient::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                'source_id',
                DB::raw('COUNT(*) as count')
            )
            ->whereBetween('created_at', [$start->copy()->subMonths(5), $end])
            ->groupBy('month', 'source_id')
            ->orderBy('month')
            ->get();

        $conversionStats = Patient::select('source_id', DB::raw('COUNT(*) as total'))
            ->whereBetween('created_at', [$start, $end])
            ->whereHas('appointments')
            ->whereNotNull('source_id')
            ->groupBy('source_id')
            ->get()
            ->keyBy('source_id');

        $sourceAnalysis = $this->buildSourceAnalysis(
            $sources, $sourceStats, $conversionStats, $totalPatients, $unknownSourceCount
        );

        return compact(
            'sourceAnalysis',
            'totalPatients',
            'monthlyTrend',
            'sources'
        ) + [
            'startDate' => $start,
            'endDate' => $end,
        ];
    }

    /**
     * Build source analysis array from raw stats.
     */
    private function buildSourceAnalysis(
        Collection $sources,
        Collection $sourceStats,
        Collection $conversionStats,
        int $totalPatients,
        int $unknownSourceCount
    ): array {
        $sourceAnalysis = [];

        foreach ($sources as $source) {
            $patientCount = $sourceStats->get($source->id)->patient_count ?? 0;
            $convertedCount = $conversionStats->get($source->id)->total ?? 0;
            $conversionRate = $patientCount > 0 ? round(($convertedCount / $patientCount) * 100, 1) : 0;
            $percentage = $totalPatients > 0 ? round(($patientCount / $totalPatients) * 100, 1) : 0;

            $sourceAnalysis[] = [
                'id' => $source->id,
                'name' => $source->name,
                'color' => $source->color ?? '#3949AB',
                'patient_count' => $patientCount,
                'percentage' => $percentage,
                'converted_count' => $convertedCount,
                'conversion_rate' => $conversionRate,
            ];
        }

        if ($unknownSourceCount > 0) {
            $sourceAnalysis[] = [
                'id' => 0,
                'name' => __('report.unknown_source'),
                'color' => '#9E9E9E',
                'patient_count' => $unknownSourceCount,
                'percentage' => $totalPatients > 0 ? round(($unknownSourceCount / $totalPatients) * 100, 1) : 0,
                'converted_count' => 0,
                'conversion_rate' => 0,
            ];
        }

        usort($sourceAnalysis, function ($a, $b) {
            return $b['patient_count'] - $a['patient_count'];
        });

        return $sourceAnalysis;
    }
}
