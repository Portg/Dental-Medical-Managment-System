<?php

namespace App\Imports;

use App\InventoryCategory;
use App\InventoryItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithLimit;

/**
 * 物品批量导入（AG-062 / AG-068）。
 *
 * 使用数字索引而非 WithHeadingRow，避免中文 slug 不稳定的问题。
 * 第一行（表头）由 WithStartRow::startRow() = 2 跳过。
 *
 * 列定义（0-based）：
 *   0 物品编码  1 物品名称  2 分类代码  3 单位
 *   4 规格型号  5 品牌/厂家 6 参考进价  7 销售价格
 *   8 有效期管理 9 安全库存  10 存放位置
 */
class InventoryItemsImport implements ToCollection, WithStartRow, WithLimit
{
    public array $errors = [];      // [['row' => 3, 'reason' => '...'], ...]
    public int $importedCount = 0;
    public int $skippedCount  = 0;

    private const MAX_ROWS = 5000;  // AG-068

    // Column index mapping (0-based)
    private const COL_ITEM_CODE         = 0;
    private const COL_NAME              = 1;
    private const COL_CATEGORY_CODE     = 2;
    private const COL_UNIT              = 3;
    private const COL_SPECIFICATION     = 4;
    private const COL_BRAND             = 5;
    private const COL_REFERENCE_PRICE   = 6;
    private const COL_SELLING_PRICE     = 7;
    private const COL_TRACK_EXPIRY      = 8;
    private const COL_STOCK_WARNING     = 9;
    private const COL_STORAGE_LOCATION  = 10;

    private int $userId;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    /**
     * 从第2行开始读取（跳过表头行）。
     */
    public function startRow(): int
    {
        return 2;
    }

    /**
     * AG-068: 最多读取 5000 行数据行。
     */
    public function limit(): int
    {
        return self::MAX_ROWS;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;  // 实际 Excel 行号（第1行表头，从第2行起是数据）

            // 跳过完全空行
            $values = $row->filter(fn($v) => $v !== null && trim((string) $v) !== '');
            if ($values->isEmpty()) {
                continue;
            }

            // 必填：物品编码
            $itemCode = trim((string) ($row[self::COL_ITEM_CODE] ?? ''));
            if ($itemCode === '') {
                $this->errors[] = ['row' => $rowNum, 'reason' => __('inventory.item_code_required')];
                $this->skippedCount++;
                continue;
            }

            // 必填：物品名称
            $name = trim((string) ($row[self::COL_NAME] ?? ''));
            if ($name === '') {
                $this->errors[] = ['row' => $rowNum, 'reason' => __('inventory.item_name_required')];
                $this->skippedCount++;
                continue;
            }

            // 必填：分类代码
            $categoryCode = trim((string) ($row[self::COL_CATEGORY_CODE] ?? ''));
            if ($categoryCode === '') {
                $this->errors[] = ['row' => $rowNum, 'reason' => __('inventory.category_code_required')];
                $this->skippedCount++;
                continue;
            }

            // 必填：单位
            $unit = trim((string) ($row[self::COL_UNIT] ?? ''));
            if ($unit === '') {
                $this->errors[] = ['row' => $rowNum, 'reason' => __('inventory.unit_required')];
                $this->skippedCount++;
                continue;
            }

            // 查找分类（通过 category_code）
            $category = InventoryCategory::where('code', $categoryCode)
                ->whereNull('deleted_at')
                ->first();
            if (!$category) {
                $this->errors[] = [
                    'row'    => $rowNum,
                    'reason' => __('inventory.import_category_not_found', ['code' => $categoryCode]),
                ];
                $this->skippedCount++;
                continue;
            }

            // AG-062：item_code 唯一性检查，重复则跳过
            if (InventoryItem::where('item_code', $itemCode)->whereNull('deleted_at')->exists()) {
                $this->errors[] = [
                    'row'    => $rowNum,
                    'reason' => __('inventory.import_item_code_duplicate', ['code' => $itemCode]),
                ];
                $this->skippedCount++;
                continue;
            }

            // 处理可选数值字段
            $referencePrice = $this->parseDecimal($row[self::COL_REFERENCE_PRICE] ?? null);
            $sellingPrice   = $this->parseDecimal($row[self::COL_SELLING_PRICE] ?? null);
            $trackExpiry    = $this->parseBool($row[self::COL_TRACK_EXPIRY] ?? null);
            $stockWarning   = max(0, intval($row[self::COL_STOCK_WARNING] ?? 0));

            try {
                InventoryItem::create([
                    'item_code'           => $itemCode,
                    'name'                => $name,
                    'category_id'         => $category->id,
                    'unit'                => $unit,
                    'specification'       => trim((string) ($row[self::COL_SPECIFICATION] ?? '')),
                    'brand'               => trim((string) ($row[self::COL_BRAND] ?? '')),
                    'reference_price'     => $referencePrice,
                    'selling_price'       => $sellingPrice,
                    'track_expiry'        => $trackExpiry,
                    'stock_warning_level' => $stockWarning,
                    'storage_location'    => trim((string) ($row[self::COL_STORAGE_LOCATION] ?? '')),
                    'current_stock'       => 0,
                    'average_cost'        => 0,
                    'is_active'           => 1,
                    '_who_added'          => $this->userId,
                ]);

                $this->importedCount++;
            } catch (\Exception $e) {
                Log::warning('InventoryItemsImport: row failed', [
                    'row'   => $rowNum,
                    'error' => $e->getMessage(),
                ]);
                $this->errors[] = ['row' => $rowNum, 'reason' => $e->getMessage()];
                $this->skippedCount++;
            }
        }
    }

    /**
     * 解析十进制数字，非数字时返回 0。
     */
    private function parseDecimal(mixed $value): float
    {
        if ($value === null || trim((string) $value) === '') {
            return 0.0;
        }
        return is_numeric($value) ? (float) $value : 0.0;
    }

    /**
     * 解析布尔值：是/1/yes/true → true，其余 → false。
     */
    private function parseBool(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }
        return in_array(mb_strtolower(trim((string) $value)), ['是', '1', 'yes', 'true'], true);
    }
}
