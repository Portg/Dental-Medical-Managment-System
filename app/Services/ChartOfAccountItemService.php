<?php

namespace App\Services;

use App\ChartOfAccountItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChartOfAccountItemService
{
    /**
     * Create a new chart of account item.
     */
    public function createItem(array $data): ?ChartOfAccountItem
    {
        return ChartOfAccountItem::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'chart_of_account_category_id' => $data['account_type'],
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Find an item by ID with its category name.
     */
    public function findItem(int $id): ?object
    {
        return DB::table('chart_of_account_items')
            ->leftJoin('chart_of_account_categories', 'chart_of_account_categories.id',
                'chart_of_account_items.chart_of_account_category_id')
            ->whereNull('chart_of_account_items.deleted_at')
            ->where('chart_of_account_items.id', $id)
            ->select(['chart_of_account_items.*', 'chart_of_account_categories.name as category_name'])
            ->first();
    }

    /**
     * Update an existing item.
     */
    public function updateItem(int $id, array $data): bool
    {
        return (bool) ChartOfAccountItem::where('id', $id)->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'chart_of_account_category_id' => $data['account_type'],
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * 科目是否仍被引用。两张表都有指向 chart_of_account_items 的外键，
     * 硬删会直接撞约束，软删则会留下费用类别指着一个查不到的科目。
     */
    public function isInUse(int $id): bool
    {
        return DB::table('expense_categories')
                ->where('chart_of_account_item_id', $id)
                ->whereNull('deleted_at')
                ->exists()
            || DB::table('expense_payments')
                ->where('payment_account_id', $id)
                ->whereNull('deleted_at')
                ->exists();
    }

    /**
     * Soft delete an item.
     */
    public function deleteItem(int $id): bool
    {
        return (bool) ChartOfAccountItem::where('id', $id)->delete();
    }
}
