<?php

namespace App\Services;

use App\Treatment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TreatmentService
{
    /**
     * Get treatments for a patient (with added_by info).
     */
    public function getTreatmentsByPatient(int $patientId): Collection
    {
        return DB::table('treatments')
            ->leftJoin('appointments', 'appointments.id', 'treatments.appointment_id')
            ->leftJoin('users', 'users.id', 'treatments._who_added')
            ->whereNull('treatments.deleted_at')
            ->where('appointments.patient_id', $patientId)
            ->orderBy('treatments.updated_at', 'desc')
            ->select('treatments.*', 'users.surname as added_by')
            ->get();
    }

    /**
     * Get treatment history for a patient (with doctor name).
     */
    public function getTreatmentHistory(int $patientId): Collection
    {
        return DB::table('treatments')
            ->join('appointments', 'appointments.id', 'treatments.appointment_id')
            ->join('users', 'users.id', 'treatments._who_added')
            ->where('appointments.patient_id', $patientId)
            ->select('treatments.*', 'users.surname', 'users.othername')
            ->get();
    }

    /**
     * Find a treatment by ID.
     */
    public function find(int $id): ?Treatment
    {
        return Treatment::where('id', $id)->first();
    }

    /**
     * Create a new treatment.
     */
    public function createTreatment(string $clinicalNotes, string $treatment, int $appointmentId): ?Treatment
    {
        return Treatment::create([
            'clinical_notes' => $clinicalNotes,
            'treatment' => $treatment,
            'appointment_id' => $appointmentId,
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Update an existing treatment.
     */
    public function updateTreatment(int $id, string $clinicalNotes, string $treatment, int $appointmentId): bool
    {
        return (bool) Treatment::where('id', $id)->update([
            'clinical_notes' => $clinicalNotes,
            'treatment' => $treatment,
            'appointment_id' => $appointmentId,
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Delete a treatment (soft-delete).
     */
    public function deleteTreatment(int $id): bool
    {
        return (bool) Treatment::where('id', $id)->delete();
    }
}
