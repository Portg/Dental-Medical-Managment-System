<?php

namespace App\Services;

use App\InventoryCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class InventoryCategoryService
{
    /**
     * Get all inventory categories for DataTables listing.
     */
    public function getList(): Collection
    {
        return InventoryCategory::orderBy('sort_order')
            ->orderBy('updated_at', 'DESC')
            ->get();
    }

    /**
     * Get active categories for dropdown lists.
     */
    public function getActiveCategories(): Collection
    {
        return InventoryCategory::active()
            ->ordered()
            ->get(['id', 'name', 'code', 'type']);
    }

    /**
     * Create a new inventory category.
     */
    public function create(array $input): ?InventoryCategory
    {
        return InventoryCategory::create([
            'name' => $input['name'],
            'code' => $input['code'],
            'type' => $input['type'],
            'description' => $input['description'] ?? null,
            'sort_order' => $input['sort_order'] ?? 0,
            'is_active' => $input['is_active'] ?? true,
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Find an inventory category by ID.
     */
    public function find(int $id): ?InventoryCategory
    {
        return InventoryCategory::find($id);
    }

    /**
     * Update an existing inventory category.
     */
    public function update(int $id, array $input): bool
    {
        return (bool) InventoryCategory::where('id', $id)->update([
            'name' => $input['name'],
            'code' => $input['code'],
            'type' => $input['type'],
            'description' => $input['description'] ?? null,
            'sort_order' => $input['sort_order'] ?? 0,
            'is_active' => $input['is_active'] ?? true,
        ]);
    }

    /**
     * Delete an inventory category.
     * Returns false if the category has items.
     */
    public function delete(int $id): array
    {
        $category = InventoryCategory::find($id);
        if ($category && $category->items()->count() > 0) {
            return ['success' => false, 'has_items' => true];
        }

        $success = (bool) InventoryCategory::where('id', $id)->delete();

        return ['success' => $success, 'has_items' => false];
    }
}
