<?php

namespace App\Services;

use App\ExpenseCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpenseCategoryService
{
    /**
     * Get all expense categories for DataTables listing.
     */
    public function getList(): Collection
    {
        return ExpenseCategory::orderBy('updated_at', 'DESC')->get();
    }

    /**
     * Get expense accounts for the index view dropdown.
     */
    public function getExpenseAccounts(): Collection
    {
        return DB::table('chart_of_account_items')
            ->leftJoin('chart_of_account_categories', 'chart_of_account_categories.id',
                'chart_of_account_items.chart_of_account_category_id')
            ->leftJoin('accounting_equations', 'accounting_equations.id',
                'chart_of_account_categories.accounting_equation_id')
            ->whereNull('chart_of_account_items.deleted_at')
            ->where('accounting_equations.name', 'Expenses')
            ->select('chart_of_account_items.*')
            ->get();
    }

    /**
     * Get all expense category names for autocomplete filtering.
     */
    public function filterCategories(): array
    {
        $result = ExpenseCategory::leftjoin('chart_of_account_items', 'chart_of_account_items.id',
            'expense_categories.chart_of_account_item_id')
            ->select('expense_categories.*')->get();

        $data = [];
        foreach ($result as $row) {
            $data[] = $row->name;
        }

        return $data;
    }

    /**
     * Search categories by name for Select2.
     */
    public function searchByName(string $keyword): array
    {
        $data = ExpenseCategory::where('name', 'LIKE', "%$keyword%")->get();

        $formatted = [];
        foreach ($data as $tag) {
            $formatted[] = ['id' => $tag->id, 'text' => $tag->name];
        }

        return $formatted;
    }

    /**
     * Create a new expense category.
     */
    public function create(array $input): ?ExpenseCategory
    {
        return ExpenseCategory::create([
            'name' => $input['name'],
            'chart_of_account_item_id' => $input['expense_account'],
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Find an expense category by ID.
     */
    public function find(int $id): ?ExpenseCategory
    {
        return ExpenseCategory::where('id', $id)->first();
    }

    /**
     * Update an existing expense category.
     */
    public function update(int $id, array $input): bool
    {
        return (bool) ExpenseCategory::where('id', $id)->update([
            'name' => $input['name'],
            'chart_of_account_item_id' => $input['expense_account'],
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Delete an expense category (soft-delete).
     */
    public function delete(int $id): bool
    {
        return (bool) ExpenseCategory::where('id', $id)->delete();
    }
}
