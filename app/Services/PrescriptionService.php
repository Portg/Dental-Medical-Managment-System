<?php

namespace App\Services;

use App\Prescription;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PrescriptionService
{
    /**
     * Get all prescriptions for the list-all page.
     */
    public function getAllPrescriptions(): Collection
    {
        return DB::table('prescriptions')
            ->leftJoin('appointments', 'appointments.id', 'prescriptions.appointment_id')
            ->leftJoin('patients', 'patients.id', 'appointments.patient_id')
            ->leftJoin('users', 'users.id', 'prescriptions._who_added')
            ->whereNull('prescriptions.deleted_at')
            ->whereNull('patients.deleted_at')
            ->orderBy('prescriptions.created_at', 'desc')
            ->select(
                'prescriptions.*',
                'patients.patient_no',
                DB::raw(app()->getLocale() === 'zh-CN' ? "CONCAT(patients.surname, patients.othername) as patient_name" : "CONCAT(patients.surname, ' ', patients.othername) as patient_name"),
                DB::raw(app()->getLocale() === 'zh-CN' ? "CONCAT(users.surname, users.othername) as added_by" : "CONCAT(users.surname, ' ', users.othername) as added_by"),
                'appointments.id as appointment_id'
            )
            ->get();
    }

    /**
     * Get prescriptions for a specific appointment.
     */
    public function getPrescriptionsByAppointment(int $appointmentId): Collection
    {
        return Prescription::where('appointment_id', $appointmentId)->get();
    }

    /**
     * Get all unique drug names for autocomplete.
     */
    public function getAllDrugNames(): array
    {
        return Prescription::select('drug')->get()->pluck('drug')->toArray();
    }

    /**
     * Create multiple prescriptions for an appointment.
     */
    public function createPrescriptions(int $appointmentId, array $items): void
    {
        foreach ($items as $value) {
            Prescription::create([
                'drug' => $value['drug'],
                'qty' => $value['qty'],
                'directions' => $value['directions'],
                'appointment_id' => $appointmentId,
                '_who_added' => Auth::User()->id,
            ]);
        }
    }

    /**
     * Get prescription print data.
     */
    public function getPrintData(int $appointmentId): array
    {
        $patient = DB::table('appointments')
            ->leftJoin('patients', 'patients.id', 'appointments.patient_id')
            ->where('appointments.id', $appointmentId)
            ->select('patients.*')
            ->first();

        $prescriptions = Prescription::where('appointment_id', $appointmentId)->get();

        $prescribed_by = DB::table('prescriptions')
            ->join('users', 'users.id', 'prescriptions._who_added')
            ->whereNull('prescriptions.deleted_at')
            ->where('prescriptions.appointment_id', $appointmentId)
            ->select('users.*')
            ->first();

        return compact('patient', 'prescriptions', 'prescribed_by');
    }

    /**
     * Get a single prescription for editing.
     */
    public function getPrescriptionForEdit(int $id): ?Prescription
    {
        return Prescription::where('id', $id)->first();
    }

    /**
     * Update a prescription.
     */
    public function updatePrescription(int $id, array $data): bool
    {
        return (bool) Prescription::where('id', $id)->update([
            'drug' => $data['drug'],
            'qty' => $data['qty'],
            'directions' => $data['directions'],
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Delete a prescription (soft-delete).
     */
    public function deletePrescription(int $id): bool
    {
        return (bool) Prescription::where('id', $id)->delete();
    }
}
