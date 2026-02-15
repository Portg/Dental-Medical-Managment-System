<?php

namespace App\Services;

use App\Diagnosis;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DiagnosisService
{
    /**
     * Get diagnoses for a patient.
     */
    public function getByPatient(int $patientId): Collection
    {
        return DB::table('diagnoses')
            ->leftJoin('medical_cases', 'medical_cases.id', 'diagnoses.medical_case_id')
            ->leftJoin('users', 'users.id', 'diagnoses._who_added')
            ->whereNull('diagnoses.deleted_at')
            ->where('diagnoses.patient_id', $patientId)
            ->orderBy('diagnoses.diagnosis_date', 'desc')
            ->select(
                'diagnoses.*',
                'medical_cases.case_no',
                'medical_cases.title as case_title',
                DB::raw(app()->getLocale() === 'zh-CN' ? "CONCAT(users.surname, users.othername) as added_by" : "CONCAT(users.surname, ' ', users.othername) as added_by")
            )
            ->get();
    }

    /**
     * Get diagnoses for a medical case.
     */
    public function getByCase(int $caseId): Collection
    {
        return DB::table('diagnoses')
            ->leftJoin('users', 'users.id', 'diagnoses._who_added')
            ->whereNull('diagnoses.deleted_at')
            ->where('diagnoses.medical_case_id', $caseId)
            ->orderBy('diagnoses.diagnosis_date', 'desc')
            ->select(
                'diagnoses.*',
                DB::raw(app()->getLocale() === 'zh-CN' ? "CONCAT(users.surname, users.othername) as added_by" : "CONCAT(users.surname, ' ', users.othername) as added_by")
            )
            ->get();
    }

    /**
     * Create a new diagnosis.
     */
    public function createDiagnosis(array $data): ?Diagnosis
    {
        return Diagnosis::create([
            'diagnosis_name' => $data['diagnosis_name'],
            'icd_code' => $data['icd_code'] ?? null,
            'diagnosis_date' => $data['diagnosis_date'],
            'status' => $data['status'] ?? 'Active',
            'severity' => $data['severity'] ?? null,
            'notes' => $data['notes'] ?? null,
            'medical_case_id' => $data['medical_case_id'] ?? null,
            'patient_id' => $data['patient_id'],
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Find a diagnosis by ID.
     */
    public function find(int $id): ?Diagnosis
    {
        return Diagnosis::where('id', $id)->first();
    }

    /**
     * Update an existing diagnosis.
     */
    public function updateDiagnosis(int $id, array $data): bool
    {
        $updateData = [
            'diagnosis_name' => $data['diagnosis_name'],
            'icd_code' => $data['icd_code'] ?? null,
            'diagnosis_date' => $data['diagnosis_date'],
            'status' => $data['status'] ?? null,
            'severity' => $data['severity'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];

        if (($data['status'] ?? null) == 'Resolved' && empty($data['resolved_date'])) {
            $updateData['resolved_date'] = now();
        } elseif (!empty($data['resolved_date'])) {
            $updateData['resolved_date'] = $data['resolved_date'];
        }

        return (bool) Diagnosis::where('id', $id)->update($updateData);
    }

    /**
     * Delete a diagnosis (soft-delete).
     */
    public function deleteDiagnosis(int $id): bool
    {
        return (bool) Diagnosis::where('id', $id)->delete();
    }
}
