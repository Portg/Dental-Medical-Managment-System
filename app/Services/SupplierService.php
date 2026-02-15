<?php

namespace App\Services;

use App\Supplier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class SupplierService
{
    /**
     * Get all suppliers ordered by updated_at.
     */
    public function getSupplierList(): Collection
    {
        return Supplier::orderBy('updated_at', 'DESC')->get();
    }

    /**
     * Get supplier names for autocomplete.
     */
    public function filterSuppliers(): array
    {
        return Supplier::select('name')->get()->pluck('name')->toArray();
    }

    /**
     * Get supplier for editing.
     */
    public function getSupplier(int $id): ?Supplier
    {
        return Supplier::where('id', $id)->first();
    }

    /**
     * Create a new supplier.
     */
    public function createSupplier(array $data): ?Supplier
    {
        return Supplier::create([
            'name' => $data['name'],
            '_who_added' => Auth::user()->id,
        ]);
    }

    /**
     * Update a supplier.
     */
    public function updateSupplier(int $id, array $data): bool
    {
        return (bool) Supplier::where('id', $id)->update([
            'name' => $data['name'],
            '_who_added' => Auth::user()->id,
        ]);
    }

    /**
     * Delete a supplier.
     */
    public function deleteSupplier(int $id): bool
    {
        return (bool) Supplier::where('id', $id)->delete();
    }
}
