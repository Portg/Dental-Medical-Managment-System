<?php

namespace App\Services;

use App\ServicePackage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServicePackageService
{
    public function getAll(): \Illuminate\Database\Eloquent\Collection
    {
        return ServicePackage::whereNull('deleted_at')
            ->with('items.service')
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): ServicePackage
    {
        return DB::transaction(function () use ($data) {
            $pkg = ServicePackage::create([
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
                'total_price' => $data['total_price'],
                'is_active'   => $data['is_active'] ?? true,
                '_who_added'  => Auth::id(),
            ]);
            $this->syncItems($pkg, $data['items'] ?? []);
            return $pkg;
        });
    }

    public function update(int $id, array $data): bool
    {
        return DB::transaction(function () use ($id, $data) {
            $pkg = ServicePackage::findOrFail($id);
            $pkg->update([
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
                'total_price' => $data['total_price'],
                'is_active'   => $data['is_active'] ?? true,
            ]);
            $this->syncItems($pkg, $data['items'] ?? []);
            return true;
        });
    }

    public function delete(int $id): bool
    {
        return (bool) ServicePackage::where('id', $id)->delete();
    }

    /** 先删后插套餐明细（物理删除，service_package_items 无 deleted_at） */
    private function syncItems(ServicePackage $pkg, array $items): void
    {
        $pkg->items()->delete();
        foreach ($items as $i => $item) {
            $pkg->items()->create([
                'service_id' => $item['service_id'],
                'qty'        => $item['qty'] ?? 1,
                'price'      => $item['price'],
                'sort_order' => $i,
            ]);
        }
    }
}
