<?php

namespace App\Services;

use App\Branch;
use App\InventoryBatch;
use App\InventoryItem;
use App\ServiceConsumable;
use App\StockOut;
use App\StockOutItem;
use App\SystemSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        DB::beginTransaction();
        try {
            foreach ($stockOut->items as $item) {
                // AG-048: 悲观锁防并发超扣（TOCTOU 修复：库存检查移入事务内）
                $inventoryItem = InventoryItem::lockForUpdate()->find($item->inventory_item_id);

                if (bccomp((string)$inventoryItem->current_stock, (string)$item->qty, 4) < 0) {
                    DB::rollBack();
                    return [
                        'status' => false,
                        'message' => __('inventory.insufficient_stock', ['item' => $inventoryItem->name]),
                    ];
                }

                // Deduct from inventory using bcmath (AG-065)
                $newStock = bcsub((string)$inventoryItem->current_stock, (string)$item->qty, 4);
                $inventoryItem->update([
                    'current_stock' => $newStock,
                ]);

                // Deduct from batches using FIFO with pessimistic lock
                $remainingQty = (string)$item->qty;
                $batches = InventoryBatch::where('inventory_item_id', $item->inventory_item_id)
                    ->available()
                    ->fifo()
                    ->lockForUpdate()
                    ->get();

                foreach ($batches as $batch) {
                    if (bccomp($remainingQty, '0', 4) <= 0) break;

                    $deductQty = bccomp((string)$batch->qty, $remainingQty, 4) <= 0
                        ? (string)$batch->qty
                        : $remainingQty;
                    $batch->deductQty($deductQty);
                    $remainingQty = bcsub($remainingQty, $deductQty, 4);
                }
            }

            $stockOut->update(['status' => StockOut::STATUS_CONFIRMED]);

            DB::commit();
            return ['status' => true, 'message' => __('inventory.stock_out_confirmed')];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('confirmStockOut failed', ['stock_out_id' => $id, 'error' => $e->getMessage()]);
            return ['status' => false, 'message' => __('messages.error_occurred_later')];
        }
    }

    /**
     * 前台代销：收费时自动扣减库存，生成已确认出库单。
     *
     * 遵循：
     * - AG-048: 悲观锁防并发超扣
     * - AG-049: 同一 invoice_id 幂等，不重复生成出库单
     * - AG-051: 库存不足时允许收费，标记 stock_insufficient=true
     * - AG-065: 使用 bcmath 计算金额
     *
     * @param int        $invoiceId
     * @param int        $patientId
     * @param int|null   $appointmentId
     * @param array      $invoiceItems  [{medical_service_id, qty}]
     * @return array{status: bool, warnings: string[]}
     *
     * @AiGenerated
     * reason: 前台代销库存联动核心逻辑
     * generatedAt: 2026-03-16
     * reviewBy: 2026-Q3
     */
    public function createBillingStockOut(
        int $invoiceId,
        int $patientId,
        ?int $appointmentId,
        array $invoiceItems
    ): array {
        // AG-049: 幂等检查，同一发票不重复创建出库单
        if (StockOut::where('invoice_id', $invoiceId)->exists()) {
            return ['status' => true, 'warnings' => []];
        }

        // 收集需要扣减的耗材：medical_service_id → [{inventory_item_id, total_qty}]
        $consumableMap = [];
        foreach ($invoiceItems as $item) {
            $serviceId = $item['medical_service_id'] ?? null;
            if (!$serviceId) continue;

            $consumables = ServiceConsumable::where('medical_service_id', $serviceId)
                ->whereNull('deleted_at')
                ->with('inventoryItem')
                ->get();

            foreach ($consumables as $consumable) {
                $inventoryItemId = $consumable->inventory_item_id;
                $needQty = bcmul((string) $consumable->qty, (string) ($item['qty'] ?? 1), 4);

                if (!isset($consumableMap[$inventoryItemId])) {
                    $consumableMap[$inventoryItemId] = '0';
                }
                $consumableMap[$inventoryItemId] = bcadd($consumableMap[$inventoryItemId], $needQty, 4);
            }
        }

        if (empty($consumableMap)) {
            return ['status' => true, 'warnings' => []];
        }

        $warnings    = [];
        $insufficient = false;

        DB::beginTransaction();
        try {
            $stockOut = StockOut::create([
                'stock_out_no'   => StockOut::generateStockOutNo(),
                'out_type'       => 'treatment',
                'stock_out_date' => now()->format('Y-m-d'),
                'invoice_id'     => $invoiceId,
                'patient_id'     => $patientId,
                'appointment_id' => $appointmentId,
                'status'         => StockOut::STATUS_CONFIRMED,
                'notes'          => __('inventory.billing_stock_out_notes'),
                '_who_added'     => Auth::id(),
            ]);

            $totalAmount = '0';

            foreach ($consumableMap as $inventoryItemId => $neededQty) {
                // AG-048: 悲观锁
                $inventoryItem = InventoryItem::lockForUpdate()->find($inventoryItemId);
                if (!$inventoryItem) continue;

                $available   = (string) $inventoryItem->current_stock;
                $actualDeduct = $neededQty;

                if (bccomp($available, $neededQty, 4) < 0) {
                    // AG-051: 库存不足，记录警告，允许收费
                    $insufficient = true;
                    $actualDeduct = $available; // 扣完现有库存
                    $warnings[] = sprintf(
                        '物品[%s]库存不足，需要 %s 实际可用 %s',
                        $inventoryItem->name,
                        $neededQty,
                        $available
                    );
                    Log::warning('billing_stock_out: insufficient stock', [
                        'invoice_id'      => $invoiceId,
                        'inventory_item'  => $inventoryItem->name,
                        'needed'          => $neededQty,
                        'available'       => $available,
                    ]);
                }

                if (bccomp($actualDeduct, '0', 4) <= 0) continue;

                $lineAmount = bcmul($actualDeduct, (string) $inventoryItem->average_cost, 2);
                $totalAmount = bcadd($totalAmount, $lineAmount, 2);

                StockOutItem::create([
                    'stock_out_id'      => $stockOut->id,
                    'inventory_item_id' => $inventoryItemId,
                    'qty'               => $actualDeduct,
                    'unit_cost'         => $inventoryItem->average_cost,
                    'amount'            => $lineAmount,
                    'batch_no'          => null,
                    '_who_added'        => Auth::id(),
                ]);

                // 更新库存总量
                $newStock = bcsub($available, $actualDeduct, 4);
                $inventoryItem->update(['current_stock' => $newStock]);

                // FIFO 扣减批次（AG-048）
                $remaining = $actualDeduct;
                $batches = InventoryBatch::where('inventory_item_id', $inventoryItemId)
                    ->available()
                    ->fifo()
                    ->lockForUpdate()
                    ->get();

                foreach ($batches as $batch) {
                    if (bccomp($remaining, '0', 4) <= 0) break;
                    $deduct = bccomp((string) $batch->qty, $remaining, 4) <= 0
                        ? (string) $batch->qty
                        : $remaining;
                    $batch->deductQty((float) $deduct);
                    $remaining = bcsub($remaining, $deduct, 4);
                }
            }

            $stockOut->update([
                'total_amount'    => $totalAmount,
                'stock_insufficient' => $insufficient,
            ]);

            DB::commit();
            return ['status' => true, 'warnings' => $warnings];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('billing_stock_out: failed to create stock-out', ['invoice_id' => $invoiceId, 'error' => $e->getMessage()]);
            return ['status' => false, 'warnings' => [$e->getMessage()]];
        }
    }

    /**
     * AG-069: 追加服务时的增量库存扣减。
     *
     * 与 createBillingStockOut() 的区别：
     * - 不做整体幂等检查（已有出库单时向其追加明细，而非直接跳过）
     * - 适用于向已有发票追加服务的场景（appointment-linked invoice）
     *
     * 遵循：
     * - AG-048: 悲观锁防并发超扣
     * - AG-051: 库存不足时允许收费，标记 stock_insufficient=true
     * - AG-065: bcmath 金额计算
     *
     * @param int        $invoiceId
     * @param int        $patientId
     * @param int|null   $appointmentId
     * @param array      $newItems  [{medical_service_id, qty}]
     * @return array{status: bool, warnings: string[]}
     */
    public function appendBillingStockOut(
        int $invoiceId,
        int $patientId,
        ?int $appointmentId,
        array $newItems
    ): array {
        // 收集新增服务对应的耗材需求
        $consumableMap = [];
        foreach ($newItems as $item) {
            $serviceId = $item['medical_service_id'] ?? null;
            if (!$serviceId) continue;

            $consumables = ServiceConsumable::where('medical_service_id', $serviceId)
                ->whereNull('deleted_at')
                ->with('inventoryItem')
                ->get();

            foreach ($consumables as $consumable) {
                $inventoryItemId = $consumable->inventory_item_id;
                $needQty = bcmul((string) $consumable->qty, (string) ($item['qty'] ?? 1), 4);

                $consumableMap[$inventoryItemId] = bcadd(
                    $consumableMap[$inventoryItemId] ?? '0',
                    $needQty, 4
                );
            }
        }

        if (empty($consumableMap)) {
            return ['status' => true, 'warnings' => []];
        }

        $warnings    = [];
        $insufficient = false;

        DB::beginTransaction();
        try {
            // 取得或新建该发票的出库单（保证一张发票只有一条 StockOut，rollback 时可全量回滚）
            $stockOut = StockOut::where('invoice_id', $invoiceId)
                ->where('out_type', 'treatment')
                ->first();

            if (!$stockOut) {
                $stockOut = StockOut::create([
                    'stock_out_no'   => StockOut::generateStockOutNo(),
                    'out_type'       => 'treatment',
                    'stock_out_date' => now()->format('Y-m-d'),
                    'invoice_id'     => $invoiceId,
                    'patient_id'     => $patientId,
                    'appointment_id' => $appointmentId,
                    'status'         => StockOut::STATUS_CONFIRMED,
                    'notes'          => __('inventory.billing_stock_out_notes'),
                    '_who_added'     => Auth::id(),
                ]);
            }

            $appendAmount = '0';

            foreach ($consumableMap as $inventoryItemId => $neededQty) {
                // AG-048: 悲观锁
                $inventoryItem = InventoryItem::lockForUpdate()->find($inventoryItemId);
                if (!$inventoryItem) continue;

                $available    = (string) $inventoryItem->current_stock;
                $actualDeduct = $neededQty;

                if (bccomp($available, $neededQty, 4) < 0) {
                    // AG-051: 库存不足时允许收费
                    $insufficient = true;
                    $actualDeduct = $available;
                    $warnings[] = sprintf(
                        '物品[%s]库存不足，需要 %s 实际可用 %s',
                        $inventoryItem->name, $neededQty, $available
                    );
                    Log::warning('append_billing_stock_out: insufficient stock', [
                        'invoice_id'     => $invoiceId,
                        'inventory_item' => $inventoryItem->name,
                        'needed'         => $neededQty,
                        'available'      => $available,
                    ]);
                }

                if (bccomp($actualDeduct, '0', 4) <= 0) continue;

                $lineAmount   = bcmul($actualDeduct, (string) $inventoryItem->average_cost, 2);
                $appendAmount = bcadd($appendAmount, $lineAmount, 2);

                StockOutItem::create([
                    'stock_out_id'      => $stockOut->id,
                    'inventory_item_id' => $inventoryItemId,
                    'qty'               => $actualDeduct,
                    'unit_cost'         => $inventoryItem->average_cost,
                    'amount'            => $lineAmount,
                    'batch_no'          => null,
                    '_who_added'        => Auth::id(),
                ]);

                // 更新库存总量
                $inventoryItem->update([
                    'current_stock' => bcsub($available, $actualDeduct, 4),
                ]);

                // FIFO 扣减批次（AG-048）
                $remaining = $actualDeduct;
                $batches = InventoryBatch::where('inventory_item_id', $inventoryItemId)
                    ->available()
                    ->fifo()
                    ->lockForUpdate()
                    ->get();

                foreach ($batches as $batch) {
                    if (bccomp($remaining, '0', 4) <= 0) break;
                    $deduct = bccomp((string) $batch->qty, $remaining, 4) <= 0
                        ? (string) $batch->qty
                        : $remaining;
                    $batch->deductQty((float) $deduct);
                    $remaining = bcsub($remaining, $deduct, 4);
                }
            }

            // 累加到出库单总金额，并更新不足标记
            $newTotal = bcadd((string) ($stockOut->total_amount ?? 0), $appendAmount, 2);
            $stockOut->update([
                'total_amount'       => $newTotal,
                'stock_insufficient' => $stockOut->stock_insufficient || $insufficient,
            ]);

            DB::commit();
            return ['status' => true, 'warnings' => $warnings];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('append_billing_stock_out: failed', [
                'invoice_id' => $invoiceId,
                'error'      => $e->getMessage(),
            ]);
            return ['status' => false, 'warnings' => [$e->getMessage()]];
        }
    }

    /**
     * 前台代销回滚：发票删除时撤销库存扣减。
     *
     * 遵循：
     * - AG-050: 在同一事务内回滚出库单 + 恢复批次 + 更新 current_stock
     *
     * @AiGenerated
     * reason: 发票删除时事务性回滚库存
     * generatedAt: 2026-03-16
     * reviewBy: 2026-Q3
     */
    /**
     * 前台代销回滚：发票删除时撤销库存扣减。
     *
     * 注意：此方法不自行开事务，调用者（InvoiceService::deleteInvoice）负责外层事务。
     * 这样保证库存恢复与发票软删除在同一个原子事务内（AG-050）。
     * 任何异常直接向上抛出，由外层事务处理。
     *
     * @AiGenerated
     * reason: 发票删除时事务性回滚库存
     * generatedAt: 2026-03-16
     * reviewBy: 2026-Q3
     */
    public function rollbackBillingStockOut(int $invoiceId): void
    {
        $stockOut = StockOut::where('invoice_id', $invoiceId)
            ->where('out_type', 'treatment')
            ->with('items.inventoryItem')
            ->first();

        if (!$stockOut) return;

        foreach ($stockOut->items as $item) {
            $inventoryItem = $item->inventoryItem;
            if (!$inventoryItem) continue;

            // 恢复 current_stock
            $inventoryItem->update([
                'current_stock' => bcadd(
                    (string) $inventoryItem->current_stock,
                    (string) $item->qty,
                    4
                ),
            ]);

            // 恢复批次：将数量归还到最新批次（AG-050）
            $latestBatch = InventoryBatch::where('inventory_item_id', $inventoryItem->id)
                ->whereNull('deleted_at')
                ->orderByDesc('created_at')
                ->lockForUpdate()
                ->first();

            if ($latestBatch) {
                $latestBatch->qty = bcadd((string) $latestBatch->qty, (string) $item->qty, 4);
                if ($latestBatch->status === 'depleted') {
                    $latestBatch->status = 'available';
                }
                $latestBatch->save();
            }
        }

        $stockOut->items()->delete();
        $stockOut->delete();
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

    // =========================================================
    // 申领单相关方法 (Week 3 Phase 5)
    // AG-052: pending_approval 不可编辑
    // AG-053: approverId != _who_added
    // AG-054: 每 item qty <= inventory.max_requisition_qty (默认100)
    // AG-048: FIFO 扣减使用 lockForUpdate()
    // =========================================================

    /**
     * 创建申领单草稿。
     *
     * @param array $data  {stock_out_date, recipient, department, notes, branch_id, items:[{inventory_item_id, qty}]}
     * @param int   $userId
     * @return array{status: bool, stock_out?: StockOut, message?: string}
     */
    public function createRequisition(array $data, int $userId): array
    {
        $items = $data['items'] ?? [];
        if (empty($items)) {
            return ['status' => false, 'message' => __('inventory.no_items_to_confirm')];
        }

        // AG-054: 验证每条明细数量上限
        $maxQty = (int) SystemSetting::get('inventory.max_requisition_qty', 100);
        foreach ($items as $item) {
            $qty = (float) ($item['qty'] ?? 0);
            if ($qty <= 0) {
                return ['status' => false, 'message' => __('inventory.qty_min')];
            }
            if ($qty > $maxQty) {
                return [
                    'status'  => false,
                    'message' => __('inventory.max_requisition_qty_exceeded', ['max' => $maxQty]),
                ];
            }
        }

        $stockOut = StockOut::create([
            'stock_out_no'   => StockOut::generateStockOutNo(),
            'out_type'       => StockOut::OUT_TYPE_REQUISITION,
            'stock_out_date' => $data['stock_out_date'] ?? now()->format('Y-m-d'),
            'recipient'      => $data['recipient'] ?? null,
            'department'     => $data['department'] ?? null,
            'notes'          => $data['notes'] ?? null,
            'branch_id'      => $data['branch_id'] ?? null,
            'status'         => StockOut::STATUS_DRAFT,
            '_who_added'     => $userId,
        ]);

        foreach ($items as $item) {
            $inventoryItem = InventoryItem::find($item['inventory_item_id']);
            $unitCost = $inventoryItem ? (float) $inventoryItem->average_cost : 0;
            $qty      = (float) $item['qty'];
            $amount   = bcmul((string) $qty, (string) $unitCost, 2);

            StockOutItem::create([
                'stock_out_id'      => $stockOut->id,
                'inventory_item_id' => $item['inventory_item_id'],
                'qty'               => $qty,
                'unit_cost'         => $unitCost,
                'amount'            => $amount,
                'batch_no'          => null,
                '_who_added'        => $userId,
            ]);
        }

        $stockOut->refresh();

        return ['status' => true, 'stock_out' => $stockOut];
    }

    /**
     * 提交申领单（draft → pending_approval）。
     * AG-052: 只有草稿可提交，提交后不可再编辑。
     *
     * @return array{status: bool, message: string}
     */
    public function submitRequisition(int $id, int $userId): array
    {
        $stockOut = StockOut::find($id);
        if (!$stockOut) {
            return ['status' => false, 'message' => __('messages.not_found')];
        }
        if (!$stockOut->isDraft()) {
            return ['status' => false, 'message' => __('inventory.cannot_submit_non_draft')];
        }
        if ($stockOut->_who_added != $userId) {
            return ['status' => false, 'message' => __('messages.unauthorized')];
        }
        if ($stockOut->items()->count() === 0) {
            return ['status' => false, 'message' => __('inventory.no_items_to_confirm')];
        }

        $stockOut->update(['status' => StockOut::STATUS_PENDING_APPROVAL]);

        return ['status' => true, 'message' => __('inventory.requisition_submit_success')];
    }

    /**
     * 审批通过（pending_approval → confirmed + FIFO 扣库存）。
     * AG-053: approverId != _who_added
     * AG-048: lockForUpdate()
     *
     * @return array{status: bool, message: string}
     */
    public function approveRequisition(int $id, int $approverId): array
    {
        $stockOut = StockOut::with('items.inventoryItem')->find($id);
        if (!$stockOut || !$stockOut->isPendingApproval()) {
            return ['status' => false, 'message' => __('inventory.cannot_approve')];
        }

        // AG-053: 审批人不能是申领人
        if ($stockOut->_who_added == $approverId) {
            return ['status' => false, 'message' => __('inventory.cannot_self_approve')];
        }

        DB::beginTransaction();
        try {
            foreach ($stockOut->items as $item) {
                // AG-048: 悲观锁防并发超扣
                $inventoryItem = InventoryItem::lockForUpdate()->find($item->inventory_item_id);
                if (!$inventoryItem) continue;

                $available  = (string) $inventoryItem->current_stock;
                $neededQty  = (string) $item->qty;

                if (bccomp($available, $neededQty, 4) < 0) {
                    DB::rollBack();
                    return [
                        'status'  => false,
                        'message' => __('inventory.insufficient_stock', ['item' => $inventoryItem->name]),
                    ];
                }

                // 更新库存总量
                $newStock = bcsub($available, $neededQty, 4);
                $inventoryItem->update(['current_stock' => $newStock]);

                // FIFO 批次扣减（AG-048）
                $remaining = $neededQty;
                $batches = InventoryBatch::where('inventory_item_id', $item->inventory_item_id)
                    ->available()
                    ->fifo()
                    ->lockForUpdate()
                    ->get();

                foreach ($batches as $batch) {
                    if (bccomp($remaining, '0', 4) <= 0) break;
                    $deduct = bccomp((string) $batch->qty, $remaining, 4) <= 0
                        ? (string) $batch->qty
                        : $remaining;
                    $batch->deductQty((float) $deduct);
                    $remaining = bcsub($remaining, $deduct, 4);
                }
            }

            $stockOut->update([
                'status'      => StockOut::STATUS_CONFIRMED,
                'approved_by' => $approverId,
                'approved_at' => now(),
            ]);

            DB::commit();
            return ['status' => true, 'message' => __('inventory.requisition_approve_success')];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('requisition: approve failed', ['id' => $id, 'error' => $e->getMessage()]);
            return ['status' => false, 'message' => __('messages.error_occurred_later')];
        }
    }

    /**
     * 审批驳回（pending_approval → rejected）。
     * AG-053: approverId != _who_added
     *
     * @return array{status: bool, message: string}
     */
    public function rejectRequisition(int $id, int $approverId, ?string $rejectionReason = null): array
    {
        $stockOut = StockOut::find($id);
        if (!$stockOut || !$stockOut->isPendingApproval()) {
            return ['status' => false, 'message' => __('inventory.cannot_approve')];
        }

        // AG-053
        if ($stockOut->_who_added == $approverId) {
            return ['status' => false, 'message' => __('inventory.cannot_self_approve')];
        }

        $notes = $rejectionReason
            ? ($stockOut->notes ? $stockOut->notes . "\n[驳回原因] " . $rejectionReason : '[驳回原因] ' . $rejectionReason)
            : $stockOut->notes;

        $stockOut->update([
            'status'      => StockOut::STATUS_REJECTED,
            'approved_by' => $approverId,
            'approved_at' => now(),
            'notes'       => $notes,
        ]);

        return ['status' => true, 'message' => __('inventory.requisition_reject_success')];
    }

    /**
     * 重新申请（复制 rejected 为新 draft）。
     *
     * @return array{status: bool, stock_out?: StockOut, message?: string}
     */
    public function cloneRequisition(int $id, int $userId): array
    {
        $original = StockOut::with('items')->find($id);
        if (!$original || !$original->isRejected()) {
            return ['status' => false, 'message' => __('inventory.cannot_clone')];
        }

        $newStockOut = StockOut::create([
            'stock_out_no'   => StockOut::generateStockOutNo(),
            'out_type'       => StockOut::OUT_TYPE_REQUISITION,
            'stock_out_date' => now()->format('Y-m-d'),
            'recipient'      => $original->recipient,
            'department'     => $original->department,
            'notes'          => null,
            'branch_id'      => $original->branch_id,
            'status'         => StockOut::STATUS_DRAFT,
            'approved_by'    => null,
            'approved_at'    => null,
            '_who_added'     => $userId,
        ]);

        foreach ($original->items as $item) {
            StockOutItem::create([
                'stock_out_id'      => $newStockOut->id,
                'inventory_item_id' => $item->inventory_item_id,
                'qty'               => $item->qty,
                'unit_cost'         => $item->unit_cost,
                'amount'            => $item->amount,
                'batch_no'          => null,
                '_who_added'        => $userId,
            ]);
        }

        $newStockOut->refresh();

        return ['status' => true, 'stock_out' => $newStockOut, 'message' => __('inventory.requisition_clone_success')];
    }

    /**
     * 获取申领单列表（DataTables）。
     * 管理员看全部，医生只看自己（_who_added = $userId）。
     */
    public function getRequisitionList(array $filters, ?int $ownerId = null): Collection
    {
        $query = StockOut::with(['addedBy', 'items.inventoryItem', 'approvedBy'])
            ->where('out_type', StockOut::OUT_TYPE_REQUISITION)
            ->orderBy('created_at', 'DESC');

        if ($ownerId !== null) {
            $query->where('_who_added', $ownerId);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->get();
    }

    /**
     * 获取申领单详情。
     */
    public function getRequisitionDetail(int $id): ?StockOut
    {
        return StockOut::with(['addedBy', 'items.inventoryItem', 'approvedBy'])
            ->where('out_type', StockOut::OUT_TYPE_REQUISITION)
            ->find($id);
    }

    // =========================================================
    // 报损单 / 退货单相关方法 (Week 3 Phase 6)
    // AG-053: approverId != _who_added
    // AG-055: damage/supplier_return 必须经过审批
    // AG-056: qty <= current_stock（创建和审批通过时均校验）
    // AG-057: supplier_return 时 supplier_id 必填
    // AG-048: FIFO 扣减使用 lockForUpdate()
    // =========================================================

    /**
     * 创建报损单草稿（damage）。
     * AG-056: qty 不得超过当前库存
     *
     * @param array $data  {stock_out_date, notes, branch_id, items:[{inventory_item_id, qty}]}
     * @param int   $userId
     * @return array{status: bool, stock_out?: StockOut, message?: string}
     */
    public function createDamageReport(array $data, int $userId): array
    {
        $items = $data['items'] ?? [];
        if (empty($items)) {
            return ['status' => false, 'message' => __('inventory.no_items_to_confirm')];
        }

        // AG-056: 校验每条明细数量 <= current_stock
        foreach ($items as $item) {
            $qty = (float) ($item['qty'] ?? 0);
            if ($qty <= 0) {
                return ['status' => false, 'message' => __('inventory.qty_min')];
            }
            $inventoryItem = InventoryItem::find($item['inventory_item_id']);
            if ($inventoryItem && bccomp((string) $qty, (string) $inventoryItem->current_stock, 4) > 0) {
                return [
                    'status'  => false,
                    'message' => __('inventory.qty_exceeds_stock', ['stock' => $inventoryItem->current_stock]),
                ];
            }
        }

        $stockOut = StockOut::create([
            'stock_out_no'   => StockOut::generateStockOutNo(),
            'out_type'       => StockOut::OUT_TYPE_DAMAGE,
            'stock_out_date' => $data['stock_out_date'] ?? now()->format('Y-m-d'),
            'notes'          => $data['notes'] ?? null,
            'branch_id'      => $data['branch_id'] ?? null,
            'status'         => StockOut::STATUS_DRAFT,
            '_who_added'     => $userId,
        ]);

        foreach ($items as $item) {
            $inventoryItem = InventoryItem::find($item['inventory_item_id']);
            $unitCost = $inventoryItem ? (float) $inventoryItem->average_cost : 0;
            $qty      = (float) $item['qty'];
            $amount   = bcmul((string) $qty, (string) $unitCost, 2);

            StockOutItem::create([
                'stock_out_id'      => $stockOut->id,
                'inventory_item_id' => $item['inventory_item_id'],
                'qty'               => $qty,
                'unit_cost'         => $unitCost,
                'amount'            => $amount,
                'batch_no'          => null,
                '_who_added'        => $userId,
            ]);
        }

        $stockOut->refresh();

        return ['status' => true, 'stock_out' => $stockOut];
    }

    /**
     * 创建退货单草稿（supplier_return）。
     * AG-056: qty 不得超过当前库存
     * AG-057: supplier_id 必填
     *
     * @param array $data  {stock_out_date, supplier_id, notes, branch_id, items:[{inventory_item_id, qty}]}
     * @param int   $userId
     * @return array{status: bool, stock_out?: StockOut, message?: string}
     */
    public function createSupplierReturn(array $data, int $userId): array
    {
        // AG-057: 退货单必须有供应商
        if (empty($data['supplier_id'])) {
            return ['status' => false, 'message' => __('inventory.supplier_required_for_return')];
        }

        $items = $data['items'] ?? [];
        if (empty($items)) {
            return ['status' => false, 'message' => __('inventory.no_items_to_confirm')];
        }

        // AG-056: 校验每条明细数量 <= current_stock
        foreach ($items as $item) {
            $qty = (float) ($item['qty'] ?? 0);
            if ($qty <= 0) {
                return ['status' => false, 'message' => __('inventory.qty_min')];
            }
            $inventoryItem = InventoryItem::find($item['inventory_item_id']);
            if ($inventoryItem && bccomp((string) $qty, (string) $inventoryItem->current_stock, 4) > 0) {
                return [
                    'status'  => false,
                    'message' => __('inventory.qty_exceeds_stock', ['stock' => $inventoryItem->current_stock]),
                ];
            }
        }

        $stockOut = StockOut::create([
            'stock_out_no'   => StockOut::generateStockOutNo(),
            'out_type'       => StockOut::OUT_TYPE_SUPPLIER_RETURN,
            'stock_out_date' => $data['stock_out_date'] ?? now()->format('Y-m-d'),
            'supplier_id'    => $data['supplier_id'],
            'notes'          => $data['notes'] ?? null,
            'branch_id'      => $data['branch_id'] ?? null,
            'status'         => StockOut::STATUS_DRAFT,
            '_who_added'     => $userId,
        ]);

        foreach ($items as $item) {
            $inventoryItem = InventoryItem::find($item['inventory_item_id']);
            $unitCost = $inventoryItem ? (float) $inventoryItem->average_cost : 0;
            $qty      = (float) $item['qty'];
            $amount   = bcmul((string) $qty, (string) $unitCost, 2);

            StockOutItem::create([
                'stock_out_id'      => $stockOut->id,
                'inventory_item_id' => $item['inventory_item_id'],
                'qty'               => $qty,
                'unit_cost'         => $unitCost,
                'amount'            => $amount,
                'batch_no'          => null,
                '_who_added'        => $userId,
            ]);
        }

        $stockOut->refresh();

        return ['status' => true, 'stock_out' => $stockOut];
    }

    /**
     * 提交报损/退货单审批（draft → pending_approval）。
     * 适用于 out_type = damage 或 supplier_return。
     * 检查：isDraft() + _who_added == userId
     *
     * @return array{status: bool, message: string}
     */
    public function submitDamageOrReturn(int $id, int $userId): array
    {
        $stockOut = StockOut::find($id);
        if (!$stockOut) {
            return ['status' => false, 'message' => __('messages.not_found')];
        }
        if (!in_array($stockOut->out_type, [StockOut::OUT_TYPE_DAMAGE, StockOut::OUT_TYPE_SUPPLIER_RETURN], true)) {
            return ['status' => false, 'message' => __('messages.unauthorized')];
        }
        if (!$stockOut->isDraft()) {
            return ['status' => false, 'message' => __('inventory.cannot_submit_non_draft')];
        }
        if ($stockOut->_who_added != $userId) {
            return ['status' => false, 'message' => __('messages.unauthorized')];
        }
        if ($stockOut->items()->count() === 0) {
            return ['status' => false, 'message' => __('inventory.no_items_to_confirm')];
        }

        $stockOut->update(['status' => StockOut::STATUS_PENDING_APPROVAL]);

        return ['status' => true, 'message' => __('inventory.submit_approval')];
    }

    /**
     * 审批通过报损/退货（pending_approval → confirmed + FIFO 扣库存）。
     * AG-053: approverId != _who_added
     * AG-056: 事务内再次校验 qty <= current_stock
     * AG-048: lockForUpdate()
     *
     * @return array{status: bool, message: string}
     */
    public function approveDamageOrReturn(int $id, int $approverId): array
    {
        $stockOut = StockOut::with('items.inventoryItem')->find($id);
        if (!$stockOut || !$stockOut->isPendingApproval()) {
            return ['status' => false, 'message' => __('inventory.cannot_approve')];
        }
        if (!in_array($stockOut->out_type, [StockOut::OUT_TYPE_DAMAGE, StockOut::OUT_TYPE_SUPPLIER_RETURN], true)) {
            return ['status' => false, 'message' => __('inventory.cannot_approve')];
        }

        // AG-053: 审批人不能是操作人
        if ($stockOut->_who_added == $approverId) {
            return ['status' => false, 'message' => __('inventory.cannot_self_approve')];
        }

        DB::beginTransaction();
        try {
            foreach ($stockOut->items as $item) {
                // AG-048: 悲观锁防并发超扣
                $inventoryItem = InventoryItem::lockForUpdate()->find($item->inventory_item_id);
                if (!$inventoryItem) continue;

                $available = (string) $inventoryItem->current_stock;
                $neededQty = (string) $item->qty;

                // AG-056: 事务内再次校验数量 <= current_stock
                if (bccomp($available, $neededQty, 4) < 0) {
                    DB::rollBack();
                    return [
                        'status'  => false,
                        'message' => __('inventory.qty_exceeds_stock', ['stock' => $inventoryItem->current_stock]),
                    ];
                }

                // 更新库存总量
                $newStock = bcsub($available, $neededQty, 4);
                $inventoryItem->update(['current_stock' => $newStock]);

                // FIFO 批次扣减（AG-048）
                $remaining = $neededQty;
                $batches = InventoryBatch::where('inventory_item_id', $item->inventory_item_id)
                    ->available()
                    ->fifo()
                    ->lockForUpdate()
                    ->get();

                foreach ($batches as $batch) {
                    if (bccomp($remaining, '0', 4) <= 0) break;
                    $deduct = bccomp((string) $batch->qty, $remaining, 4) <= 0
                        ? (string) $batch->qty
                        : $remaining;
                    $batch->deductQty((float) $deduct);
                    $remaining = bcsub($remaining, $deduct, 4);
                }
            }

            $stockOut->update([
                'status'      => StockOut::STATUS_CONFIRMED,
                'approved_by' => $approverId,
                'approved_at' => now(),
            ]);

            DB::commit();

            $successKey = $stockOut->out_type === StockOut::OUT_TYPE_DAMAGE
                ? 'damage_approve_success'
                : 'return_approve_success';

            return ['status' => true, 'message' => __('inventory.' . $successKey)];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('damage_or_return: approve failed', ['id' => $id, 'error' => $e->getMessage()]);
            return ['status' => false, 'message' => __('messages.error_occurred_later')];
        }
    }

    /**
     * 驳回报损/退货单（pending_approval → rejected）。
     * AG-053: approverId != _who_added
     *
     * @return array{status: bool, message: string}
     */
    public function rejectDamageOrReturn(int $id, int $approverId, ?string $rejectionReason = null): array
    {
        $stockOut = StockOut::find($id);
        if (!$stockOut || !$stockOut->isPendingApproval()) {
            return ['status' => false, 'message' => __('inventory.cannot_approve')];
        }
        if (!in_array($stockOut->out_type, [StockOut::OUT_TYPE_DAMAGE, StockOut::OUT_TYPE_SUPPLIER_RETURN], true)) {
            return ['status' => false, 'message' => __('inventory.cannot_approve')];
        }

        // AG-053
        if ($stockOut->_who_added == $approverId) {
            return ['status' => false, 'message' => __('inventory.cannot_self_approve')];
        }

        $notes = $rejectionReason
            ? ($stockOut->notes ? $stockOut->notes . "\n[驳回原因] " . $rejectionReason : '[驳回原因] ' . $rejectionReason)
            : $stockOut->notes;

        $stockOut->update([
            'status'      => StockOut::STATUS_REJECTED,
            'approved_by' => $approverId,
            'approved_at' => now(),
            'notes'       => $notes,
        ]);

        $successKey = $stockOut->out_type === StockOut::OUT_TYPE_DAMAGE
            ? 'damage_reject_success'
            : 'return_reject_success';

        return ['status' => true, 'message' => __('inventory.' . $successKey)];
    }

    /**
     * 更新申领单草稿（仅 draft 状态，AG-052）。
     *
     * @return array{status: bool, message: string}
     */
    public function updateRequisition(int $id, array $data, int $userId): array
    {
        $stockOut = StockOut::find($id);
        if (!$stockOut || !$stockOut->isDraft()) {
            return ['status' => false, 'message' => __('inventory.cannot_edit_confirmed')];
        }
        if ($stockOut->_who_added != $userId) {
            return ['status' => false, 'message' => __('messages.unauthorized')];
        }

        $stockOut->update([
            'stock_out_date' => $data['stock_out_date'] ?? $stockOut->stock_out_date,
            'recipient'      => $data['recipient'] ?? $stockOut->recipient,
            'department'     => $data['department'] ?? $stockOut->department,
            'notes'          => $data['notes'] ?? $stockOut->notes,
            'branch_id'      => $data['branch_id'] ?? $stockOut->branch_id,
        ]);

        // 重建明细
        if (isset($data['items']) && is_array($data['items'])) {
            $maxQty = (int) SystemSetting::get('inventory.max_requisition_qty', 100);
            foreach ($data['items'] as $item) {
                $qty = (float) ($item['qty'] ?? 0);
                if ($qty > $maxQty) {
                    return [
                        'status'  => false,
                        'message' => __('inventory.max_requisition_qty_exceeded', ['max' => $maxQty]),
                    ];
                }
            }

            $stockOut->items()->delete();
            foreach ($data['items'] as $item) {
                $inventoryItem = InventoryItem::find($item['inventory_item_id']);
                $unitCost = $inventoryItem ? (float) $inventoryItem->average_cost : 0;
                $qty      = (float) $item['qty'];
                $amount   = bcmul((string) $qty, (string) $unitCost, 2);

                StockOutItem::create([
                    'stock_out_id'      => $stockOut->id,
                    'inventory_item_id' => $item['inventory_item_id'],
                    'qty'               => $qty,
                    'unit_cost'         => $unitCost,
                    'amount'            => $amount,
                    'batch_no'          => null,
                    '_who_added'        => $userId,
                ]);
            }
        }

        return ['status' => true, 'message' => __('inventory.stock_out_updated_successfully')];
    }
}
