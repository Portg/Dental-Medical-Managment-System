<?php

namespace App\Services;

use App\Supplier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
            'name'                 => $data['name'],
            'contact_person'       => $data['contact_person'] ?? null,
            'phone'                => $data['phone'] ?? null,
            'email'                => $data['email'] ?? null,
            'address'              => $data['address'] ?? null,
            'notes'                => $data['notes'] ?? null,
            'business_license_no'  => $data['business_license_no'] ?? null,
            'license_expiry_date'  => $data['license_expiry_date'] ?: null,
            '_who_added'           => Auth::user()->id,
        ]);
    }

    /**
     * Update a supplier.
     */
    public function updateSupplier(int $id, array $data): bool
    {
        return (bool) Supplier::where('id', $id)->update([
            'name'                 => $data['name'],
            'contact_person'       => $data['contact_person'] ?? null,
            'phone'                => $data['phone'] ?? null,
            'email'                => $data['email'] ?? null,
            'address'              => $data['address'] ?? null,
            'notes'                => $data['notes'] ?? null,
            'business_license_no'  => $data['business_license_no'] ?? null,
            'license_expiry_date'  => $data['license_expiry_date'] ?: null,
        ]);
    }

    /**
     * Delete a supplier (AG-061: 有关联入库单的供应商不可删除).
     */
    public function deleteSupplier(int $id): array
    {
        $hasStockIns = DB::table('stock_ins')
            ->where('supplier_id', $id)
            ->whereNull('deleted_at')
            ->exists();

        if ($hasStockIns) {
            return ['status' => false, 'message' => __('suppliers.supplier_has_stock_ins')];
        }

        $deleted = (bool) Supplier::where('id', $id)->delete();
        return [
            'status'  => $deleted,
            'message' => $deleted
                ? __('common.supplier_deleted_successfully')
                : __('messages.error_occurred'),
        ];
    }
}
