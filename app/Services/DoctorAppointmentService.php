<?php

namespace App\Services;

use App\Appointment;
use App\DoctorClaim;
use App\Http\Helper\NameHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DoctorAppointmentService
{
    /**
     * Get filtered appointment list for the current doctor.
     */
    public function getAppointmentList(array $filters): Collection
    {
        $doctorId = Auth::User()->id;

        $query = DB::table('appointments')
            ->join('patients', 'patients.id', 'appointments.patient_id')
            ->whereNull('appointments.deleted_at')
            ->where('appointments.doctor_id', $doctorId);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                NameHelper::addNameSearch($q, $filters['search'], 'patients');
            });
            $query->select('appointments.*', 'patients.surname', 'patients.othername');
        } elseif (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween(DB::raw('DATE_FORMAT(appointments.sort_by, \'%Y-%m-%d\')'), [
                $filters['start_date'], $filters['end_date'],
            ]);
            $query->select(
                'appointments.*', 'patients.surname', 'patients.othername',
                DB::raw('DATE_FORMAT(appointments.start_date, "%d-%b-%Y") as start_date')
            );
        } else {
            $query->select('appointments.*', 'patients.surname', 'patients.othername');
        }

        return $query->orderBy('appointments.sort_by', 'desc')->get();
    }

    /**
     * Get calendar events for the current doctor.
     */
    public function getCalendarEvents(?string $start, ?string $end): array
    {
        $query = DB::table('appointments')
            ->join('patients', 'patients.id', 'appointments.patient_id')
            ->whereNull('appointments.deleted_at')
            ->where('appointments.doctor_id', Auth::User()->id)
            ->select('appointments.*', 'patients.surname', 'patients.othername');

        if ($start && $end) {
            $query->whereBetween('appointments.sort_by', [$start, $end]);
        }

        $events = [];
        foreach ($query->get() as $value) {
            $events[] = [
                'title' => NameHelper::join($value->surname, $value->othername),
                'start' => date_format(date_create($value->sort_by), "Y-m-d\TH:i:s"),
                'end' => date_format(date_create($value->sort_by), "Y-m-d\TH:i:s"),
                'textColor' => '#ffffff',
            ];
        }

        return $events;
    }

    /**
     * Check if an appointment already has a doctor claim.
     */
    public function appointmentHasClaim(int $appointmentId): bool
    {
        return DoctorClaim::where('appointment_id', $appointmentId)->exists();
    }

    /**
     * Create a new appointment for the current doctor.
     */
    public function createAppointment(int $patientId, ?string $notes): ?Appointment
    {
        $userId = Auth::User()->id;

        return Appointment::create([
            'appointment_no' => Appointment::AppointmentNo(),
            'patient_id' => $patientId,
            'doctor_id' => $userId,
            'notes' => $notes,
            '_who_added' => $userId,
        ]);
    }

    /**
     * Get appointment detail for editing.
     */
    public function getAppointmentForEdit(int $id): ?object
    {
        return DB::table('appointments')
            ->join('users', 'users.id', 'appointments.doctor_id')
            ->join('patients', 'patients.id', 'appointments.patient_id')
            ->where('appointments.id', $id)
            ->whereNull('appointments.deleted_at')
            ->select(
                'appointments.*',
                'users.surname as d_surname', 'users.othername as d_othername',
                'patients.surname', 'patients.othername'
            )
            ->first();
    }

    /**
     * Update an existing appointment.
     */
    public function updateAppointment(int $id, int $patientId, ?string $notes): bool
    {
        return (bool) Appointment::where('id', $id)->update([
            'patient_id' => $patientId,
            'notes' => $notes,
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Update appointment status.
     */
    public function updateStatus(int $appointmentId, string $status): bool
    {
        return (bool) Appointment::where('id', $appointmentId)->update(['status' => $status]);
    }

    /**
     * Delete an appointment (soft-delete).
     */
    public function deleteAppointment(int $id): bool
    {
        return (bool) Appointment::where('id', $id)->delete();
    }
}
