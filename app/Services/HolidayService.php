<?php

namespace App\Services;

use App\Holiday;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HolidayService
{
    /**
     * Get filtered holiday list for DataTables.
     */
    public function getHolidayList(array $filters): Collection
    {
        $query = DB::table('holidays')
            ->leftJoin('users', 'users.id', 'holidays._who_added')
            ->whereNull('holidays.deleted_at')
            ->select(['holidays.*', 'users.surname'])
            ->orderBy('holidays.holiday_date');

        if (!empty($filters['filter_name'])) {
            $query->where('holidays.name', 'like', '%' . $filters['filter_name'] . '%');
        }
        if (!empty($filters['filter_repeat'])) {
            $query->where('holidays.repeat_date', $filters['filter_repeat']);
        }

        return $query->get();
    }

    /**
     * Create a new holiday.
     */
    public function createHoliday(array $data): ?Holiday
    {
        return Holiday::create([
            'name' => $data['name'],
            'holiday_date' => $data['holiday_date'],
            'repeat_date' => $data['repeat_date'],
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Find a holiday by ID.
     */
    public function findHoliday(int $id): ?Holiday
    {
        return Holiday::where('id', $id)->first();
    }

    /**
     * Update an existing holiday.
     */
    public function updateHoliday(int $id, array $data): bool
    {
        return (bool) Holiday::where('id', $id)->update([
            'name' => $data['name'],
            'holiday_date' => $data['holiday_date'],
            'repeat_date' => $data['repeat_date'],
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Delete a holiday (soft-delete).
     */
    public function deleteHoliday(int $id): bool
    {
        return (bool) Holiday::where('id', $id)->delete();
    }
}
