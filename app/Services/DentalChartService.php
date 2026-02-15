<?php

namespace App\Services;

use App\Appointment;
use App\DentalChart;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DentalChartService
{
    /**
     * Get patients with dental chart records for DataTables.
     */
    public function getPatientChartList(): \Illuminate\Database\Query\Builder
    {
        return DB::table('dental_charts')
            ->join('appointments', 'dental_charts.appointment_id', '=', 'appointments.id')
            ->join('patients', 'appointments.patient_id', '=', 'patients.id')
            ->whereNull('dental_charts.deleted_at')
            ->whereNull('patients.deleted_at')
            ->select(
                'patients.id as patient_id',
                'patients.patient_no',
                DB::raw(app()->getLocale() === 'zh-CN' ? "CONCAT(patients.surname, patients.othername) as patient_name" : "CONCAT(patients.surname, ' ', patients.othername) as patient_name"),
                DB::raw('COUNT(dental_charts.id) as tooth_count'),
                DB::raw('MAX(dental_charts.updated_at) as last_updated')
            )
            ->groupBy('patients.id', 'patients.patient_no', 'patients.surname', 'patients.othername')
            ->orderBy('last_updated', 'desc');
    }

    /**
     * Get the latest appointment for a patient.
     */
    public function getLatestAppointment(int $patientId): ?object
    {
        return DB::table('appointments')
            ->where('patient_id', $patientId)
            ->whereNull('deleted_at')
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * Get patient data for dental chart creation.
     */
    public function getPatientForChart(int $appointmentId): ?object
    {
        return DB::table('patients')
            ->leftJoin('appointments', 'patients.id', 'appointments.patient_id')
            ->whereNull('patients.deleted_at')
            ->where('appointments.id', $appointmentId)
            ->select('patients.*')
            ->first();
    }

    /**
     * Replace all dental chart entries for a patient with new data.
     */
    public function replaceChartData(int $appointmentId, array $chartData): bool
    {
        $appointment = Appointment::where('id', $appointmentId)->first();

        // Delete all previous patient dental chart records
        DB::table('dental_charts')
            ->leftJoin('appointments', 'appointments.id', 'dental_charts.appointment_id')
            ->where('appointments.patient_id', $appointment->patient_id)
            ->delete();

        foreach ($chartData as $value) {
            DentalChart::create([
                'tooth' => $value['tooth'],
                'section' => $value['section'] ?? null,
                'color' => $value['color'] ?? null,
                'appointment_id' => $appointmentId,
                '_who_added' => Auth::User()->id,
            ]);
        }

        return true;
    }

    /**
     * Get all dental chart entries for a patient by appointment ID.
     */
    public function getChartByAppointment(int $appointmentId): Collection
    {
        $appointment = Appointment::where('id', $appointmentId)->first();

        return DB::table('dental_charts')
            ->leftJoin('appointments', 'appointments.id', 'dental_charts.appointment_id')
            ->whereNull('dental_charts.deleted_at')
            ->where('appointments.patient_id', $appointment->patient_id)
            ->select('dental_charts.*')
            ->get();
    }
}
