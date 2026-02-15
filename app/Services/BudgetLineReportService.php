<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BudgetLineReportService
{
    /**
     * Get budget line report data filtered by date range.
     */
    public function getBudgetLineData(string $startDate, string $endDate): Collection
    {
        return DB::table('expense_items')
            ->join('expense_categories', 'expense_categories.id', 'expense_items.expense_category_id')
            ->join('chart_of_account_items', 'chart_of_account_items.id', 'expense_categories.chart_of_account_item_id')
            ->whereNull('expense_items.deleted_at')
            ->whereBetween(DB::raw('DATE_FORMAT(expense_items.created_at, \'%Y-%m-%d\')'), [$startDate, $endDate])
            ->select(
                'expense_items.*',
                DB::raw('sum(price*qty) as product_price'),
                DB::raw('sum(qty) as total_qty'),
                'chart_of_account_items.name as budget_line'
            )
            ->groupBy('expense_categories.chart_of_account_item_id')
            ->get();
    }

    /**
     * Get budget line export data filtered by date range.
     */
    public function getExportData(string $from, string $to): Collection
    {
        return DB::table('expense_items')
            ->join('expense_categories', 'expense_categories.id', 'expense_items.expense_category_id')
            ->join('chart_of_account_items', 'chart_of_account_items.id', 'expense_categories.chart_of_account_item_id')
            ->whereNull('expense_items.deleted_at')
            ->whereBetween(DB::raw('DATE_FORMAT(expense_items.created_at, \'%Y-%m-%d\')'), [$from, $to])
            ->select(
                'expense_items.*',
                DB::raw('sum(price*qty) as product_price'),
                DB::raw('sum(qty) as total_qty'),
                'chart_of_account_items.name as budget_line',
                'expense_categories.chart_of_account_item_id'
            )
            ->groupBy('expense_categories.chart_of_account_item_id')
            ->get();
    }
}
