<?php

namespace App\Services;

use App\Surgery;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class SurgeryService
{
    /**
     * Get surgeries for a patient (DataTables listing).
     */
    public function getListByPatient(int $patientId): Collection
    {
        return Surgery::where('patient_id', $patientId)->get();
    }

    /**
     * Find a single surgery by ID.
     */
    public function find(int $id): ?Surgery
    {
        return Surgery::where('id', $id)->first();
    }

    /**
     * Create a new surgery record.
     */
    public function create(string $surgery, string $surgeryDate, ?string $description, int $patientId): ?Surgery
    {
        return Surgery::create([
            'surgery' => $surgery,
            'surgery_date' => $surgeryDate,
            'description' => $description,
            'patient_id' => $patientId,
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Update an existing surgery record.
     */
    public function update(int $id, string $surgery, string $surgeryDate, ?string $description): bool
    {
        return (bool) Surgery::where('id', $id)->update([
            'surgery' => $surgery,
            'surgery_date' => $surgeryDate,
            'description' => $description,
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Delete a surgery record.
     */
    public function delete(int $id): bool
    {
        return (bool) Surgery::where('id', $id)->delete();
    }
}
