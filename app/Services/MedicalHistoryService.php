<?php

namespace App\Services;

use App\Patient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MedicalHistoryService
{
    /**
     * Get medical history data for a patient.
     */
    public function getMedicalHistoryForPatient(int $patientId): array
    {
        $patient = Patient::findOrFail($patientId);

        $medicalCards = DB::table('medical_card_items')
            ->join('medical_cards', 'medical_cards.id', 'medical_card_items.medical_card_id')
            ->whereNull('medical_card_items.deleted_at')
            ->where('medical_cards.patient_id', $patientId)
            ->get();

        return [
            'patient' => $patient,
            'medical_cards' => $medicalCards,
        ];
    }
}
