<?php

namespace App\Services;

use App\Lab;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LabService
{
    /**
     * Get lab list for DataTables.
     */
    public function getLabList(): Collection
    {
        return DB::table('labs')
            ->leftJoin('users', 'users.id', 'labs._who_added')
            ->whereNull('labs.deleted_at')
            ->select('labs.*', 'users.othername as added_by_name')
            ->orderBy('labs.name')
            ->get();
    }

    /**
     * Get active labs for select dropdown.
     */
    public function getActiveLabsForSelect(): Collection
    {
        return Lab::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'specialties', 'avg_turnaround_days']);
    }

    /**
     * Create a lab.
     */
    public function createLab(array $data): Lab
    {
        return Lab::create($data);
    }

    /**
     * Update a lab.
     */
    public function updateLab(int $id, array $data): bool
    {
        return (bool) Lab::where('id', $id)->update($data);
    }

    /**
     * Delete a lab (soft).
     */
    public function deleteLab(int $id): bool
    {
        $lab = Lab::findOrFail($id);

        // Prevent deletion if lab has active cases
        $activeCases = DB::table('lab_cases')
            ->where('lab_id', $id)
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['completed', 'rework'])
            ->count();

        if ($activeCases > 0) {
            return false;
        }

        return (bool) $lab->delete();
    }

    /**
     * Get a single lab.
     */
    public function getLab(int $id): ?Lab
    {
        return Lab::find($id);
    }
}
