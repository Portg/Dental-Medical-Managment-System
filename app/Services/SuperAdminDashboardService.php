<?php

namespace App\Services;

use App\Appointment;
use App\Charts\MonthlyCashFlows;
use App\Charts\MonthlyExpensesChart;
use App\Charts\MonthlyOverRollIncomeChart;
use App\Charts\MonthlyOverRollIncomeExpenseChart;
use App\ExpensePayment;
use App\Invoice;
use App\InvoicePayment;
use Illuminate\Support\Facades\DB;

class SuperAdminDashboardService
{
    /**
     * Get all dashboard data for the super admin.
     */
    public function getDashboardData(): array
    {
        return [
            'today_appointments' => Appointment::today()->count(),
            'today_cash_amount' => InvoicePayment::where('payment_method', 'Cash')
                ->whereDate('payment_date', date('Y-m-d'))->sum('amount'),
            'pending_receivable_amount' => Invoice::whereIn('payment_status', [Invoice::PAYMENT_UNPAID, Invoice::PAYMENT_PARTIAL])
                ->sum('outstanding_amount'),
            'today_expense_amount' => ExpensePayment::whereDate('payment_date', date('Y-m-d'))->sum('amount'),
            'monthlyCashFlows' => $this->buildMonthlyCashFlowsChart(),
            'monthlyExpenses' => $this->buildMonthlyExpensesChart(),
            'monthlyOverRollIncome' => $this->buildMonthlyOverRollIncomeChart(),
            'MonthlyOverRollIncomeExpense' => $this->buildMonthlyOverRollIncomeExpenseChart(),
        ];
    }

    /**
     * Build monthly cash flows line chart (current month only).
     */
    private function buildMonthlyCashFlowsChart(): MonthlyCashFlows
    {
        $currentMonth = date('m');
        $currentYear = date('Y');
        $monthFilter = function ($query) use ($currentMonth, $currentYear) {
            $query->whereNull('deleted_at')
                ->whereRaw('MONTH(payment_date) = ? AND YEAR(payment_date) = ?', [$currentMonth, $currentYear]);
        };

        $dailyCash = DB::table('invoice_payments')
            ->select(DB::raw('sum(amount) as total'), DB::raw('date(payment_date) as dates'))
            ->where('payment_method', 'Cash')
            ->tap($monthFilter)
            ->groupBy('dates')
            ->orderBy('dates', 'asc')
            ->get();

        $dailyNonCash = DB::table('invoice_payments')
            ->select(DB::raw('sum(amount) as total'), DB::raw('date(payment_date) as dates'))
            ->where('payment_method', '!=', 'Cash')
            ->tap($monthFilter)
            ->groupBy('dates')
            ->orderBy('dates', 'asc')
            ->get();

        $chart = new MonthlyCashFlows;
        $chart->labels($dailyCash->pluck('dates')->toArray());
        $chart->dataset(__('dashboard.cash_payments'), 'line', $dailyCash->pluck('total')->toArray())->options([
            'fill' => false,
        ]);
        $chart->labels($dailyNonCash->pluck('dates')->toArray());
        $chart->dataset(__('dashboard.non_cash_payments'), 'line', $dailyNonCash->pluck('total')->toArray())->options([]);

        return $chart;
    }

    /**
     * Build monthly expenses line chart (current month only).
     */
    private function buildMonthlyExpensesChart(): MonthlyExpensesChart
    {
        $currentMonth = date('m');
        $currentYear = date('Y');

        $dailyExpenses = DB::table('expense_payments')
            ->select(DB::raw('sum(amount) as total_amount'), DB::raw('date(payment_date) as dates'))
            ->whereNull('deleted_at')
            ->whereRaw('MONTH(payment_date) = ? AND YEAR(payment_date) = ?', [$currentMonth, $currentYear])
            ->groupBy('dates')
            ->orderBy('dates', 'asc')
            ->get();

        $labels = [];
        $expenseData = [];
        foreach ($dailyExpenses as $item) {
            $labels[] = $item->dates;
            $expenseData[] = $item->total_amount;
        }

        $chart = new MonthlyExpensesChart;
        $chart->labels($labels);
        $chart->dataset(__('dashboard.expense'), 'line', $expenseData)->options([
            'backgroundColor' => '#DBF2F2',
        ]);

        return $chart;
    }

    /**
     * Build monthly overall income pie chart (current month only).
     */
    private function buildMonthlyOverRollIncomeChart(): MonthlyOverRollIncomeChart
    {
        $currentMonth = date('m');
        $currentYear = date('Y');

        $methodTotals = DB::table('invoice_payments')
            ->select('payment_method', DB::raw('sum(amount) as total'))
            ->whereNull('deleted_at')
            ->whereRaw('MONTH(payment_date) = ? AND YEAR(payment_date) = ?', [$currentMonth, $currentYear])
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method');

        $colorMap = [
            'Cash' => '#3598DC',
            'WeChat' => '#09BB07',
            'Alipay' => '#1677FF',
            'BankCard' => '#F5A623',
            'StoredValue' => '#8E44AD',
            'Credit' => '#E74C3C',
        ];
        $labelMap = [
            'Cash' => __('invoices.cash'),
            'WeChat' => __('invoices.wechat_pay'),
            'Alipay' => __('invoices.alipay'),
            'BankCard' => __('invoices.bank_card'),
            'StoredValue' => __('invoices.stored_value'),
            'Credit' => __('invoices.credit'),
        ];

        $labels = [];
        $data = [];
        $bgColors = [];
        foreach ($methodTotals as $method => $total) {
            $labels[] = $labelMap[$method] ?? $method;
            $data[] = $total;
            $bgColors[] = $colorMap[$method] ?? '#BDC3C7';
        }

        $chart = new MonthlyOverRollIncomeChart;
        $chart->labels($labels);
        $chart->dataset(__('dashboard.income_by_payment_method'), 'pie', $data)->options([
            'backgroundColor' => $bgColors,
        ]);

        return $chart;
    }

    /**
     * Build monthly overall income vs expense bar chart (current month only).
     */
    private function buildMonthlyOverRollIncomeExpenseChart(): MonthlyOverRollIncomeExpenseChart
    {
        $currentMonth = date('m');
        $currentYear = date('Y');

        $monthlyIncome = DB::table('invoice_payments')
            ->whereNull('deleted_at')
            ->whereRaw('MONTH(payment_date) = ? AND YEAR(payment_date) = ?', [$currentMonth, $currentYear])
            ->sum('amount');

        $monthlyExpenses = DB::table('expense_payments')
            ->whereNull('deleted_at')
            ->whereRaw('MONTH(payment_date) = ? AND YEAR(payment_date) = ?', [$currentMonth, $currentYear])
            ->sum('amount');

        $chart = new MonthlyOverRollIncomeExpenseChart;
        $chart->labels([__('dashboard.income'), __('dashboard.expense')]);
        $chart->dataset(__('dashboard.income_vs_expense'), 'bar', [$monthlyIncome, $monthlyExpenses])->options([
            'backgroundColor' => ['#3598DC', '#E9B4C3'],
        ]);

        return $chart;
    }
}
