<?php

namespace App\Services;

use App\InventoryItem;
use App\StockIn;
use App\StockInItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class StockInItemService
{
    /**
     * Get items for a stock-in record.
     */
    public function getItemsByStockIn(int $stockInId): Collection
    {
        return StockInItem::with('inventoryItem')
            ->where('stock_in_id', $stockInId)
            ->orderBy('id')
            ->get();
    }

    /**
     * Verify that the stock-in is in draft status.
     *
     * @return array|null  Returns error array if not draft, null if OK.
     */
    public function verifyDraftStatus(int $stockInId): ?array
    {
        $stockIn = StockIn::find($stockInId);
        if (!$stockIn || !$stockIn->isDraft()) {
            return ['message' => __('inventory.cannot_edit_confirmed'), 'status' => false];
        }

        return null;
    }

    /**
     * Check if the inventory item requires batch/expiry and they are missing.
     *
     * @return array|null  Returns error array if required fields missing, null if OK.
     */
    public function checkExpiryRequirement(int $inventoryItemId, ?string $batchNo, ?string $expiryDate): ?array
    {
        $inventoryItem = InventoryItem::find($inventoryItemId);
        if ($inventoryItem && $inventoryItem->track_expiry) {
            if (empty($batchNo) || empty($expiryDate)) {
                return ['message' => __('inventory.batch_expiry_required'), 'status' => false];
            }
        }

        return null;
    }

    /**
     * Check price deviation against reference price (BR-043).
     *
     * @return array|null  Returns warning array if deviation >20% and not confirmed, null if OK.
     */
    public function checkPriceDeviation(int $inventoryItemId, float $unitPrice, bool $confirmed): ?array
    {
        $inventoryItem = InventoryItem::find($inventoryItemId);
        if ($inventoryItem && $inventoryItem->reference_price > 0) {
            $deviation = abs($unitPrice - $inventoryItem->reference_price) / $inventoryItem->reference_price;
            if ($deviation > 0.2 && !$confirmed) {
                return [
                    'message' => __('inventory.price_deviation_warning'),
                    'requires_confirmation' => true,
                    'deviation_percent' => round($deviation * 100, 1),
                    'status' => 'warning',
                ];
            }
        }

        return null;
    }

    /**
     * Create a stock-in item.
     */
    public function createItem(array $data): ?StockInItem
    {
        return StockInItem::create([
            'stock_in_id' => $data['stock_in_id'],
            'inventory_item_id' => $data['inventory_item_id'],
            'qty' => $data['qty'],
            'unit_price' => $data['unit_price'],
            'amount' => $data['qty'] * $data['unit_price'],
            'batch_no' => $data['batch_no'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
            'production_date' => $data['production_date'] ?? null,
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Find a stock-in item by ID with inventory item.
     */
    public function find(int $id): ?StockInItem
    {
        return StockInItem::with('inventoryItem')->find($id);
    }

    /**
     * Find a stock-in item by ID (without eager loading).
     */
    public function findBasic(int $id): ?StockInItem
    {
        return StockInItem::find($id);
    }

    /**
     * Verify an item's stock-in is in draft status.
     *
     * @return array|null  Returns error array if not draft, null if OK.
     */
    public function verifyItemDraftStatus(StockInItem $item): ?array
    {
        $stockIn = $item->stockIn;
        if (!$stockIn || !$stockIn->isDraft()) {
            return ['message' => __('inventory.cannot_edit_confirmed'), 'status' => false];
        }

        return null;
    }

    /**
     * Check expiry requirement for an existing item.
     *
     * @return array|null  Returns error array if required fields missing, null if OK.
     */
    public function checkItemExpiryRequirement(StockInItem $item, ?string $batchNo, ?string $expiryDate): ?array
    {
        $inventoryItem = $item->inventoryItem;
        if ($inventoryItem && $inventoryItem->track_expiry) {
            if (empty($batchNo) || empty($expiryDate)) {
                return ['message' => __('inventory.batch_expiry_required'), 'status' => false];
            }
        }

        return null;
    }

    /**
     * Update a stock-in item.
     */
    public function updateItem(StockInItem $item, array $data): bool
    {
        return (bool) $item->update([
            'qty' => $data['qty'],
            'unit_price' => $data['unit_price'],
            'amount' => $data['qty'] * $data['unit_price'],
            'batch_no' => $data['batch_no'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
            'production_date' => $data['production_date'] ?? null,
        ]);
    }

    /**
     * Delete a stock-in item.
     */
    public function deleteItem(StockInItem $item): bool
    {
        return (bool) $item->delete();
    }
}
