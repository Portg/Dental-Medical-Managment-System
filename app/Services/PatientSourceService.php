<?php

namespace App\Services;

use App\PatientSource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PatientSourceService
{
    private const CACHE_KEY_ACTIVE = 'patient_sources:active';
    private const CACHE_TTL = 21600; // 6h

    /**
     * Get filtered source list for DataTables.
     */
    public function getSourceList(array $filters): Collection
    {
        $query = DB::table('patient_sources')
            ->leftJoin('users', 'users.id', 'patient_sources._who_added')
            ->whereNull('patient_sources.deleted_at')
            ->select(
                'patient_sources.*',
                'users.surname as added_by_name'
            );

        // Quick search filter
        if (!empty($filters['quick_search'])) {
            $search = $filters['quick_search'];
            $query->where(function ($q) use ($search) {
                $q->where('patient_sources.name', 'like', '%' . $search . '%')
                  ->orWhere('patient_sources.code', 'like', '%' . $search . '%');
            });
        }

        // Status filter (use is_numeric to handle '0' correctly)
        if (isset($filters['status']) && is_numeric($filters['status'])) {
            $query->where('patient_sources.is_active', $filters['status']);
        }

        return $query->orderBy('patient_sources.name', 'asc')->get();
    }

    /**
     * Get active sources for Select2 dropdown.
     */
    public function getActiveSourcesForSelect(?string $search = null): Collection
    {
        if ($search) {
            return PatientSource::active()
                ->orderBy('name', 'asc')
                ->where('name', 'like', '%' . $search . '%')
                ->select('id', 'name', 'code')
                ->get()
                ->map(function ($source) {
                    return ['id' => $source->id, 'text' => $source->name];
                });
        }

        return Cache::remember(self::CACHE_KEY_ACTIVE, self::CACHE_TTL, function () {
            return PatientSource::active()
                ->orderBy('name', 'asc')
                ->select('id', 'name', 'code')
                ->get()
                ->map(function ($source) {
                    return ['id' => $source->id, 'text' => $source->name];
                });
        });
    }

    /**
     * Create a new patient source.
     */
    public function createSource(array $data): ?PatientSource
    {
        $data['_who_added'] = Auth::user()->id;

        $source = PatientSource::create($data);

        Cache::forget(self::CACHE_KEY_ACTIVE);

        return $source;
    }

    /**
     * Get a single source by ID.
     */
    public function getSource(int $id): PatientSource
    {
        return PatientSource::findOrFail($id);
    }

    /**
     * Update a patient source.
     */
    public function updateSource(int $id, array $data): bool
    {
        $result = (bool) PatientSource::where('id', $id)->update($data);

        Cache::forget(self::CACHE_KEY_ACTIVE);

        return $result;
    }

    /**
     * Check if source is in use by patients.
     */
    public function isSourceInUse(int $id): bool
    {
        return DB::table('patients')
            ->where('source_id', $id)
            ->whereNull('deleted_at')
            ->count() > 0;
    }

    /**
     * Delete a patient source (soft-delete).
     */
    public function deleteSource(int $id): bool
    {
        $result = (bool) PatientSource::where('id', $id)->delete();

        Cache::forget(self::CACHE_KEY_ACTIVE);

        return $result;
    }
}
