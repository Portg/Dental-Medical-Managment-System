<?php

namespace App\Services;

use App\ServiceCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ServiceCategoryService
{
    private const CACHE_KEY = 'service_category_list';
    private const CACHE_TTL = 3600 * 6;

    public function getAll(): \Illuminate\Database\Eloquent\Collection
    {
        return ServiceCategory::whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'sort_order', 'is_active']);
    }

    public function create(array $data): ServiceCategory
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget('billing_service_category_tree');
        return ServiceCategory::create([
            'name'       => $data['name'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active'  => $data['is_active'] ?? true,
            '_who_added' => Auth::id(),
        ]);
    }

    public function update(int $id, array $data): bool
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget('billing_service_category_tree');
        return (bool) ServiceCategory::where('id', $id)->update([
            'name'       => $data['name'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active'  => $data['is_active'] ?? true,
        ]);
    }

    public function delete(int $id): bool
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget('billing_service_category_tree');
        return (bool) ServiceCategory::where('id', $id)->delete();
    }

    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $pos => $id) {
            ServiceCategory::where('id', $id)->update(['sort_order' => $pos + 1]);
        }
        Cache::forget(self::CACHE_KEY);
        Cache::forget('billing_service_category_tree');
    }
}
