<?php

namespace App\Services;

use App\ExpenseCategory;
use App\ExpenseItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpenseItemService
{
    /**
     * Get expense items for a given expense, for DataTables listing.
     */
    public function getListByExpense(int $expenseId): Collection
    {
        return DB::table('expense_items')
            ->join('expense_categories', 'expense_categories.id', 'expense_items.expense_category_id')
            ->join('users', 'users.id', 'expense_items._who_added')
            ->whereNull('expense_items.deleted_at')
            ->where('expense_items.expense_id', '=', $expenseId)
            ->select('expense_items.*', 'expense_categories.name', 'users.othername as added_by')
            ->orderBy('expense_items.updated_at', 'desc')
            ->get();
    }

    /**
     * Create or retrieve an expense category by name.
     */
    public function createOrGetExpenseCategory(string $categoryName): int
    {
        $existing = ExpenseCategory::where('name', $categoryName)->first();
        if ($existing) {
            return $existing->id;
        }

        $newItem = ExpenseCategory::create([
            'name' => $categoryName,
            '_who_added' => Auth::User()->id,
        ]);

        return $newItem->id;
    }

    /**
     * Create a new expense item.
     */
    public function create(array $input): ?ExpenseItem
    {
        $categoryId = $this->createOrGetExpenseCategory($input['item']);

        return ExpenseItem::create([
            'expense_category_id' => $categoryId,
            'qty' => $input['qty'],
            'price' => $input['price'],
            'expense_id' => $input['expense_id'],
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Find an expense item by ID with category info.
     */
    public function find(int $id)
    {
        return DB::table('expense_items')
            ->leftJoin('expense_categories', 'expense_categories.id', 'expense_items.expense_category_id')
            ->whereNull('expense_items.deleted_at')
            ->where('expense_items.id', $id)
            ->select('expense_items.*', 'expense_categories.name')
            ->first();
    }

    /**
     * Update an existing expense item.
     */
    public function update(int $id, array $input): bool
    {
        $categoryId = $this->createOrGetExpenseCategory($input['item']);

        return (bool) ExpenseItem::where('id', $id)->update([
            'expense_category_id' => $categoryId,
            'qty' => $input['qty'],
            'price' => $input['price'],
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Delete an expense item (soft-delete).
     */
    public function delete(int $id): bool
    {
        return (bool) ExpenseItem::where('id', $id)->delete();
    }
}
