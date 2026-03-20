<?php

namespace App\Services;

use App\Shift;
use Illuminate\Support\Collection;

class ShiftService
{
    public function getAllShifts(): Collection
    {
        return Shift::ordered()->get();
    }

    public function createShift(array $data): Shift
    {
        $maxSort = Shift::max('sort_order') ?? 0;
        $data['sort_order'] = $maxSort + 1;
        $data['_who_added'] = auth()->id();

        return Shift::create($data);
    }

    public function find(int $id): ?Shift
    {
        return Shift::find($id);
    }

    public function updateShift(int $id, array $data): bool
    {
        $shift = Shift::findOrFail($id);
        return $shift->update($data);
    }

    /**
     * Delete shift if not referenced by any schedule (AG-035).
     *
     * @return array{success: bool, message: string}
     */
    public function deleteShift(int $id): array
    {
        $shift = Shift::findOrFail($id);

        $refCount = $shift->schedules()->whereNull('doctor_schedules.deleted_at')->count();
        if ($refCount > 0) {
            return [
                'success' => false,
                'message' => __('shifts.delete_has_references', ['count' => $refCount]),
            ];
        }

        $shift->delete();

        return ['success' => true, 'message' => __('shifts.deleted_successfully')];
    }

    /**
     * Reorder shifts by given ID array.
     */
    public function reorder(array $ids): void
    {
        foreach ($ids as $index => $id) {
            Shift::where('id', $id)->update(['sort_order' => $index + 1]);
        }
    }
}
