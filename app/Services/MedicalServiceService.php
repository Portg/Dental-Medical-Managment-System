<?php

namespace App\Services;

use App\MedicalService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MedicalServiceService
{
    private const CACHE_KEY_NAMES = 'medical_services:names';
    private const CACHE_TTL = 21600; // 6h

    /**
     * Get medical services list for DataTables.
     */
    public function getServiceList(?string $search, ?int $categoryId = null): Collection
    {
        $query = DB::table('medical_services')
            ->leftJoin('users', 'users.id', 'medical_services._who_added')
            ->leftJoin('service_categories', 'service_categories.id', 'medical_services.category_id')
            ->whereNull('medical_services.deleted_at')
            ->select([
                'medical_services.*',
                'users.surname',
                'service_categories.name as category_name',
            ]);

        if ($search) {
            $query->where('medical_services.name', 'like', '%' . $search . '%');
        }
        if ($categoryId) {
            $query->where('medical_services.category_id', $categoryId);
        }

        return $query->orderBy('medical_services.id', 'desc')->get();
    }

    /**
     * Get all service names as a flat array.
     */
    public function getAllServiceNames(): array
    {
        return Cache::remember(self::CACHE_KEY_NAMES, self::CACHE_TTL, function () {
            return MedicalService::select('name')->get()->pluck('name')->toArray();
        });
    }

    /**
     * Search/filter services by name (for Select2).
     */
    public function filterServices(string $keyword): array
    {
        $data = MedicalService::where('name', 'LIKE', "%$keyword%")->get();

        $formatted = [];
        foreach ($data as $tag) {
            $formatted[] = ['id' => $tag->id, 'text' => $tag->name, 'price' => $tag->price];
        }

        return $formatted;
    }

    /**
     * Get a single service for editing.
     */
    public function getServiceForEdit(int $id): ?object
    {
        return DB::table('medical_services')
            ->leftJoin('service_categories', 'service_categories.id', '=', 'medical_services.category_id')
            ->where('medical_services.id', $id)
            ->whereNull('medical_services.deleted_at')
            ->select([
                'medical_services.*',
                'service_categories.name as category_name',
            ])
            ->first();
    }

    /**
     * Create a new medical service.
     */
    public function createService(array $data): ?MedicalService
    {
        $service = MedicalService::create([
            'name'            => $data['name'],
            'price'           => $data['price'],
            'unit'            => $data['unit'] ?? null,
            'description'     => $data['description'] ?? null,
            'category_id'     => $data['category_id'] ?? null,
            'is_active'       => $data['is_active'] ?? true,
            'is_discountable' => $data['is_discountable'] ?? true,
            'is_favorite'     => $data['is_favorite'] ?? false,
            'sort_order'      => $data['sort_order'] ?? 0,
            '_who_added'      => Auth::id(),
        ]);

        Cache::forget(self::CACHE_KEY_NAMES);

        return $service;
    }

    /**
     * Update a medical service.
     */
    public function updateService(int $id, array $data): bool
    {
        $result = (bool) MedicalService::where('id', $id)->update([
            'name'            => $data['name'],
            'price'           => $data['price'],
            'unit'            => $data['unit'] ?? null,
            'description'     => $data['description'] ?? null,
            'category_id'     => $data['category_id'] ?? null,
            'is_active'       => $data['is_active'] ?? true,
            'is_discountable' => $data['is_discountable'] ?? true,
            'is_favorite'     => $data['is_favorite'] ?? false,
            'sort_order'      => $data['sort_order'] ?? 0,
        ]);

        Cache::forget(self::CACHE_KEY_NAMES);

        return $result;
    }

    /**
     * Delete a medical service.
     */
    public function deleteService(int $id): bool
    {
        $result = (bool) MedicalService::where('id', $id)->delete();

        Cache::forget(self::CACHE_KEY_NAMES);

        return $result;
    }

    /**
     * 批量改价：按 category_id（空=全部）调整价格，支持百分比或固定金额。
     * @param array{mode: 'percent'|'fixed', value: float, category_id?: int|null} $data
     */
    public function batchUpdatePrice(array $data): int
    {
        $query = MedicalService::whereNull('deleted_at');
        if (!empty($data['category_id'])) {
            $query->where('category_id', $data['category_id']);
        }
        $services = $query->get(['id', 'price']);
        $count = 0;
        // @AiGenerated: bcmath precision chain — percent: price × (100+value)/100; fixed: price+value; floor at 0.00
        DB::transaction(function () use ($services, $data, &$count) {
            foreach ($services as $svc) {
                $newPrice = $data['mode'] === 'percent'
                    ? bcmul((string) $svc->price, bcdiv((string)(100 + $data['value']), '100', 4), 2)
                    : bcadd((string) $svc->price, (string) $data['value'], 2);
                if (bccomp($newPrice, '0', 2) < 0) {
                    $newPrice = '0.00';
                }
                MedicalService::where('id', $svc->id)->update(['price' => $newPrice]);
                $count++;
            }
        });
        Cache::forget(self::CACHE_KEY_NAMES);
        Cache::forget('billing_service_category_tree');
        return $count;
    }
}
