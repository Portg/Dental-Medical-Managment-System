<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QuotationConversionReportService
{
    /**
     * Get quotation conversion report data.
     */
    public function getReportData(?string $startDateStr, ?string $endDateStr): array
    {
        $startDate = $startDateStr ? Carbon::parse($startDateStr) : Carbon::now()->subMonths(6)->startOfDay();
        $endDate = $endDateStr ? Carbon::parse($endDateStr) : Carbon::now()->endOfDay();

        $summary = $this->getSummary($startDate, $endDate);
        $byDoctor = $this->getByDoctor($startDate, $endDate);
        $monthlyTrend = $this->getMonthlyTrend($startDate, $endDate);
        $unconvertedList = $this->getUnconvertedList($startDate, $endDate, 20);

        return compact(
            'summary',
            'byDoctor',
            'monthlyTrend',
            'unconvertedList',
            'startDate',
            'endDate'
        );
    }

    private function getSummary(Carbon $startDate, Carbon $endDate): array
    {
        $totalQuoted = DB::table('quotations')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->count();

        $convertedIds = $this->getConvertedIds($startDate, $endDate);
        $convertedCount = $convertedIds->count();

        $conversionRate = $totalQuoted > 0
            ? round(($convertedCount / $totalQuoted) * 100, 1)
            : 0;

        // Average quoted amount
        $avgQuotedAmount = DB::query()
            ->fromSub(
                DB::table('quotation_items as qi')
                    ->join('quotations as q', 'qi.quotation_id', '=', 'q.id')
                    ->whereBetween('q.created_at', [$startDate, $endDate])
                    ->whereNull('q.deleted_at')
                    ->whereNull('qi.deleted_at')
                    ->select('q.id', DB::raw('SUM(COALESCE(qi.qty, 1) * qi.amount) as total'))
                    ->groupBy('q.id'),
                'sub'
            )
            ->avg('sub.total') ?? 0;

        // Average actual invoice amount for converted quotations
        $avgInvoiceAmount = 0;
        if ($convertedIds->isNotEmpty()) {
            $avgInvoiceAmount = DB::table('invoices')
                ->whereIn('patient_id', function ($query) use ($convertedIds) {
                    $query->select('patient_id')
                        ->from('quotations')
                        ->whereIn('id', $convertedIds);
                })
                ->whereBetween('created_at', [$startDate, $endDate->copy()->addMonths(3)])
                ->whereNull('deleted_at')
                ->avg('total_amount') ?? 0;
        }

        return [
            'total_quoted' => $totalQuoted,
            'converted_count' => $convertedCount,
            'conversion_rate' => $conversionRate,
            'avg_quoted_amount' => round($avgQuotedAmount, 2),
            'avg_invoice_amount' => round($avgInvoiceAmount, 2),
        ];
    }

    private function getConvertedIds(Carbon $startDate, Carbon $endDate): Collection
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
            ->distinct()
            ->pluck('q.id');
    }

    private function getByDoctor(Carbon $startDate, Carbon $endDate): Collection
    {
        $convertedSub = $this->buildConvertedSubquery($startDate, $endDate);

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
     * Build a subquery returning distinct converted quotation IDs (for use with leftJoinSub).
     */
    private function buildConvertedSubquery(Carbon $startDate, Carbon $endDate)
    {
        return DB::table('quotations as q2')
            ->join('quotation_items as qi2', 'q2.id', '=', 'qi2.quotation_id')
            ->join('invoices as inv2', 'q2.patient_id', '=', 'inv2.patient_id')
            ->join('invoice_items as ii2', function ($join) {
                $join->on('inv2.id', '=', 'ii2.invoice_id')
                     ->on('qi2.medical_service_id', '=', 'ii2.medical_service_id');
            })
            ->whereColumn('inv2.created_at', '>=', 'q2.created_at')
            ->whereBetween('q2.created_at', [$startDate, $endDate])
            ->whereNull('q2.deleted_at')
            ->whereNull('qi2.deleted_at')
            ->whereNull('inv2.deleted_at')
            ->whereNull('ii2.deleted_at')
            ->select('q2.id')
            ->distinct();
    }

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

            $converted = $this->getConvertedIds($monthStart, $monthEnd)->count();
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

    private function getUnconvertedList(Carbon $startDate, Carbon $endDate, int $limit): Collection
    {
        $convertedIds = $this->getConvertedIds($startDate, $endDate);

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
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $row->days_since = Carbon::parse($row->created_at)->diffInDays(now());
                $row->total_amount = round($row->total_amount ?? 0, 2);
                return $row;
            });
    }
}
