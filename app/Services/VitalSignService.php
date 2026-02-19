<?php

namespace App\Services;

use App\MedicalCase;
use App\VitalSign;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VitalSignService
{
    /**
     * Get vital signs for a patient.
     */
    public function getByPatient(int $patientId): Collection
    {
        return DB::table('vital_signs')
            ->leftJoin('appointments', 'appointments.id', 'vital_signs.appointment_id')
            ->leftJoin('users', 'users.id', 'vital_signs._who_added')
            ->whereNull('vital_signs.deleted_at')
            ->where('vital_signs.patient_id', $patientId)
            ->orderBy('vital_signs.recorded_at', 'desc')
            ->select(
                'vital_signs.*',
                'appointments.appointment_no',
                DB::raw(app()->getLocale() === 'zh-CN' ? "CONCAT(users.surname, users.othername) as added_by" : "CONCAT(users.surname, ' ', users.othername) as added_by")
            )
            ->get();
    }

    /**
     * Get the patient_id from a medical case, or null if not found.
     */
    public function getPatientIdFromCase(int $caseId): ?int
    {
        $medicalCase = MedicalCase::find($caseId);

        return $medicalCase ? (int) $medicalCase->patient_id : null;
    }

    /**
     * Create a new vital sign record.
     */
    public function createVitalSign(array $data): ?VitalSign
    {
        $data['_who_added'] = Auth::User()->id;

        return VitalSign::create($data);
    }

    /**
     * Get a single vital sign for editing.
     */
    public function getVitalSign(int $id): ?VitalSign
    {
        return VitalSign::where('id', $id)->first();
    }

    /**
     * Update a vital sign record.
     */
    public function updateVitalSign(int $id, array $data): bool
    {
        return (bool) VitalSign::where('id', $id)->update($data);
    }

    /**
     * Delete a vital sign record (soft-delete).
     */
    public function deleteVitalSign(int $id): bool
    {
        return (bool) VitalSign::where('id', $id)->delete();
    }

    /**
     * Get the latest vital sign for a patient.
     */
    public function getLatestForPatient(int $patientId): ?VitalSign
    {
        return VitalSign::where('patient_id', $patientId)
            ->orderBy('recorded_at', 'desc')
            ->first();
    }
}
