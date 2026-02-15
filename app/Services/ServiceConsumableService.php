<?php

namespace App\Services;

use App\ServiceConsumable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ServiceConsumableService
{
    /**
     * Get consumables list for DataTables, optionally filtered by service.
     */
    public function getList(?int $medicalServiceId): Collection
    {
        $query = ServiceConsumable::with(['medicalService', 'inventoryItem']);

        if ($medicalServiceId) {
            $query->where('medical_service_id', $medicalServiceId);
        }

        return $query->orderBy('id', 'DESC')->get();
    }

    /**
     * Get consumables for a specific service.
     */
    public function getByService(int $serviceId): Collection
    {
        return ServiceConsumable::with('inventoryItem')
            ->where('medical_service_id', $serviceId)
            ->get();
    }

    /**
     * Check if a consumable already exists for the given service and item.
     */
    public function exists(int $medicalServiceId, int $inventoryItemId): bool
    {
        return ServiceConsumable::where('medical_service_id', $medicalServiceId)
            ->where('inventory_item_id', $inventoryItemId)
            ->exists();
    }

    /**
     * Create a new service consumable.
     */
    public function create(array $data): ?ServiceConsumable
    {
        return ServiceConsumable::create([
            'medical_service_id' => $data['medical_service_id'],
            'inventory_item_id' => $data['inventory_item_id'],
            'qty' => $data['qty'],
            'is_required' => $data['is_required'] ?? true,
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Update a service consumable.
     */
    public function update(int $id, array $data): bool
    {
        return (bool) ServiceConsumable::where('id', $id)->update([
            'qty' => $data['qty'],
            'is_required' => $data['is_required'] ?? true,
        ]);
    }

    /**
     * Delete a service consumable.
     */
    public function delete(int $id): bool
    {
        return (bool) ServiceConsumable::where('id', $id)->delete();
    }
}
