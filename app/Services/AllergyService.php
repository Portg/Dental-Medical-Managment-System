<?php

namespace App\Services;

use App\Allergy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class AllergyService
{
    /**
     * Get allergies for a patient (DataTables listing).
     */
    public function getListByPatient(int $patientId): Collection
    {
        return Allergy::where('patient_id', $patientId)
            ->orderBy('updated_at', 'DESC')
            ->get();
    }

    /**
     * Find a single allergy by ID.
     */
    public function find(int $id): ?Allergy
    {
        return Allergy::where('id', $id)->first();
    }

    /**
     * Create a new allergy record.
     */
    public function create(string $bodyReaction, int $patientId): ?Allergy
    {
        return Allergy::create([
            'body_reaction' => $bodyReaction,
            'patient_id' => $patientId,
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Update an existing allergy record.
     */
    public function update(int $id, string $bodyReaction): bool
    {
        return (bool) Allergy::where('id', $id)->update([
            'body_reaction' => $bodyReaction,
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Delete an allergy record.
     */
    public function delete(int $id): bool
    {
        return (bool) Allergy::where('id', $id)->delete();
    }
}
