<?php

namespace App\Services;

use App\InventoryItem;
use App\StockOut;
use App\StockOutItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class StockOutItemService
{
    /**
     * Get items for a stock-out record.
     */
    public function getItemsByStockOut(int $stockOutId): Collection
    {
        return StockOutItem::with('inventoryItem')
            ->where('stock_out_id', $stockOutId)
            ->orderBy('id')
            ->get();
    }

    /**
     * Verify that the stock-out is in draft status.
     *
     * @return array|null  Returns error array if not draft, null if OK.
     */
    public function verifyDraftStatus(int $stockOutId): ?array
    {
        $stockOut = StockOut::find($stockOutId);
        if (!$stockOut || !$stockOut->isDraft()) {
            return ['message' => __('inventory.cannot_edit_confirmed'), 'status' => false];
        }

        return null;
    }

    /**
     * Check stock availability for a given inventory item.
     *
     * @return array|null  Returns error array if insufficient, null if OK.
     */
    public function checkStockAvailability(int $inventoryItemId, float $qty): ?array
    {
        $inventoryItem = InventoryItem::find($inventoryItemId);
        if ($inventoryItem->current_stock < $qty) {
            return [
                'message' => __('inventory.insufficient_stock', ['item' => $inventoryItem->name]),
                'status' => false,
            ];
        }

        return null;
    }

    /**
     * Create a stock-out item.
     */
    public function createItem(array $data): ?StockOutItem
    {
        $inventoryItem = InventoryItem::find($data['inventory_item_id']);
        $unitCost = $inventoryItem->average_cost;

        return StockOutItem::create([
            'stock_out_id' => $data['stock_out_id'],
            'inventory_item_id' => $data['inventory_item_id'],
            'qty' => $data['qty'],
            'unit_cost' => $unitCost,
            'amount' => $data['qty'] * $unitCost,
            'batch_no' => $data['batch_no'] ?? null,
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Find a stock-out item by ID with inventory item.
     */
    public function find(int $id): ?StockOutItem
    {
        return StockOutItem::with('inventoryItem')->find($id);
    }

    /**
     * Find a stock-out item by ID (without eager loading).
     */
    public function findBasic(int $id): ?StockOutItem
    {
        return StockOutItem::find($id);
    }

    /**
     * Verify an item's stock-out is in draft status.
     *
     * @return array|null  Returns error array if not draft, null if OK.
     */
    public function verifyItemDraftStatus(StockOutItem $item): ?array
    {
        $stockOut = $item->stockOut;
        if (!$stockOut || !$stockOut->isDraft()) {
            return ['message' => __('inventory.cannot_edit_confirmed'), 'status' => false];
        }

        return null;
    }

    /**
     * Check stock availability for an existing item's inventory item.
     *
     * @return array|null  Returns error array if insufficient, null if OK.
     */
    public function checkItemStockAvailability(StockOutItem $item, float $qty): ?array
    {
        $inventoryItem = $item->inventoryItem;
        if ($inventoryItem->current_stock < $qty) {
            return [
                'message' => __('inventory.insufficient_stock', ['item' => $inventoryItem->name]),
                'status' => false,
            ];
        }

        return null;
    }

    /**
     * Update a stock-out item.
     */
    public function updateItem(StockOutItem $item, array $data): bool
    {
        return (bool) $item->update([
            'qty' => $data['qty'],
            'amount' => $data['qty'] * $item->unit_cost,
            'batch_no' => $data['batch_no'] ?? null,
        ]);
    }

    /**
     * Delete a stock-out item.
     */
    public function deleteItem(StockOutItem $item): bool
    {
        return (bool) $item->delete();
    }
}
