<?php

namespace App\Services;

use App\Patient;
use App\PatientFollowup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PatientFollowupService
{
    /**
     * Get all followups for DataTables.
     */
    public function getAllFollowups(): Collection
    {
        return DB::table('patient_followups')
            ->leftJoin('patients', 'patients.id', 'patient_followups.patient_id')
            ->leftJoin('users as added_by', 'added_by.id', 'patient_followups._who_added')
            ->whereNull('patient_followups.deleted_at')
            ->orderBy('patient_followups.scheduled_date', 'desc')
            ->select(
                'patient_followups.*',
                DB::raw(app()->getLocale() === 'zh-CN' ? "CONCAT(patients.surname, patients.othername) as patient_name" : "CONCAT(patients.surname, ' ', patients.othername) as patient_name"),
                'patients.patient_no',
                'patients.phone_no',
                DB::raw(app()->getLocale() === 'zh-CN' ? "CONCAT(added_by.surname, added_by.othername) as added_by_name" : "CONCAT(added_by.surname, ' ', added_by.othername) as added_by_name")
            )
            ->get();
    }

    /**
     * Get followups for a specific patient.
     */
    public function getPatientFollowups(int $patientId): Collection
    {
        return DB::table('patient_followups')
            ->leftJoin('users as added_by', 'added_by.id', 'patient_followups._who_added')
            ->whereNull('patient_followups.deleted_at')
            ->where('patient_followups.patient_id', $patientId)
            ->orderBy('patient_followups.scheduled_date', 'desc')
            ->select(
                'patient_followups.*',
                DB::raw(app()->getLocale() === 'zh-CN' ? "CONCAT(added_by.surname, added_by.othername) as added_by_name" : "CONCAT(added_by.surname, ' ', added_by.othername) as added_by_name")
            )
            ->get();
    }

    /**
     * Get pending followups within a date range.
     */
    public function getPendingFollowups(int $days = 7): Collection
    {
        return DB::table('patient_followups')
            ->leftJoin('patients', 'patients.id', 'patient_followups.patient_id')
            ->whereNull('patient_followups.deleted_at')
            ->where('patient_followups.status', 'Pending')
            ->whereBetween('patient_followups.scheduled_date', [now()->toDateString(), now()->addDays($days)->toDateString()])
            ->orderBy('patient_followups.scheduled_date', 'asc')
            ->select(
                'patient_followups.*',
                DB::raw(app()->getLocale() === 'zh-CN' ? "CONCAT(patients.surname, patients.othername) as patient_name" : "CONCAT(patients.surname, ' ', patients.othername) as patient_name"),
                'patients.phone_no'
            )
            ->get();
    }

    /**
     * Get overdue followups.
     */
    public function getOverdueFollowups(): Collection
    {
        return DB::table('patient_followups')
            ->leftJoin('patients', 'patients.id', 'patient_followups.patient_id')
            ->whereNull('patient_followups.deleted_at')
            ->where('patient_followups.status', 'Pending')
            ->where('patient_followups.scheduled_date', '<', now()->toDateString())
            ->orderBy('patient_followups.scheduled_date', 'asc')
            ->select(
                'patient_followups.*',
                DB::raw(app()->getLocale() === 'zh-CN' ? "CONCAT(patients.surname, patients.othername) as patient_name" : "CONCAT(patients.surname, ' ', patients.othername) as patient_name"),
                'patients.phone_no'
            )
            ->get();
    }

    /**
     * Get followup detail with relationships.
     */
    public function getFollowupDetail(int $id): PatientFollowup
    {
        return PatientFollowup::with(['patient', 'addedBy'])->findOrFail($id);
    }

    /**
     * Get followup for editing.
     */
    public function getFollowupForEdit(int $id): ?PatientFollowup
    {
        return PatientFollowup::where('id', $id)->first();
    }

    /**
     * Create a new followup.
     */
    public function createFollowup(array $data): ?PatientFollowup
    {
        return PatientFollowup::create([
            'followup_no' => PatientFollowup::generateFollowupNo(),
            'followup_type' => $data['followup_type'],
            'scheduled_date' => $data['scheduled_date'],
            'status' => 'Pending',
            'purpose' => $data['purpose'],
            'notes' => $data['notes'] ?? null,
            'next_followup_date' => $data['next_followup_date'] ?? null,
            'patient_id' => $data['patient_id'],
            'appointment_id' => $data['appointment_id'] ?? null,
            'medical_case_id' => $data['medical_case_id'] ?? null,
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Update an existing followup.
     */
    public function updateFollowup(int $id, array $data): bool
    {
        $updateData = [
            'followup_type' => $data['followup_type'],
            'scheduled_date' => $data['scheduled_date'],
            'status' => $data['status'],
            'purpose' => $data['purpose'],
            'notes' => $data['notes'] ?? null,
            'outcome' => $data['outcome'] ?? null,
            'next_followup_date' => $data['next_followup_date'] ?? null,
        ];

        if ($data['status'] == 'Completed') {
            $updateData['completed_date'] = now();
        }

        return (bool) PatientFollowup::where('id', $id)->update($updateData);
    }

    /**
     * Mark a followup as complete.
     */
    public function completeFollowup(int $id, ?string $outcome = null): bool
    {
        return (bool) PatientFollowup::where('id', $id)->update([
            'status' => 'Completed',
            'completed_date' => now(),
            'outcome' => $outcome,
        ]);
    }

    /**
     * Delete a followup (soft-delete).
     */
    public function deleteFollowup(int $id): bool
    {
        return (bool) PatientFollowup::where('id', $id)->delete();
    }

    /**
     * Get all patients for the index view.
     */
    public function getAllPatients(): Collection
    {
        return Patient::whereNull('deleted_at')->orderBy('surname')->get();
    }
}
