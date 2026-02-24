<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TreatmentPlanCompletionReportService
{
    /**
     * Get all treatment plan completion report data.
     */
    public function getReportData(?string $startDateStr, ?string $endDateStr): array
    {
        $startDate = $startDateStr ? Carbon::parse($startDateStr) : Carbon::now()->subMonths(6)->startOfDay();
        $endDate = $endDateStr ? Carbon::parse($endDateStr) : Carbon::now()->endOfDay();

        $summary = $this->getSummary($startDate, $endDate);
        $byServiceCategory = $this->getByServiceCategory($startDate, $endDate);
        $byDoctor = $this->getByDoctor($startDate, $endDate);
        $unconvertedHighValue = $this->getUnconvertedHighValue($startDate, $endDate);
        $monthlyTrend = $this->getMonthlyTrend($startDate, $endDate);

        return compact(
            'summary',
            'byServiceCategory',
            'byDoctor',
            'unconvertedHighValue',
            'monthlyTrend',
            'startDate',
            'endDate'
        );
    }

    /**
     * Summary: total quotations, converted, conversion rate, avg conversion days.
     */
    private function getSummary(Carbon $startDate, Carbon $endDate): array
    {
        $totalQuotations = DB::table('quotations')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->count();

        // A quotation is "converted" if at least one of its items has a matching
        // invoice_item (same patient + same medical_service_id) created after the quotation.
        $convertedData = $this->getConvertedQuotationIds($startDate, $endDate);
        $convertedCount = $convertedData->count();

        $conversionRate = $totalQuotations > 0
            ? round(($convertedCount / $totalQuotations) * 100, 1)
            : 0;

        $totalQuotationAmount = DB::table('quotation_items as qi')
            ->join('quotations as q', 'qi.quotation_id', '=', 'q.id')
            ->whereBetween('q.created_at', [$startDate, $endDate])
            ->whereNull('q.deleted_at')
            ->whereNull('qi.deleted_at')
            ->sum(DB::raw('COALESCE(qi.qty, 1) * qi.amount'));

        // Average conversion days
        $avgConversionDays = 0;
        if ($convertedData->isNotEmpty()) {
            $totalDays = $convertedData->sum('days');
            $avgConversionDays = round($totalDays / $convertedData->count(), 1);
        }

        return [
            'total_quotations' => $totalQuotations,
            'converted_count' => $convertedCount,
            'conversion_rate' => $conversionRate,
            'total_quotation_amount' => round($totalQuotationAmount, 2),
            'avg_conversion_days' => $avgConversionDays,
        ];
    }

    /**
     * Get IDs of converted quotations with conversion days.
     */
    private function getConvertedQuotationIds(Carbon $startDate, Carbon $endDate): Collection
    {
        return DB::table('quotations as q')
            ->join('quotation_items as qi', 'q.id', '=', 'qi.quotation_id')
            ->join('invoices as inv', 'q.patient_id', '=', 'inv.patient_id')
            ->join('invoice_items as ii', function ($join) {
                $join->on('inv.id', '=', 'ii.invoice_id')
                     ->on('qi.medical_service_id', '=', 'ii.medical_service_id');
            })
            ->where('inv.created_at', '>=', DB::raw('q.created_at'))
            ->whereBetween('q.created_at', [$startDate, $endDate])
            ->whereNull('q.deleted_at')
            ->whereNull('qi.deleted_at')
            ->whereNull('inv.deleted_at')
            ->whereNull('ii.deleted_at')
            ->groupBy('q.id', 'q.created_at')
            ->select(
                'q.id',
                DB::raw('MIN(DATEDIFF(inv.created_at, q.created_at)) as days')
            )
            ->get();
    }

    /**
     * Conversion rate by service category.
     */
    private function getByServiceCategory(Carbon $startDate, Carbon $endDate): Collection
    {
        return DB::table('quotation_items as qi')
            ->join('quotations as q', 'qi.quotation_id', '=', 'q.id')
            ->join('medical_services as ms', 'qi.medical_service_id', '=', 'ms.id')
            ->leftJoin('invoice_items as ii', function ($join) {
                $join->on('qi.medical_service_id', '=', 'ii.medical_service_id');
            })
            ->leftJoin('invoices as inv', function ($join) {
                $join->on('ii.invoice_id', '=', 'inv.id')
                     ->on('inv.patient_id', '=', 'q.patient_id')
                     ->whereColumn('inv.created_at', '>=', 'q.created_at')
                     ->whereNull('inv.deleted_at');
            })
            ->whereBetween('q.created_at', [$startDate, $endDate])
            ->whereNull('q.deleted_at')
            ->whereNull('qi.deleted_at')
            ->select(
                DB::raw("COALESCE(ms.category, '" . __('report.uncategorized') . "') as category"),
                'ms.name as service_name',
                DB::raw('COUNT(DISTINCT qi.id) as quoted_count'),
                DB::raw('COUNT(DISTINCT CASE WHEN inv.id IS NOT NULL THEN qi.id END) as converted_count'),
                DB::raw('SUM(COALESCE(qi.qty, 1) * qi.amount) as quoted_amount')
            )
            ->groupBy('ms.category', 'ms.name')
            ->orderByDesc('quoted_count')
            ->limit(20)
            ->get()
            ->map(function ($row) {
                $row->conversion_rate = $row->quoted_count > 0
                    ? round(($row->converted_count / $row->quoted_count) * 100, 1)
                    : 0;
                return $row;
            });
    }

    /**
     * Conversion stats by doctor (who created the quotation).
     */
    private function getByDoctor(Carbon $startDate, Carbon $endDate): Collection
    {
        $convertedSub = DB::table('quotations as q2')
            ->join('quotation_items as qi2', 'q2.id', '=', 'qi2.quotation_id')
            ->join('invoices as inv2', 'q2.patient_id', '=', 'inv2.patient_id')
            ->join('invoice_items as ii2', function ($join) {
                $join->on('inv2.id', '=', 'ii2.invoice_id')
                     ->on('qi2.medical_service_id', '=', 'ii2.medical_service_id');
            })
            ->whereColumn('inv2.created_at', '>=', 'q2.created_at')
            ->whereNull('inv2.deleted_at')
            ->whereNull('ii2.deleted_at')
            ->whereNull('qi2.deleted_at')
            ->select('q2.id')
            ->distinct();

        return DB::table('quotations as q')
            ->join('users as u', 'q._who_added', '=', 'u.id')
            ->leftJoinSub($convertedSub, 'converted', function ($join) {
                $join->on('q.id', '=', 'converted.id');
            })
            ->whereBetween('q.created_at', [$startDate, $endDate])
            ->whereNull('q.deleted_at')
            ->select(
                'u.id as doctor_id',
                'u.surname as doctor_name',
                DB::raw('COUNT(DISTINCT q.id) as total_quotations'),
                DB::raw('COUNT(DISTINCT converted.id) as converted_count')
            )
            ->groupBy('u.id', 'u.surname')
            ->orderByDesc('total_quotations')
            ->get()
            ->map(function ($row) {
                $row->conversion_rate = $row->total_quotations > 0
                    ? round(($row->converted_count / $row->total_quotations) * 100, 1)
                    : 0;
                return $row;
            });
    }

    /**
     * Top unconverted high-value quotations.
     */
    private function getUnconvertedHighValue(Carbon $startDate, Carbon $endDate): Collection
    {
        $convertedIds = $this->getConvertedQuotationIds($startDate, $endDate)->pluck('id');

        return DB::table('quotations as q')
            ->join('users as u', 'q._who_added', '=', 'u.id')
            ->join('patients as p', 'q.patient_id', '=', 'p.id')
            ->leftJoin('quotation_items as qi', function ($join) {
                $join->on('q.id', '=', 'qi.quotation_id')
                     ->whereNull('qi.deleted_at');
            })
            ->whereBetween('q.created_at', [$startDate, $endDate])
            ->whereNull('q.deleted_at')
            ->when($convertedIds->isNotEmpty(), function ($query) use ($convertedIds) {
                $query->whereNotIn('q.id', $convertedIds);
            })
            ->select(
                'q.id',
                'q.quotation_no',
                'q.created_at',
                'p.surname as patient_name',
                'u.surname as doctor_name',
                DB::raw('SUM(COALESCE(qi.qty, 1) * qi.amount) as total_amount')
            )
            ->groupBy('q.id', 'q.quotation_no', 'q.created_at', 'p.surname', 'u.surname')
            ->orderByDesc('total_amount')
            ->limit(15)
            ->get()
            ->map(function ($row) {
                $row->days_since_quoted = Carbon::parse($row->created_at)->diffInDays(now());
                $row->total_amount = round($row->total_amount ?? 0, 2);
                return $row;
            });
    }

    /**
     * Monthly conversion trend.
     */
    private function getMonthlyTrend(Carbon $startDate, Carbon $endDate): array
    {
        $trend = [];
        $current = $startDate->copy()->startOfMonth();
        $end = $endDate->copy()->endOfMonth();

        while ($current->lte($end)) {
            $monthStart = $current->copy()->startOfMonth();
            $monthEnd = $current->copy()->endOfMonth();

            $total = DB::table('quotations')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->whereNull('deleted_at')
                ->count();

            $converted = $this->getConvertedQuotationIds($monthStart, $monthEnd)->count();

            $rate = $total > 0 ? round(($converted / $total) * 100, 1) : 0;

            $trend[] = [
                'month' => $monthStart->format('Y-m'),
                'total' => $total,
                'converted' => $converted,
                'rate' => $rate,
            ];

            $current->addMonth();
        }

        return $trend;
    }
}
