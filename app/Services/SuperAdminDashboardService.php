<?php

namespace App\Services;

use App\Appointment;
use App\Charts\MonthlyCashFlows;
use App\Charts\MonthlyExpensesChart;
use App\Charts\MonthlyOverRollIncomeChart;
use App\Charts\MonthlyOverRollIncomeExpenseChart;
use App\ExpensePayment;
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
            'today_appointments' => Appointment::where('start_date', '=', date('Y-m-d'))->count(),
            'today_cash_amount' => InvoicePayment::where('payment_method', 'Cash')
                ->whereDate('payment_date', date('Y-m-d'))->sum('amount'),
            'today_Insurance_amount' => InvoicePayment::where('payment_method', 'Insurance')
                ->whereDate('payment_date', date('Y-m-d'))->sum('amount'),
            'today_expense_amount' => ExpensePayment::whereDate('payment_date', date('Y-m-d'))->sum('amount'),
            'monthlyCashFlows' => $this->buildMonthlyCashFlowsChart(),
            'monthlyExpenses' => $this->buildMonthlyExpensesChart(),
            'monthlyOverRollIncome' => $this->buildMonthlyOverRollIncomeChart(),
            'MonthlyOverRollIncomeExpense' => $this->buildMonthlyOverRollIncomeExpenseChart(),
        ];
    }

    /**
     * Build monthly cash flows line chart.
     */
    private function buildMonthlyCashFlowsChart(): MonthlyCashFlows
    {
        $dailyCash = DB::table('invoice_payments')
            ->select(DB::raw('sum(amount) as cash_amount'), DB::raw('date(payment_date) as dates'))
            ->whereNull('deleted_at')
            ->where('payment_method', 'Cash')
            ->groupBy('dates')
            ->orderBy('dates', 'asc')
            ->get();

        $cashLabels = [];
        $cashData = [];
        foreach ($dailyCash as $item) {
            $cashLabels[] = $item->dates;
            $cashData[] = $item->cash_amount;
        }

        $dailyInsurance = DB::table('invoice_payments')
            ->select(DB::raw('sum(amount) as insurance_amount'), DB::raw('date(payment_date) as dates'))
            ->whereNull('deleted_at')
            ->where('payment_method', 'Insurance')
            ->groupBy('dates')
            ->orderBy('dates', 'asc')
            ->get();

        $insuranceLabels = [];
        $insuranceData = [];
        foreach ($dailyInsurance as $item) {
            $insuranceLabels[] = $item->dates;
            $insuranceData[] = $item->insurance_amount;
        }

        $chart = new MonthlyCashFlows;
        $chart->labels($cashLabels);
        $chart->dataset(__('report.daily_cash_payments'), 'line', $cashData)->options([
            'fill' => false,
        ]);
        $chart->labels($insuranceLabels);
        $chart->dataset(__('report.daily_insurance_payments'), 'line', $insuranceData)->options([]);

        return $chart;
    }

    /**
     * Build monthly expenses line chart.
     */
    private function buildMonthlyExpensesChart(): MonthlyExpensesChart
    {
        $dailyExpenses = DB::table('expense_payments')
            ->select(DB::raw('sum(amount) as total_amount'), DB::raw('date(payment_date) as dates'))
            ->whereNull('deleted_at')
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
        $chart->dataset(__('report.daily_expenses'), 'line', $expenseData)->options([
            'backgroundColor' => '#DBF2F2',
        ]);

        return $chart;
    }

    /**
     * Build monthly overall income pie chart.
     */
    private function buildMonthlyOverRollIncomeChart(): MonthlyOverRollIncomeChart
    {
        $monthlyCash = DB::table('invoice_payments')
            ->whereNull('deleted_at')
            ->where('payment_method', 'Cash')
            ->whereRaw('MONTH(created_at) = ?', [date('m')])
            ->sum('amount');

        $monthlyInsurance = DB::table('invoice_payments')
            ->whereNull('deleted_at')
            ->where('payment_method', 'Insurance')
            ->whereRaw('MONTH(created_at) = ?', [date('m')])
            ->sum('amount');

        $chart = new MonthlyOverRollIncomeChart;
        $chart->labels([__('report.cash'), __('report.insurance')]);
        $chart->dataset(__('report.over_roll'), 'pie', [$monthlyCash, $monthlyInsurance])->options([
            'backgroundColor' => ['#3598DC', '#78CC66'],
        ]);

        return $chart;
    }

    /**
     * Build monthly overall income vs expense doughnut chart.
     */
    private function buildMonthlyOverRollIncomeExpenseChart(): MonthlyOverRollIncomeExpenseChart
    {
        $monthlyIncome = DB::table('invoice_payments')
            ->whereNull('deleted_at')
            ->whereRaw('MONTH(created_at) = ?', [date('m')])
            ->sum('amount');

        $monthlyExpenses = DB::table('expense_payments')
            ->whereNull('deleted_at')
            ->whereRaw('MONTH(created_at) = ?', [date('m')])
            ->sum('amount');

        $chart = new MonthlyOverRollIncomeExpenseChart;
        $chart->labels([__('report.income'), __('report.expenditure')]);
        $chart->dataset(__('report.over_roll'), 'doughnut', [$monthlyIncome, $monthlyExpenses])->options([
            'backgroundColor' => ['rgba(233, 180, 195, 0.86)', 'rgba(215, 201, 15, 0.73)'],
        ]);

        return $chart;
    }
}
