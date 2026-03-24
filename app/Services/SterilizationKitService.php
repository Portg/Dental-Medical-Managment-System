<?php

namespace App\Services;

use App\SterilizationKit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SterilizationKitService
{
    public function getAll(): \Illuminate\Database\Eloquent\Collection
    {
        return SterilizationKit::whereNull('deleted_at')
            ->with('instruments')
            ->orderBy('kit_no')
            ->get();
    }

    public function create(array $data): SterilizationKit
    {
        return DB::transaction(function () use ($data) {
            $kit = SterilizationKit::create([
                'kit_no'     => $data['kit_no'],
                'name'       => $data['name'],
                'is_active'  => $data['is_active'] ?? true,
                '_who_added' => Auth::id(),
            ]);
            $this->syncInstruments($kit, $data['instruments'] ?? []);
            return $kit;
        });
    }

    public function update(int $id, array $data): bool
    {
        return DB::transaction(function () use ($id, $data) {
            $kit = SterilizationKit::findOrFail($id);
            $kit->update([
                'kit_no'    => $data['kit_no'],
                'name'      => $data['name'],
                'is_active' => $data['is_active'] ?? true,
            ]);
            $this->syncInstruments($kit, $data['instruments'] ?? []);
            return true;
        });
    }

    public function delete(int $id): bool
    {
        return (bool) SterilizationKit::where('id', $id)->delete();
    }

    /** 先删后插明细（物理删除，无 deleted_at） */
    private function syncInstruments(SterilizationKit $kit, array $instruments): void
    {
        $kit->instruments()->delete();
        foreach ($instruments as $i => $inst) {
            $kit->instruments()->create([
                'instrument_name' => $inst['instrument_name'],
                'quantity'        => $inst['quantity'] ?? 1,
                'sort_order'      => $i,
            ]);
        }
    }
}
