<?php

namespace App\Services;

use App\Chair;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ChairService
{
    /**
     * Get all chairs with branch and who-added user info for listing.
     */
    public function getChairList(): Collection
    {
        return DB::table('chairs')
            ->leftJoin('users', 'users.id', 'chairs._who_added')
            ->leftJoin('branches', 'branches.id', 'chairs.branch_id')
            ->whereNull('chairs.deleted_at')
            ->select(['chairs.*', 'users.surname', 'branches.name as branch_name'])
            ->orderBy('chairs.id', 'desc')
            ->get();
    }

    /**
     * Find a chair by ID.
     */
    public function findChair(int $id): ?Chair
    {
        return Chair::where('id', $id)->first();
    }

    /**
     * Create a new chair.
     */
    public function createChair(array $data, int $userId): ?Chair
    {
        $data['_who_added'] = $userId;
        return Chair::create($data);
    }

    /**
     * Update an existing chair.
     */
    public function updateChair(int $id, array $data): bool
    {
        return (bool) Chair::where('id', $id)->update($data);
    }

    /**
     * Delete a chair (soft-delete).
     * Returns false if chair has active appointments.
     */
    public function deleteChair(int $id): bool
    {
        $chair = Chair::find($id);
        if (!$chair) {
            return false;
        }

        // Check for active appointments
        $activeCount = $chair->appointments()
            ->whereNull('deleted_at')
            ->whereIn('status', ['Scheduled', 'In Progress', 'Waiting'])
            ->count();

        if ($activeCount > 0) {
            return false;
        }

        return (bool) $chair->delete();
    }
}
