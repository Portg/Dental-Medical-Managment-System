<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MedicalTreatmentService
{
    /**
     * Get medical treatment data for an appointment.
     */
    public function getTreatmentDataForAppointment(int $appointmentId): array
    {
        $patient = DB::table('appointments')
            ->join('patients', 'patients.id', 'appointments.patient_id')
            ->where('appointments.id', $appointmentId)
            ->select('patients.*')
            ->first();

        $patientId = '';
        if ($patient != null) {
            $patientId = $patient->id;
        }

        $medicalCards = DB::table('medical_card_items')
            ->join('medical_cards', 'medical_cards.id', 'medical_card_items.medical_card_id')
            ->whereNull('medical_card_items.deleted_at')
            ->where('medical_cards.patient_id', $patientId)
            ->get();

        return [
            'patient' => $patient,
            'medical_cards' => $medicalCards,
            'appointment_id' => $appointmentId,
        ];
    }
}
