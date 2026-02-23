<?php

namespace App\Services;

use App\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MonthlyBusinessSummaryReportService
{
    /**
     * Get monthly business summary report data.
     */
    public function getReportData(?string $monthStr): array
    {
        $month = $monthStr ? Carbon::parse($monthStr . '-01') : Carbon::now()->startOfMonth();
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();

        $prevMonthStart = $month->copy()->subMonth()->startOfMonth();
        $prevMonthEnd = $month->copy()->subMonth()->endOfMonth();

        $revenue = $this->getRevenue($monthStart, $monthEnd);
        $prevRevenue = $this->getRevenue($prevMonthStart, $prevMonthEnd);

        $expenses = $this->getExpenses($monthStart, $monthEnd);
        $prevExpenses = $this->getExpenses($prevMonthStart, $prevMonthEnd);

        $grossProfit = $revenue - $expenses;
        $prevGrossProfit = $prevRevenue - $prevExpenses;

        $newPatients = $this->getNewPatients($monthStart, $monthEnd);
        $prevNewPatients = $this->getNewPatients($prevMonthStart, $prevMonthEnd);

        $appointmentStats = $this->getAppointmentStats($monthStart, $monthEnd);
        $prevAppointmentStats = $this->getAppointmentStats($prevMonthStart, $prevMonthEnd);

        $topServices = $this->getTopServices($monthStart, $monthEnd, 10);
        $revenueByDay = $this->getRevenueByDay($monthStart, $monthEnd);

        $summary = [
            'revenue' => $revenue,
            'prev_revenue' => $prevRevenue,
            'revenue_change' => $this->calcChange($revenue, $prevRevenue),
            'expenses' => $expenses,
            'prev_expenses' => $prevExpenses,
            'expenses_change' => $this->calcChange($expenses, $prevExpenses),
            'gross_profit' => $grossProfit,
            'prev_gross_profit' => $prevGrossProfit,
            'gross_profit_change' => $this->calcChange($grossProfit, $prevGrossProfit),
            'new_patients' => $newPatients,
            'prev_new_patients' => $prevNewPatients,
            'new_patients_change' => $this->calcChange($newPatients, $prevNewPatients),
            'appointment_total' => $appointmentStats['total'],
            'appointment_completed' => $appointmentStats['completed'],
            'completion_rate' => $appointmentStats['completion_rate'],
            'prev_completion_rate' => $prevAppointmentStats['completion_rate'],
        ];

        return compact(
            'summary',
            'topServices',
            'revenueByDay',
            'monthStart',
            'monthEnd'
        );
    }

    private function getRevenue(Carbon $start, Carbon $end): float
    {
        return (float) DB::table('invoice_payments')
            ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
            ->whereNull('deleted_at')
            ->sum('amount');
    }

    private function getExpenses(Carbon $start, Carbon $end): float
    {
        return (float) DB::table('expense_payments')
            ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
            ->whereNull('deleted_at')
            ->sum('amount');
    }

    private function getNewPatients(Carbon $start, Carbon $end): int
    {
        return DB::table('patients')
            ->whereBetween('created_at', [$start, $end])
            ->whereNull('deleted_at')
            ->count();
    }

    private function getAppointmentStats(Carbon $start, Carbon $end): array
    {
        $total = DB::table('appointments')
            ->whereBetween('start_date', [$start, $end])
            ->whereNull('deleted_at')
            ->count();

        $completed = DB::table('appointments')
            ->whereBetween('start_date', [$start, $end])
            ->whereIn('status', [Appointment::STATUS_COMPLETED, Appointment::STATUS_CHECKED_IN, Appointment::STATUS_IN_PROGRESS])
            ->whereNull('deleted_at')
            ->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
        ];
    }

    private function getTopServices(Carbon $start, Carbon $end, int $limit): Collection
    {
        return DB::table('invoice_items as ii')
            ->join('invoices as inv', 'ii.invoice_id', '=', 'inv.id')
            ->join('medical_services as ms', 'ii.medical_service_id', '=', 'ms.id')
            ->whereBetween('inv.created_at', [$start, $end])
            ->whereNull('inv.deleted_at')
            ->whereNull('ii.deleted_at')
            ->select(
                'ms.name as service_name',
                DB::raw('SUM(COALESCE(ii.qty, 1)) as total_qty'),
                DB::raw('SUM(COALESCE(ii.price, ii.amount) * COALESCE(ii.qty, 1)) as total_revenue')
            )
            ->groupBy('ms.id', 'ms.name')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get();
    }

    private function getRevenueByDay(Carbon $start, Carbon $end): array
    {
        $rows = DB::table('invoice_payments')
            ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
            ->whereNull('deleted_at')
            ->select(
                'payment_date as date',
                DB::raw('SUM(amount) as revenue')
            )
            ->groupBy('payment_date')
            ->orderBy('payment_date')
            ->get()
            ->keyBy('date');

        $days = [];
        $current = $start->copy();
        while ($current->lte($end)) {
            $dateStr = $current->toDateString();
            $days[] = [
                'date' => $dateStr,
                'revenue' => isset($rows[$dateStr]) ? round($rows[$dateStr]->revenue, 2) : 0,
            ];
            $current->addDay();
        }

        return $days;
    }

    private function calcChange(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }
        return round((($current - $previous) / abs($previous)) * 100, 1);
    }
}
