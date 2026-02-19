<?php

namespace App\Services;

use App\Branch;
use App\InventoryBatch;
use App\StockOut;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockOutService
{
    /**
     * Get filtered stock-out records for DataTables.
     */
    public function getStockOutList(array $filters): Collection
    {
        $query = StockOut::with(['patient', 'addedBy'])
            ->orderBy('stock_out_date', 'DESC')
            ->orderBy('id', 'DESC');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['out_type'])) {
            $query->where('out_type', $filters['out_type']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('stock_out_date', [$filters['start_date'], $filters['end_date']]);
        }

        return $query->get();
    }

    /**
     * Get data needed for the create form.
     */
    public function getCreateFormData(): array
    {
        return [
            'branches' => Branch::orderBy('name')->get(),
            'stock_out_no' => StockOut::generateStockOutNo(),
        ];
    }

    /**
     * Create a new stock-out record.
     */
    public function createStockOut(array $data): ?StockOut
    {
        return StockOut::create([
            'stock_out_no' => StockOut::generateStockOutNo(),
            'out_type' => $data['out_type'],
            'stock_out_date' => $data['stock_out_date'],
            'patient_id' => $data['patient_id'] ?? null,
            'appointment_id' => $data['appointment_id'] ?? null,
            'department' => $data['department'] ?? null,
            'notes' => $data['notes'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'status' => StockOut::STATUS_DRAFT,
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Get stock-out with related data for show page.
     */
    public function getStockOutDetail(int $id): ?StockOut
    {
        return StockOut::with(['patient', 'appointment', 'items.inventoryItem', 'addedBy'])->find($id);
    }

    /**
     * Get stock-out for editing (must be draft).
     */
    public function getStockOutForEdit(int $id): ?array
    {
        $stockOut = StockOut::with(['patient', 'appointment', 'items.inventoryItem'])->find($id);
        if (!$stockOut || !$stockOut->isDraft()) {
            return null;
        }

        return [
            'stockOut' => $stockOut,
            'branches' => Branch::orderBy('name')->get(),
        ];
    }

    /**
     * Update a draft stock-out record.
     *
     * @return array{status: bool, message: string}
     */
    public function updateStockOut(int $id, array $data): array
    {
        $stockOut = StockOut::find($id);
        if (!$stockOut || !$stockOut->isDraft()) {
            return ['status' => false, 'message' => __('inventory.cannot_edit_confirmed')];
        }

        $updated = $stockOut->update([
            'out_type' => $data['out_type'],
            'stock_out_date' => $data['stock_out_date'],
            'patient_id' => $data['patient_id'] ?? null,
            'appointment_id' => $data['appointment_id'] ?? null,
            'department' => $data['department'] ?? null,
            'notes' => $data['notes'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
        ]);

        return [
            'status' => (bool) $updated,
            'message' => $updated
                ? __('inventory.stock_out_updated_successfully')
                : __('messages.error_occurred_later'),
        ];
    }

    /**
     * Delete a draft stock-out record.
     *
     * @return array{status: bool, message: string}
     */
    public function deleteStockOut(int $id): array
    {
        $stockOut = StockOut::find($id);
        if (!$stockOut || !$stockOut->isDraft()) {
            return ['status' => false, 'message' => __('inventory.cannot_delete_confirmed')];
        }

        $deleted = (bool) $stockOut->delete();

        return [
            'status' => $deleted,
            'message' => $deleted
                ? __('inventory.stock_out_deleted_successfully')
                : __('messages.error_occurred_later'),
        ];
    }

    /**
     * Confirm a stock-out and update inventory (deduct stock using FIFO).
     *
     * @return array{status: bool, message: string}
     */
    public function confirmStockOut(int $id): array
    {
        $stockOut = StockOut::with('items.inventoryItem')->find($id);
        if (!$stockOut || !$stockOut->isDraft()) {
            return ['status' => false, 'message' => __('inventory.cannot_confirm')];
        }

        if ($stockOut->items()->count() == 0) {
            return ['status' => false, 'message' => __('inventory.no_items_to_confirm')];
        }

        // Check stock availability
        foreach ($stockOut->items as $item) {
            $inventoryItem = $item->inventoryItem;
            if ($inventoryItem->current_stock < $item->qty) {
                return [
                    'status' => false,
                    'message' => __('inventory.insufficient_stock', ['item' => $inventoryItem->name]),
                ];
            }
        }

        DB::beginTransaction();
        try {
            foreach ($stockOut->items as $item) {
                $inventoryItem = $item->inventoryItem;

                // Deduct from inventory
                $inventoryItem->update([
                    'current_stock' => $inventoryItem->current_stock - $item->qty,
                ]);

                // Deduct from batches using FIFO
                $remainingQty = $item->qty;
                $batches = InventoryBatch::where('inventory_item_id', $item->inventory_item_id)
                    ->available()
                    ->fifo()
                    ->get();

                foreach ($batches as $batch) {
                    if ($remainingQty <= 0) break;

                    $deductQty = min($batch->qty, $remainingQty);
                    $batch->deductQty($deductQty);
                    $remainingQty -= $deductQty;
                }
            }

            $stockOut->update(['status' => StockOut::STATUS_CONFIRMED]);

            DB::commit();
            return ['status' => true, 'message' => __('inventory.stock_out_confirmed')];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['status' => false, 'message' => __('messages.error_occurred_later')];
        }
    }

    /**
     * Cancel a draft stock-out.
     *
     * @return array{status: bool, message: string}
     */
    public function cancelStockOut(int $id): array
    {
        $stockOut = StockOut::find($id);
        if (!$stockOut || !$stockOut->isDraft()) {
            return ['status' => false, 'message' => __('inventory.cannot_cancel')];
        }

        $cancelled = (bool) $stockOut->update(['status' => StockOut::STATUS_CANCELLED]);

        return [
            'status' => $cancelled,
            'message' => $cancelled
                ? __('inventory.stock_out_cancelled')
                : __('messages.error_occurred_later'),
        ];
    }
}
