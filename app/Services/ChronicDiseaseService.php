<?php

namespace App\Services;

use App\ChronicDisease;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ChronicDiseaseService
{
    /**
     * Get chronic diseases for a patient (DataTables listing).
     */
    public function getListByPatient(int $patientId): Collection
    {
        return ChronicDisease::where('patient_id', $patientId)
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Find a single chronic disease by ID.
     */
    public function find(int $id): ?ChronicDisease
    {
        return ChronicDisease::where('id', $id)->first();
    }

    /**
     * Create a new chronic disease record.
     */
    public function create(string $disease, string $status, int $patientId): ?ChronicDisease
    {
        return ChronicDisease::create([
            'disease' => $disease,
            'status' => $status,
            'patient_id' => $patientId,
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Update an existing chronic disease record.
     */
    public function update(int $id, string $disease, string $status): bool
    {
        return (bool) ChronicDisease::where('id', $id)->update([
            'disease' => $disease,
            'status' => $status,
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Delete a chronic disease record.
     */
    public function delete(int $id): bool
    {
        return (bool) ChronicDisease::where('id', $id)->delete();
    }
}
