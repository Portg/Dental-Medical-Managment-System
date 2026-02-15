<?php

namespace App\Services;

use App\ProgressNote;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProgressNoteService
{
    /**
     * Get progress notes for a specific patient.
     */
    public function getNotesByPatient(int $patientId): Collection
    {
        return DB::table('progress_notes')
            ->leftJoin('medical_cases', 'medical_cases.id', 'progress_notes.medical_case_id')
            ->leftJoin('appointments', 'appointments.id', 'progress_notes.appointment_id')
            ->leftJoin('users', 'users.id', 'progress_notes._who_added')
            ->whereNull('progress_notes.deleted_at')
            ->where('progress_notes.patient_id', $patientId)
            ->orderBy('progress_notes.note_date', 'desc')
            ->select(
                'progress_notes.*',
                'medical_cases.case_no',
                'medical_cases.title as case_title',
                'appointments.appointment_no',
                DB::raw(app()->getLocale() === 'zh-CN' ? "CONCAT(users.surname, users.othername) as added_by" : "CONCAT(users.surname, ' ', users.othername) as added_by")
            )
            ->get();
    }

    /**
     * Get progress notes for a specific medical case.
     */
    public function getNotesByCase(int $caseId): Collection
    {
        return DB::table('progress_notes')
            ->leftJoin('appointments', 'appointments.id', 'progress_notes.appointment_id')
            ->leftJoin('users', 'users.id', 'progress_notes._who_added')
            ->whereNull('progress_notes.deleted_at')
            ->where('progress_notes.medical_case_id', $caseId)
            ->orderBy('progress_notes.note_date', 'desc')
            ->select(
                'progress_notes.*',
                'appointments.appointment_no',
                DB::raw(app()->getLocale() === 'zh-CN' ? "CONCAT(users.surname, users.othername) as added_by" : "CONCAT(users.surname, ' ', users.othername) as added_by")
            )
            ->get();
    }

    /**
     * Create a new progress note.
     */
    public function createNote(array $data): ?ProgressNote
    {
        return ProgressNote::create([
            'subjective' => $data['subjective'] ?? null,
            'objective' => $data['objective'] ?? null,
            'assessment' => $data['assessment'] ?? null,
            'plan' => $data['plan'] ?? null,
            'note_date' => $data['note_date'],
            'note_type' => $data['note_type'] ?? 'SOAP',
            'appointment_id' => $data['appointment_id'] ?? null,
            'medical_case_id' => $data['medical_case_id'] ?? null,
            'patient_id' => $data['patient_id'],
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Get a progress note with relations for viewing.
     */
    public function getNoteWithRelations(int $id): ProgressNote
    {
        return ProgressNote::with(['patient', 'medicalCase', 'appointment', 'addedBy'])->findOrFail($id);
    }

    /**
     * Get a progress note for editing.
     */
    public function getNoteForEdit(int $id): ?ProgressNote
    {
        return ProgressNote::where('id', $id)->first();
    }

    /**
     * Update a progress note.
     */
    public function updateNote(int $id, array $data): bool
    {
        return (bool) ProgressNote::where('id', $id)->update([
            'subjective' => $data['subjective'] ?? null,
            'objective' => $data['objective'] ?? null,
            'assessment' => $data['assessment'] ?? null,
            'plan' => $data['plan'] ?? null,
            'note_date' => $data['note_date'],
            'note_type' => $data['note_type'] ?? null,
        ]);
    }

    /**
     * Delete a progress note (soft-delete).
     */
    public function deleteNote(int $id): bool
    {
        return (bool) ProgressNote::where('id', $id)->delete();
    }
}
