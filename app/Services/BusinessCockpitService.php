<?php

namespace App\Services;

use App\Appointment;
use App\Invoice;
use App\MedicalCaseAmendment;
use App\Refund;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BusinessCockpitService
{
    public function getCockpitData(): array
    {
        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();
        $prevMonthStart = $today->copy()->subMonth()->startOfMonth();
        $prevMonthEnd = $today->copy()->subMonth()->endOfMonth();
        // 上月同期截止日：上月1日到上月的"今天是几号"
        $prevMonthSameDay = $prevMonthStart->copy()->addDays($today->day - 1);
        if ($prevMonthSameDay->gt($prevMonthEnd)) {
            $prevMonthSameDay = $prevMonthEnd->copy();
        }

        return [
            'kpi'             => $this->getKpiCards($today, $monthStart, $prevMonthStart, $prevMonthSameDay),
            'revenueTrend'    => $this->getRevenueTrend(30),
            'paymentMix'      => $this->getPaymentMix($monthStart, $today),
            'completionTrend' => $this->getCompletionTrend(30),
            'doctorRanking'   => $this->getDoctorRanking($monthStart, $today, 5),
            'topServices'     => $this->getTopServices($monthStart, $today, 10),
            'pendingItems'    => $this->getPendingItems(),
        ];
    }

    // ─── KPI Cards ────────────────────────────────────────────────

    private function getKpiCards(Carbon $today, Carbon $monthStart, Carbon $prevMonthStart, Carbon $prevMonthSameDay): array
    {
        $todayStr = $today->toDateString();
        $monthStartStr = $monthStart->toDateString();

        $todayRevenue = $this->sumPayments($todayStr, $todayStr);
        $mtdRevenue = $this->sumPayments($monthStartStr, $todayStr);
        $prevMtdRevenue = $this->sumPayments($prevMonthStart->toDateString(), $prevMonthSameDay->toDateString());

        $todayAppointments = DB::table('appointments')
            ->where('sort_by', $todayStr)
            ->whereNull('deleted_at')
            ->count();

        $todayNewPatients = DB::table('patients')
            ->whereDate('created_at', $todayStr)
            ->whereNull('deleted_at')
            ->count();

        $pendingCount = $this->countPendingItems();

        $receivables = (float) DB::table('invoices')
            ->whereIn('payment_status', [Invoice::PAYMENT_UNPAID, Invoice::PAYMENT_PARTIAL])
            ->whereNull('deleted_at')
            ->sum('outstanding_amount');

        return [
            'today_revenue'      => $todayRevenue,
            'mtd_revenue'        => $mtdRevenue,
            'mtd_revenue_change' => $this->calcChange($mtdRevenue, $prevMtdRevenue),
            'today_appointments' => $todayAppointments,
            'today_new_patients' => $todayNewPatients,
            'pending_count'      => $pendingCount,
            'receivables'        => $receivables,
        ];
    }

    // ─── Revenue Trend (last N days) ──────────────────────────────

    private function getRevenueTrend(int $days): array
    {
        $start = Carbon::today()->subDays($days - 1);
        $end = Carbon::today();

        $rows = DB::table('invoice_payments')
            ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
            ->whereNull('deleted_at')
            ->select('payment_date as date', DB::raw('SUM(amount) as revenue'))
            ->groupBy('payment_date')
            ->orderBy('payment_date')
            ->get()
            ->keyBy('date');

        $result = [];
        $current = $start->copy();
        while ($current->lte($end)) {
            $dateStr = $current->toDateString();
            $result[] = [
                'date'    => $dateStr,
                'revenue' => isset($rows[$dateStr]) ? round($rows[$dateStr]->revenue, 2) : 0,
            ];
            $current->addDay();
        }

        return $result;
    }

    // ─── Payment Method Mix (MTD) ─────────────────────────────────

    private function getPaymentMix(Carbon $start, Carbon $end): Collection
    {
        return DB::table('invoice_payments')
            ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
            ->whereNull('deleted_at')
            ->select('payment_method', DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();
    }

    // ─── Appointment Completion Rate Trend ────────────────────────

    private function getCompletionTrend(int $days): array
    {
        $start = Carbon::today()->subDays($days - 1);
        $end = Carbon::today();

        $rows = DB::table('appointments')
            ->whereBetween('sort_by', [$start->toDateString(), $end->toDateString()])
            ->whereNull('deleted_at')
            ->select(
                DB::raw('DATE(sort_by) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status IN ('" . Appointment::STATUS_COMPLETED . "','" . Appointment::STATUS_CHECKED_IN . "','" . Appointment::STATUS_IN_PROGRESS . "','" . Appointment::STATUS_TREATMENT_COMPLETE . "') THEN 1 ELSE 0 END) as completed")
            )
            ->groupBy(DB::raw('DATE(sort_by)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $result = [];
        $current = $start->copy();
        while ($current->lte($end)) {
            $dateStr = $current->toDateString();
            $row = $rows[$dateStr] ?? null;
            $result[] = [
                'date' => $dateStr,
                'rate' => $row && $row->total > 0 ? round(($row->completed / $row->total) * 100, 1) : 0,
            ];
            $current->addDay();
        }

        return $result;
    }

    // ─── Doctor Revenue Ranking ───────────────────────────────────

    private function getDoctorRanking(Carbon $start, Carbon $end, int $limit): Collection
    {
        return DB::table('invoice_items as ii')
            ->join('invoices as inv', 'ii.invoice_id', '=', 'inv.id')
            ->join('appointments as apt', 'inv.appointment_id', '=', 'apt.id')
            ->join('users as u', 'apt.doctor_id', '=', 'u.id')
            ->whereBetween('inv.created_at', [$start, $end])
            ->whereNull('inv.deleted_at')
            ->whereNull('ii.deleted_at')
            ->select(
                'u.id as doctor_id',
                DB::raw("CONCAT(u.surname, u.othername) as doctor_name"),
                DB::raw('SUM(COALESCE(ii.price, ii.amount) * COALESCE(ii.qty, 1)) as revenue')
            )
            ->groupBy('u.id', 'u.surname', 'u.othername')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get();
    }

    // ─── Top Services ─────────────────────────────────────────────

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

    // ─── Pending Items ────────────────────────────────────────────

    private function getPendingItems(): array
    {
        $items = [];

        // 折扣审批
        $discounts = DB::table('invoices')
            ->where('discount_approval_status', Invoice::DISCOUNT_PENDING)
            ->whereNull('deleted_at')
            ->select('id', 'invoice_no', 'discount_amount')
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get();

        foreach ($discounts as $d) {
            $items[] = [
                'type'        => __('cockpit.discount_approval'),
                'description' => $d->invoice_no . ' — ¥' . number_format($d->discount_amount, 2),
                'url'         => url('invoices/pending-discount-approvals'),
            ];
        }

        // 退费审批
        $refunds = DB::table('refunds')
            ->where('approval_status', Refund::APPROVAL_PENDING)
            ->whereNull('deleted_at')
            ->select('id', 'refund_amount')
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get();

        foreach ($refunds as $r) {
            $items[] = [
                'type'        => __('cockpit.refund_approval'),
                'description' => '#' . $r->id . ' — ¥' . number_format($r->refund_amount, 2),
                'url'         => url('refunds'),
            ];
        }

        // 病历修改审批
        $amendments = DB::table('medical_case_amendments')
            ->where('status', MedicalCaseAmendment::STATUS_PENDING)
            ->select('id', 'medical_case_id')
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get();

        foreach ($amendments as $a) {
            $items[] = [
                'type'        => __('cockpit.amendment_approval'),
                'description' => __('cockpit.medical_case') . ' #' . $a->medical_case_id,
                'url'         => url('medical-cases/' . $a->medical_case_id),
            ];
        }

        return $items;
    }

    private function countPendingItems(): int
    {
        $discounts = DB::table('invoices')
            ->where('discount_approval_status', Invoice::DISCOUNT_PENDING)
            ->whereNull('deleted_at')
            ->count();

        $refunds = DB::table('refunds')
            ->where('approval_status', Refund::APPROVAL_PENDING)
            ->whereNull('deleted_at')
            ->count();

        $amendments = DB::table('medical_case_amendments')
            ->where('status', MedicalCaseAmendment::STATUS_PENDING)
            ->count();

        return $discounts + $refunds + $amendments;
    }

    // ─── Helpers ──────────────────────────────────────────────────

    private function sumPayments(string $startDate, string $endDate): float
    {
        return (float) DB::table('invoice_payments')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->whereNull('deleted_at')
            ->sum('amount');
    }

    private function calcChange(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }
        return round((($current - $previous) / abs($previous)) * 100, 1);
    }
}
