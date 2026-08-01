<?php

namespace App\Services;

use App\Patient;
use Illuminate\Support\Facades\DB;

class MedicalTreatmentService
{
    /**
     * Get medical treatment data for an appointment.
     */
    public function getTreatmentDataForAppointment(int $appointmentId): array
    {
        $patientId = DB::table('appointments')
            ->where('id', $appointmentId)
            ->whereNull('deleted_at')
            ->value('patient_id');

        // Use Eloquent so Blade can access accessors like full_name
        $patient = $patientId ? Patient::find($patientId) : null;

        $medicalCards = collect();
        if ($patient) {
            $medicalCards = DB::table('medical_card_items')
                ->join('medical_cards', 'medical_cards.id', 'medical_card_items.medical_card_id')
                ->whereNull('medical_card_items.deleted_at')
                ->where('medical_cards.patient_id', $patient->id)
                ->get();
        }

        return [
            'patient' => $patient,
            'medical_cards' => $medicalCards,
            'appointment_id' => $appointmentId,
        ];
    }
}
