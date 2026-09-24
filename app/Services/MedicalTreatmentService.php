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
            // 开加工单预填牙位：牙位图上做了修复体（牙冠/桥体）的那几颗。
            // 加工单那边的 teeth_positions 是个纯文本框，医生刚在牙位图上标完
            // 17冠-16桥-15冠，转头又要手敲一遍。接收端一直就绪（ctx.teeth），
            // 缺的只是把牙位带过去。
            'lab_teeth' => $patient
                ? implode(', ', app(DentalChartService::class)->prostheticTeethForPatient($patient->id))
                : '',
            // 加工单表上有 medical_case_id 一列，此前这个入口没带上，
            // 于是「这次戴牙对应哪份病历」仍然挂不上。
            'medical_case_id' => $appointment->medical_case_id ?? null,
        ];
    }
}
