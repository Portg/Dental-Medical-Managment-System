<?php

namespace App\Services;

use App\Branch;
use App\InventoryBatch;
use App\StockIn;
use App\Supplier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockInService
{
    /**
     * Get filtered stock-in records for DataTables.
     */
    public function getStockInList(array $filters): Collection
    {
        $query = StockIn::with(['supplier', 'addedBy'])
            ->orderBy('stock_in_date', 'DESC')
            ->orderBy('id', 'DESC');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('stock_in_date', [$filters['start_date'], $filters['end_date']]);
        }

        return $query->get();
    }

    /**
     * Get data needed for the create form.
     */
    public function getCreateFormData(): array
    {
        return [
            'suppliers' => Supplier::orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
            'stock_in_no' => StockIn::generateStockInNo(),
        ];
    }

    /**
     * Create a new stock-in record.
     */
    public function createStockIn(array $data): ?StockIn
    {
        return StockIn::create([
            'stock_in_no' => StockIn::generateStockInNo(),
            'supplier_id' => $data['supplier_id'] ?? null,
            'stock_in_date' => $data['stock_in_date'],
            'notes' => $data['notes'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'status' => StockIn::STATUS_DRAFT,
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Get stock-in with related data for show page.
     */
    public function getStockInDetail(int $id): ?StockIn
    {
        return StockIn::with(['supplier', 'items.inventoryItem', 'addedBy'])->find($id);
    }

    /**
     * Get stock-in for editing (must be draft).
     */
    public function getStockInForEdit(int $id): ?array
    {
        $stockIn = StockIn::with(['supplier', 'items.inventoryItem'])->find($id);
        if (!$stockIn || !$stockIn->isDraft()) {
            return null;
        }

        return [
            'stockIn' => $stockIn,
            'suppliers' => Supplier::orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
        ];
    }

    /**
     * Update a draft stock-in record.
     *
     * @return array{status: bool, message: string}
     */
    public function updateStockIn(int $id, array $data): array
    {
        $stockIn = StockIn::find($id);
        if (!$stockIn || !$stockIn->isDraft()) {
            return ['status' => false, 'message' => __('inventory.cannot_edit_confirmed')];
        }

        $updated = $stockIn->update([
            'supplier_id' => $data['supplier_id'] ?? null,
            'stock_in_date' => $data['stock_in_date'],
            'notes' => $data['notes'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
        ]);

        return [
            'status' => (bool) $updated,
            'message' => $updated
                ? __('inventory.stock_in_updated_successfully')
                : __('messages.error_occurred_later'),
        ];
    }

    /**
     * Delete a draft stock-in record.
     *
     * @return array{status: bool, message: string}
     */
    public function deleteStockIn(int $id): array
    {
        $stockIn = StockIn::find($id);
        if (!$stockIn || !$stockIn->isDraft()) {
            return ['status' => false, 'message' => __('inventory.cannot_delete_confirmed')];
        }

        $deleted = (bool) $stockIn->delete();

        return [
            'status' => $deleted,
            'message' => $deleted
                ? __('inventory.stock_in_deleted_successfully')
                : __('messages.error_occurred_later'),
        ];
    }

    /**
     * Confirm a stock-in and update inventory (stock + batches).
     *
     * @return array{status: bool, message: string}
     */
    public function confirmStockIn(int $id): array
    {
        $stockIn = StockIn::with('items.inventoryItem')->find($id);
        if (!$stockIn || !$stockIn->isDraft()) {
            return ['status' => false, 'message' => __('inventory.cannot_confirm')];
        }

        if ($stockIn->items()->count() == 0) {
            return ['status' => false, 'message' => __('inventory.no_items_to_confirm')];
        }

        DB::beginTransaction();
        try {
            foreach ($stockIn->items as $item) {
                $inventoryItem = $item->inventoryItem;
                $oldStock = $inventoryItem->current_stock;
                $oldCost = $inventoryItem->average_cost;
                $newStock = $oldStock + $item->qty;

                // Calculate weighted average cost
                $newCost = $oldStock == 0
                    ? $item->unit_price
                    : (($oldStock * $oldCost) + ($item->qty * $item->unit_price)) / $newStock;

                $inventoryItem->update([
                    'current_stock' => $newStock,
                    'average_cost' => $newCost,
                ]);

                // Create batch record
                InventoryBatch::create([
                    'inventory_item_id' => $item->inventory_item_id,
                    'batch_no' => $item->batch_no ?? ('B' . date('YmdHis') . $item->id),
                    'expiry_date' => $item->expiry_date,
                    'production_date' => $item->production_date,
                    'qty' => $item->qty,
                    'unit_cost' => $item->unit_price,
                    'stock_in_id' => $stockIn->id,
                    'status' => 'available',
                    '_who_added' => Auth::User()->id,
                ]);
            }

            $stockIn->update(['status' => StockIn::STATUS_CONFIRMED]);

            DB::commit();
            return ['status' => true, 'message' => __('inventory.stock_in_confirmed')];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['status' => false, 'message' => __('messages.error_occurred_later')];
        }
    }

    /**
     * Cancel a draft stock-in.
     *
     * @return array{status: bool, message: string}
     */
    public function cancelStockIn(int $id): array
    {
        $stockIn = StockIn::find($id);
        if (!$stockIn || !$stockIn->isDraft()) {
            return ['status' => false, 'message' => __('inventory.cannot_cancel')];
        }

        $cancelled = (bool) $stockIn->update(['status' => StockIn::STATUS_CANCELLED]);

        return [
            'status' => $cancelled,
            'message' => $cancelled
                ? __('inventory.stock_in_cancelled')
                : __('messages.error_occurred_later'),
        ];
    }

    /**
     * Get suppliers list for dropdowns.
     */
    public function getSuppliers(): Collection
    {
        return Supplier::orderBy('name')->get();
    }
}
