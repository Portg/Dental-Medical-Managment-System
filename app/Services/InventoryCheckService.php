<?php

namespace App\Services;

use App\InventoryBatch;
use App\InventoryCheck;
use App\InventoryCheckItem;
use App\InventoryItem;
use App\StockIn;
use App\StockInItem;
use App\StockOut;
use App\StockOutItem;
use App\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 盘点单 Service（Week 4 Phase 7）
 *
 * AG-058：创建前查 draft 状态同分类同日期，存在则拒绝。
 * AG-059：创建时从 current_stock 快照 system_qty，之后出入库不影响快照。
 * AG-060：偏差 > check_deviation_threshold（默认 0.5=50%）时返回 needs_confirm。
 * AG-065：diff_qty 使用 bcmath 计算。
 * AG-066：应用层检查（无 DB 唯一索引，见 migration 注释）。
 * AG-067：confirmCheck() 后端重新计算偏差率，不信任前端状态。
 */
class InventoryCheckService
{
    /**
     * 创建盘点单草稿。
     *
     * AG-058：同分类同日期已有 draft 时拒绝。
     * AG-059：从 current_stock 快照 system_qty。
     *
     * @return array{status: bool, check?: InventoryCheck, message?: string}
     */
    public function createCheck(int $categoryId, string $checkDate, int $userId, string $notes = ''): array
    {
        // AG-058 / AG-066：应用层查重
        $exists = InventoryCheck::where('category_id', $categoryId)
            ->where('check_date', $checkDate)
            ->where('status', InventoryCheck::STATUS_DRAFT)
            ->whereNull('deleted_at')
            ->exists();

        if ($exists) {
            return ['status' => false, 'message' => __('inventory.check_already_exists')];
        }

        // 查询该分类所有 active 物品
        $inventoryItems = InventoryItem::where('category_id', $categoryId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->get();

        if ($inventoryItems->isEmpty()) {
            return ['status' => false, 'message' => __('inventory.no_items_in_category')];
        }

        $checkNo = InventoryCheck::generateCheckNo();

        $check = InventoryCheck::create([
            'check_no'    => $checkNo,
            'category_id' => $categoryId,
            'check_date'  => $checkDate,
            'status'      => InventoryCheck::STATUS_DRAFT,
            'notes'       => $notes ?: null,
            '_who_added'  => $userId,
        ]);

        // AG-059：批量创建明细，system_qty 快照自 current_stock
        foreach ($inventoryItems as $item) {
            InventoryCheckItem::create([
                'inventory_check_id' => $check->id,
                'inventory_item_id'  => $item->id,
                'system_qty'         => $item->current_stock,
                'actual_qty'         => null,
                'diff_qty'           => null,
                '_who_added'         => $userId,
            ]);
        }

        $check->load(['category', 'items.inventoryItem', 'addedBy']);

        return ['status' => true, 'check' => $check];
    }

    /**
     * 更新 actual_qty。
     * 只允许 draft 状态修改（AG-052 同理）。
     * 同时计算并保存 diff_qty = actual_qty - system_qty（bcmath，AG-065）。
     *
     * @param array $items  [['id' => checkItemId, 'actual_qty' => '10.00'], ...]
     * @return array{status: bool, message: string}
     */
    public function updateActualQty(int $checkId, array $items, int $userId): array
    {
        $check = InventoryCheck::find($checkId);
        if (!$check) {
            return ['status' => false, 'message' => __('messages.not_found')];
        }
        if (!$check->isDraft()) {
            return ['status' => false, 'message' => __('inventory.cannot_edit_confirmed')];
        }

        foreach ($items as $itemData) {
            $checkItemId = $itemData['id'] ?? null;
            $actualQty   = isset($itemData['actual_qty']) ? (string) $itemData['actual_qty'] : null;

            if (!$checkItemId || $actualQty === null) {
                continue;
            }

            $checkItem = InventoryCheckItem::where('inventory_check_id', $checkId)
                ->where('id', $checkItemId)
                ->first();

            if (!$checkItem) {
                continue;
            }

            // AG-065：bcmath 计算差异
            $diffQty = bcsub($actualQty, (string) $checkItem->system_qty, 2);

            $checkItem->update([
                'actual_qty' => $actualQty,
                'diff_qty'   => $diffQty,
            ]);
        }

        return ['status' => true, 'message' => __('inventory.actual_qty_saved')];
    }

    /**
     * 确认盘点单。
     *
     * AG-067：后端重新计算偏差率，不信任前端传入的偏差结果。
     * AG-060：偏差 > check_deviation_threshold 时返回 needs_confirm。
     * 盘亏（diff < 0）→ 已确认出库单（inventory_loss）+ 扣库存。
     * 盘盈（diff > 0）→ 已确认入库单 + 增库存。
     *
     * @return array{status: bool, needs_confirm?: bool, items?: array, message?: string}
     */
    public function confirmCheck(int $checkId, int $userId, bool $forceConfirm = false): array
    {
        $check = InventoryCheck::with('items.inventoryItem')->find($checkId);
        if (!$check) {
            return ['status' => false, 'message' => __('messages.not_found')];
        }
        if (!$check->isDraft()) {
            return ['status' => false, 'message' => __('inventory.cannot_confirm_non_draft')];
        }

        // 检查所有明细已填写 actual_qty
        $unfilled = $check->items->filter(fn($i) => is_null($i->actual_qty));
        if ($unfilled->isNotEmpty()) {
            return ['status' => false, 'message' => __('inventory.actual_qty_required')];
        }

        // AG-067：后端重新计算各 item 的 diff_qty 和偏差率
        $threshold = (float) SystemSetting::get('inventory.check_deviation_threshold', 0.5);

        $deviationItems = [];
        foreach ($check->items as $item) {
            // 重新计算 diff_qty
            $diffQty  = bcsub((string) $item->actual_qty, (string) $item->system_qty, 2);
            $item->update(['diff_qty' => $diffQty]);

            // 计算偏差率
            $rate = $item->deviation_rate; // 使用 accessor（已基于最新 diff_qty 读模型）

            if (bccomp((string)$rate, (string)$threshold, 6) > 0) {
                $inventoryItem = $item->inventoryItem;
                $deviationItems[] = [
                    'id'             => $item->id,
                    'item_code'      => $inventoryItem->item_code ?? '-',
                    'item_name'      => $inventoryItem->name ?? '-',
                    'system_qty'     => $item->system_qty,
                    'actual_qty'     => $item->actual_qty,
                    'diff_qty'       => $diffQty,
                    'deviation_rate' => round($rate * 100, 2),
                ];
            }
        }

        // AG-060：偏差超阈值且未强制确认，要求前端二次确认
        if (!$forceConfirm && !empty($deviationItems)) {
            return [
                'status'        => false,
                'needs_confirm' => true,
                'items'         => $deviationItems,
                'message'       => __('inventory.check_confirm_warning', [
                    'threshold' => round($threshold * 100),
                ]),
            ];
        }

        // 执行确认事务
        DB::beginTransaction();
        try {
            $checkDate = $check->check_date->format('Y-m-d');

            foreach ($check->items as $item) {
                $inventoryItem = $item->inventoryItem;
                if (!$inventoryItem) {
                    continue;
                }

                $diffQty = (string) $item->diff_qty;

                if (bccomp($diffQty, '0', 2) < 0) {
                    // 盘亏：出库（inventory_loss）
                    $this->createLossStockOut($inventoryItem, $diffQty, $checkDate, $userId, $check->id);
                } elseif (bccomp($diffQty, '0', 2) > 0) {
                    // 盘盈：入库
                    $this->createSurplusStockIn($inventoryItem, $diffQty, $checkDate, $userId, $check->id);
                }
                // diff = 0：无需操作
            }

            // 更新盘点单状态
            $check->update([
                'status'       => InventoryCheck::STATUS_CONFIRMED,
                'checked_by'   => $userId,
                'confirmed_at' => now(),
            ]);

            DB::commit();

            return ['status' => true, 'message' => __('inventory.check_confirmed')];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('inventory_check: confirm failed', ['check_id' => $checkId, 'error' => $e->getMessage()]);
            return ['status' => false, 'message' => __('messages.error_occurred_later')];
        }
    }

    /**
     * 获取盘点单列表（DataTables query）。
     */
    public function getChecksDataTable(Request $request, bool $manageAll = false, int $userId = 0)
    {
        $query = InventoryCheck::with(['category', 'addedBy', 'items'])
            ->orderBy('created_at', 'DESC');

        if (!$manageAll && $userId) {
            $query->where('_who_added', $userId);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        return $query->get();
    }

    /**
     * 获取盘点单详情（含明细）。
     */
    public function getCheckDetail(int $checkId): ?InventoryCheck
    {
        return InventoryCheck::with([
            'category',
            'addedBy',
            'checkedBy',
            'items.inventoryItem',
        ])->find($checkId);
    }

    // ─────────────────────────────────────────────────────────────
    // 私有辅助方法
    // ─────────────────────────────────────────────────────────────

    /**
     * 盘亏时创建已确认出库单，扣减库存及批次（FIFO）。
     * diff_qty < 0，取绝对值作为出库数量。
     */
    private function createLossStockOut(
        InventoryItem $inventoryItem,
        string $diffQty,
        string $checkDate,
        int $userId,
        int $checkId
    ): void {
        // 绝对值（diffQty 为负数）
        $qty = bcsub('0', $diffQty, 2);

        // 悲观锁防并发
        $lockedItem = InventoryItem::lockForUpdate()->find($inventoryItem->id);
        $available  = (string) $lockedItem->current_stock;

        // 扣减量不超过当前库存
        $actualDeduct = bccomp($qty, $available, 4) > 0 ? $available : $qty;

        if (bccomp($actualDeduct, '0', 4) <= 0) {
            return;
        }

        $unitCost   = (string) $lockedItem->average_cost;
        $lineAmount = bcmul($actualDeduct, $unitCost, 2);

        $stockOut = StockOut::create([
            'stock_out_no'   => StockOut::generateStockOutNo(),
            'out_type'       => 'inventory_loss',
            'stock_out_date' => $checkDate,
            'status'         => StockOut::STATUS_CONFIRMED,
            'notes'          => __('inventory.loss_adjustment') . ' #' . $checkId,
            'total_amount'   => $lineAmount,
            '_who_added'     => $userId,
        ]);

        StockOutItem::create([
            'stock_out_id'      => $stockOut->id,
            'inventory_item_id' => $lockedItem->id,
            'qty'               => $actualDeduct,
            'unit_cost'         => $unitCost,
            'amount'            => $lineAmount,
            'batch_no'          => null,
            '_who_added'        => $userId,
        ]);

        // 更新库存
        $newStock = bcsub($available, $actualDeduct, 4);
        $lockedItem->update(['current_stock' => $newStock]);

        // FIFO 批次扣减
        $remaining = $actualDeduct;
        $batches   = InventoryBatch::where('inventory_item_id', $lockedItem->id)
            ->available()
            ->fifo()
            ->lockForUpdate()
            ->get();

        foreach ($batches as $batch) {
            if (bccomp($remaining, '0', 4) <= 0) break;
            $deduct    = bccomp((string) $batch->qty, $remaining, 4) <= 0
                ? (string) $batch->qty
                : $remaining;
            $batch->deductQty($deduct);
            $remaining = bcsub($remaining, $deduct, 4);
        }
    }

    /**
     * 盘盈时创建已确认入库单，增加库存。
     * diff_qty > 0。
     */
    private function createSurplusStockIn(
        InventoryItem $inventoryItem,
        string $diffQty,
        string $checkDate,
        int $userId,
        int $checkId
    ): void {
        $unitCost   = (string) $inventoryItem->average_cost;
        $lineAmount = bcmul($diffQty, $unitCost, 2);

        $stockIn = StockIn::create([
            'stock_in_no'   => StockIn::generateStockInNo(),
            'supplier_id'   => null,
            'stock_in_date' => $checkDate,
            'total_amount'  => $lineAmount,
            'status'        => StockIn::STATUS_CONFIRMED,
            'notes'         => __('inventory.surplus_adjustment') . ' #' . $checkId,
            '_who_added'    => $userId,
        ]);

        // amount 由 StockInItem::saving() hook 自动计算（qty * unit_price）
        StockInItem::create([
            'stock_in_id'       => $stockIn->id,
            'inventory_item_id' => $inventoryItem->id,
            'qty'               => $diffQty,
            'unit_price'        => $unitCost,
            '_who_added'        => $userId,
        ]);

        // 更新库存（AG-048: 悲观锁防并发）
        $lockedSurplusItem = InventoryItem::lockForUpdate()->find($inventoryItem->id);
        $newStock = bcadd((string) $lockedSurplusItem->current_stock, $diffQty, 4);
        $lockedSurplusItem->update(['current_stock' => $newStock]);

        // 创建入库批次（无有效期，状态 available）
        InventoryBatch::create([
            'inventory_item_id' => $inventoryItem->id,
            'batch_no'          => 'ADJ-' . date('Ymd') . '-' . $checkId,
            'expiry_date'       => null,
            'production_date'   => null,
            'qty'               => $diffQty,
            'unit_cost'         => $unitCost,
            'stock_in_id'       => $stockIn->id,
            'status'            => 'available',
            '_who_added'        => $userId,
        ]);
    }
}
