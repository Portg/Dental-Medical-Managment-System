<?php

namespace App\Services;

use App\Appointment;
use App\Charts\MonthlyCashFlows;
use App\Charts\MonthlyExpensesChart;
use App\Charts\MonthlyOverRollIncomeChart;
use App\Charts\MonthlyOverRollIncomeExpenseChart;
use App\ExpensePayment;
use App\InsuranceCompany;
use App\InvoicePayment;
use App\Patient;
use App\User;
use Illuminate\Support\Facades\DB;

class HomeService
{
    /**
     * Get dashboard summary data including counts and charts.
     */
    public function getDashboardData(): array
    {
        $data = [];
        $data['total_patients'] = Patient::count();
        $data['total_users'] = User::count();
        $data['total_insurance_company'] = InsuranceCompany::count();

        $data['today_appointments'] = Appointment::where('start_date', '=', date('Y-m-d'))->count();

        $data['today_cash_amount'] = InvoicePayment::where('payment_method', 'Cash')
            ->whereDate('payment_date', date('Y-m-d'))->sum('amount');

        $data['today_Insurance_amount'] = InvoicePayment::where('payment_method', 'Insurance')
            ->whereDate('payment_date', date('Y-m-d'))->sum('amount');

        $data['today_expense_amount'] = ExpensePayment::whereDate('payment_date', date('Y-m-d'))->sum('amount');

        $data['monthlyCashFlows'] = $this->buildMonthlyCashFlows();
        $data['monthlyExpenses'] = $this->buildMonthlyExpenses();
        $data['monthlyOverRollIncome'] = $this->buildMonthlyOverRollIncomeChart();
        $data['MonthlyOverRollIncomeExpense'] = $this->buildMonthlyOverRollIncomeExpense();

        return $data;
    }

    /**
     * Build the monthly cash flows chart.
     */
    private function buildMonthlyCashFlows(): MonthlyCashFlows
    {
        // Daily cash earnings
        $daily_cash = DB::table('invoice_payments')
            ->select(DB::raw('sum(amount) as cash_amount'), DB::raw('date(payment_date) as dates'))
            ->whereNull('deleted_at')
            ->where('payment_method', 'Cash')
            ->groupBy('dates')
            ->orderBy('dates', 'asc')
            ->get();

        $cashFlows_labels = [];
        $cashFlows_data = [];
        foreach ($daily_cash as $item) {
            $cashFlows_labels[] = $item->dates;
            $cashFlows_data[] = $item->cash_amount;
        }

        // Daily insurance earnings
        $daily_insurance = DB::table('invoice_payments')
            ->select(DB::raw('sum(amount) as insurance_amount'), DB::raw('date(payment_date) as dates'))
            ->whereNull('deleted_at')
            ->where('payment_method', 'Insurance')
            ->groupBy('dates')
            ->orderBy('dates', 'asc')
            ->get();

        $InsuranceFlows_labels = [];
        $InsuranceFlows_data = [];
        foreach ($daily_insurance as $item) {
            $InsuranceFlows_labels[] = $item->dates;
            $InsuranceFlows_data[] = $item->insurance_amount;
        }

        $monthlyCashFlows = new MonthlyCashFlows;
        $monthlyCashFlows->labels($cashFlows_labels);
        $monthlyCashFlows->dataset('Daily Cash Payments', 'line', $cashFlows_data)->options([
            'fill' => false,
        ]);

        $monthlyCashFlows->labels($InsuranceFlows_labels);
        $monthlyCashFlows->dataset('Daily Insurance Payments', 'line', $InsuranceFlows_data)->options([]);

        return $monthlyCashFlows;
    }

    /**
     * Build the monthly expenses chart.
     */
    private function buildMonthlyExpenses(): MonthlyExpensesChart
    {
        $daily_expenses = DB::table('expense_payments')
            ->select(DB::raw('sum(amount) as total_amount'), DB::raw('date(payment_date) as dates'))
            ->whereNull('deleted_at')
            ->groupBy('dates')
            ->orderBy('dates', 'asc')
            ->get();

        $labels = [];
        $expense_data = [];
        foreach ($daily_expenses as $item) {
            $labels[] = $item->dates;
            $expense_data[] = $item->total_amount;
        }

        $monthlyExpenses = new MonthlyExpensesChart;
        $monthlyExpenses->labels($labels);
        $monthlyExpenses->dataset('Daily Expenses', 'line', $expense_data)->options([
            'backgroundColor' => '#DBF2F2',
        ]);

        return $monthlyExpenses;
    }

    /**
     * Build the monthly overall income pie chart.
     */
    private function buildMonthlyOverRollIncomeChart(): MonthlyOverRollIncomeChart
    {
        $monthly_cash = DB::table('invoice_payments')
            ->whereNull('deleted_at')
            ->where('payment_method', 'Cash')
            ->whereRaw('MONTH(created_at) = ?', [date('m')])
            ->sum('amount');

        $monthly_insurance = DB::table('invoice_payments')
            ->select(DB::raw('sum(amount) as insurance_amount'))
            ->whereNull('deleted_at')
            ->where('payment_method', 'Insurance')
            ->whereRaw('MONTH(created_at) = ?', [date('m')])
            ->sum('amount');

        $monthlyOverRollIncomeChart = new MonthlyOverRollIncomeChart;
        $monthlyOverRollIncomeChart->labels(['Cash', 'Insurance']);
        $monthlyOverRollIncomeChart->dataset('Over Roll ', 'pie', [$monthly_cash, $monthly_insurance])->options([
            'backgroundColor' => ['#3598DC', '#78CC66'],
        ]);

        return $monthlyOverRollIncomeChart;
    }

    /**
     * Build the monthly overall income vs expense doughnut chart.
     */
    private function buildMonthlyOverRollIncomeExpense(): MonthlyOverRollIncomeExpenseChart
    {
        $monthly_income = DB::table('invoice_payments')
            ->whereNull('deleted_at')
            ->whereRaw('MONTH(created_at) = ?', [date('m')])
            ->sum('amount');

        $monthly_expenses = DB::table('expense_payments')
            ->select(DB::raw('sum(amount) as insurance_amount'))
            ->whereNull('deleted_at')
            ->whereRaw('MONTH(created_at) = ?', [date('m')])
            ->sum('amount');

        $monthlyOverRollIncomeExpenseChart = new MonthlyOverRollIncomeExpenseChart;
        $monthlyOverRollIncomeExpenseChart->labels(['Income', 'Expenditure']);
        $monthlyOverRollIncomeExpenseChart->dataset('Over Roll ', 'doughnut', [$monthly_income, $monthly_expenses])->options([
            'backgroundColor' => ['rgba(233, 180, 195, 0.86)', 'rgba(215, 201, 15, 0.73)'],
        ]);

        return $monthlyOverRollIncomeExpenseChart;
    }
}
