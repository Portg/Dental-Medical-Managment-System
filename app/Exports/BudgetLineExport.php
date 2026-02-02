<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class BudgetLineExport implements WithMultipleSheets
{
    private $data;
    private $sheetTitle;
    private $from;
    private $to;

    public function __construct($queryData, $sheetTitle, $from, $to)
    {
        $this->data = $queryData;
        $this->sheetTitle = $sheetTitle;
        $this->from = $from;
        $this->to = $to;
    }

    public function sheets(): array
    {
        $sheets = [];

        // Summary sheet
        $sheets[] = new BudgetLineSummarySheet($this->data, $this->sheetTitle);

        // Collect unique budget lines
        $budgetLines = [];
        foreach ($this->data as $row) {
            $exists = false;
            foreach ($budgetLines as $bl) {
                if ($bl['id'] == $row->chart_of_account_item_id) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $budgetLines[] = ['id' => $row->chart_of_account_item_id, 'name' => $row->budget_line];
            }
        }

        // Per-budget-line detail sheets
        foreach ($budgetLines as $bl) {
            $detailData = DB::table('expense_items')
                ->join('expense_categories', 'expense_categories.id', 'expense_items.expense_category_id')
                ->whereNull('expense_items.deleted_at')
                ->whereBetween(DB::raw('DATE_FORMAT(expense_items.created_at, \'%Y-%m-%d\')'), [$this->from, $this->to])
                ->where('expense_categories.chart_of_account_item_id', $bl['id'])
                ->select('expense_items.*', 'expense_categories.name as product_name')
                ->get();

            $sheets[] = new BudgetLineDetailSheet($detailData, $bl['name']);
        }

        return $sheets;
    }
}
