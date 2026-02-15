<?php

namespace App\Services;

use App\InventoryBatch;
use App\InventoryCategory;
use App\InventoryItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class InventoryItemService
{
    /**
     * Get filtered inventory items for DataTables.
     */
    public function getItemList(array $filters): Collection
    {
        $query = InventoryItem::with('category')
            ->orderBy('updated_at', 'DESC');

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== '' && $filters['is_active'] !== null) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->get();
    }

    /**
     * Search inventory items for autocomplete.
     */
    public function searchItems(string $keyword): array
    {
        $items = InventoryItem::active()
            ->search($keyword)
            ->with('category')
            ->limit(20)
            ->get();

        $formatted = [];
        foreach ($items as $item) {
            $formatted[] = [
                'id' => $item->id,
                'text' => $item->item_code . ' - ' . $item->name,
                'item_code' => $item->item_code,
                'name' => $item->name,
                'specification' => $item->specification,
                'unit' => $item->unit,
                'reference_price' => $item->reference_price,
                'selling_price' => $item->selling_price,
                'current_stock' => $item->current_stock,
                'average_cost' => $item->average_cost,
                'track_expiry' => $item->track_expiry,
                'category' => $item->category ? $item->category->name : null,
            ];
        }

        return $formatted;
    }

    /**
     * Create a new inventory item.
     */
    public function createItem(array $data): ?InventoryItem
    {
        return InventoryItem::create([
            'item_code' => $data['item_code'],
            'name' => $data['name'],
            'specification' => $data['specification'] ?? null,
            'unit' => $data['unit'],
            'category_id' => $data['category_id'],
            'brand' => $data['brand'] ?? null,
            'reference_price' => $data['reference_price'] ?? 0,
            'selling_price' => $data['selling_price'] ?? 0,
            'track_expiry' => $data['track_expiry'] ?? false,
            'stock_warning_level' => $data['stock_warning_level'] ?? 10,
            'storage_location' => $data['storage_location'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Get an inventory item with category for editing.
     */
    public function getItemForEdit(int $id): ?InventoryItem
    {
        return InventoryItem::with('category')->find($id);
    }

    /**
     * Update an existing inventory item.
     */
    public function updateItem(int $id, array $data): bool
    {
        return (bool) InventoryItem::where('id', $id)->update([
            'item_code' => $data['item_code'],
            'name' => $data['name'],
            'specification' => $data['specification'] ?? null,
            'unit' => $data['unit'],
            'category_id' => $data['category_id'],
            'brand' => $data['brand'] ?? null,
            'reference_price' => $data['reference_price'] ?? 0,
            'selling_price' => $data['selling_price'] ?? 0,
            'track_expiry' => $data['track_expiry'] ?? false,
            'stock_warning_level' => $data['stock_warning_level'] ?? 10,
            'storage_location' => $data['storage_location'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Delete an inventory item if it has no stock movements.
     *
     * @return array{status: bool, message: string}
     */
    public function deleteItem(int $id): array
    {
        $item = InventoryItem::find($id);
        if ($item && ($item->stockInItems()->count() > 0 || $item->stockOutItems()->count() > 0)) {
            return ['status' => false, 'message' => __('inventory.item_has_movements')];
        }

        $deleted = (bool) InventoryItem::where('id', $id)->delete();

        return [
            'status' => $deleted,
            'message' => $deleted
                ? __('inventory.item_deleted_successfully')
                : __('messages.error_occurred_later'),
        ];
    }

    /**
     * Get low-stock active items for warnings.
     */
    public function getLowStockItems(): Collection
    {
        return InventoryItem::lowStock()
            ->active()
            ->with('category')
            ->orderBy('current_stock')
            ->get();
    }

    /**
     * Get batches near expiry.
     */
    public function getExpiryWarningBatches(int $warningDays = 30): Collection
    {
        return InventoryBatch::with(['inventoryItem', 'inventoryItem.category'])
            ->where('status', 'available')
            ->where('qty', '>', 0)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', Carbon::now()->addDays($warningDays))
            ->orderBy('expiry_date')
            ->get();
    }

    /**
     * Get active categories for filter dropdowns.
     */
    public function getActiveCategories(): Collection
    {
        return InventoryCategory::active()->ordered()->get();
    }
}
