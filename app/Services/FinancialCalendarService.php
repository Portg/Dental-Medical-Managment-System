<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class FinancialCalendarService
{
    /**
     * 获取指定月份每日收支数据，返回 FullCalendar events 格式。
     *
     * AG-043: 收入来源 invoice_payments，支出来源 expense_payments，退款来源 refunds。
     */
    public function getMonthEvents(int $year, int $month): array
    {
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end   = date('Y-m-t', strtotime($start));

        $income   = $this->getDailyIncome($start, $end);
        $expenses = $this->getDailyExpenses($start, $end);
        $refunds  = $this->getDailyRefunds($start, $end);

        // Merge by date
        $days = [];
        foreach ($income as $row) {
            $days[$row->date]['income'] = (float) $row->total;
        }
        foreach ($expenses as $row) {
            $days[$row->date]['expense'] = (float) $row->total;
        }
        foreach ($refunds as $row) {
            $days[$row->date]['refund'] = (float) $row->total;
        }

        $events = [];
        foreach ($days as $date => $values) {
            $inc = $values['income'] ?? 0;
            $exp = $values['expense'] ?? 0;
            $ref = $values['refund'] ?? 0;
            $net = $inc - $exp - $ref;

            if ($inc > 0) {
                $events[] = [
                    'start'           => $date,
                    'title'           => '收 ¥' . number_format($inc, 0),
                    'className'       => 'fc-event-income',
                    'extendedProps'   => ['type' => 'income', 'amount' => $inc],
                ];
            }
            if ($exp > 0) {
                $events[] = [
                    'start'           => $date,
                    'title'           => '支 ¥' . number_format($exp, 0),
                    'className'       => 'fc-event-expense',
                    'extendedProps'   => ['type' => 'expense', 'amount' => $exp],
                ];
            }
            if ($ref > 0) {
                $events[] = [
                    'start'           => $date,
                    'title'           => '退 ¥' . number_format($ref, 0),
                    'className'       => 'fc-event-refund',
                    'extendedProps'   => ['type' => 'refund', 'amount' => $ref],
                ];
            }

            // Net summary event
            $events[] = [
                'start'         => $date,
                'title'         => '净 ¥' . number_format($net, 0),
                'className'     => $net >= 0 ? 'fc-event-net-positive' : 'fc-event-net-negative',
                'extendedProps' => ['type' => 'net', 'amount' => $net],
            ];
        }

        return $events;
    }

    private function getDailyIncome(string $start, string $end)
    {
        return DB::table('invoice_payments')
            ->whereNull('deleted_at')
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(amount) as total'))
            ->groupBy('date')
            ->get();
    }

    private function getDailyExpenses(string $start, string $end)
    {
        return DB::table('expense_payments')
            ->whereNull('deleted_at')
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(amount) as total'))
            ->groupBy('date')
            ->get();
    }

    private function getDailyRefunds(string $start, string $end)
    {
        return DB::table('refunds')
            ->whereNull('deleted_at')
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(amount) as total'))
            ->groupBy('date')
            ->get();
    }
}
