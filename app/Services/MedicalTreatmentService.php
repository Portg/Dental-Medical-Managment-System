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
        $appointment = DB::table('appointments')
            ->where('id', $appointmentId)
            ->whereNull('deleted_at')
            ->first(['patient_id', 'doctor_id']);

        // Use Eloquent so Blade can access accessors like full_name
        $patient = $appointment?->patient_id ? Patient::find($appointment->patient_id) : null;

        $medicalCards = collect();
        if ($patient) {
            $medicalCards = DB::table('medical_card_items')
                ->join('medical_cards', 'medical_cards.id', 'medical_card_items.medical_card_id')
                ->whereNull('medical_card_items.deleted_at')
                ->where('medical_cards.patient_id', $patient->id)
                ->get();
        }

        $doctorId = $appointment->doctor_id ?? null;
        $doctor = $doctorId ? \App\User::find($doctorId) : null;

        return [
            'patient' => $patient,
            'medical_cards' => $medicalCards,
            'appointment_id' => $appointmentId,
            // 划价面板的「操作医生」下拉，与患者页同一份列表
            'doctors' => \App\User::activeDoctorOptions(),
            // 开加工单预填接诊医生（与本次预约一致）
            'doctor_id' => $doctorId,
            'doctor_text' => $doctor ? trim(($doctor->surname ?? '') . ($doctor->othername ?? '')) : null,
        ];
    }
}
