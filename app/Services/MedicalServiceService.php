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
    public function getServiceList(?string $search): Collection
    {
        $query = DB::table('medical_services')
            ->leftJoin('users', 'users.id', 'medical_services._who_added')
            ->whereNull('medical_services.deleted_at')
            ->select(['medical_services.*', 'users.surname']);

        if ($search) {
            $query->where('medical_services.name', 'like', '%' . $search . '%');
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
    public function getServiceForEdit(int $id): ?MedicalService
    {
        return MedicalService::where('id', $id)->first();
    }

    /**
     * Create a new medical service.
     */
    public function createService(array $data): ?MedicalService
    {
        $service = MedicalService::create([
            'name' => $data['name'],
            'price' => $data['price'],
            '_who_added' => Auth::User()->id,
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
            'name' => $data['name'],
            'price' => $data['price'],
            '_who_added' => Auth::User()->id,
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
}
