<?php

namespace App\Services;

use App\Appointment;
use App\DentalChart;
use App\Patient;
use App\User;
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
     * Get the latest usable appointment for a patient.
     */
    public function getLatestAppointment(int $patientId): ?Appointment
    {
        return Appointment::where('patient_id', $patientId)
            ->whereNotIn('status', [
                Appointment::STATUS_CANCELLED,
                Appointment::STATUS_NO_SHOW,
                Appointment::STATUS_REJECTED,
            ])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Resolve an appointment to open the chart editor for a patient.
     *
     * Chart rows are keyed by appointment_id, but load/save already scope by
     * patient — so any historical appointment can carry the editor session.
     * Only create a lightweight today visit when the patient has never had one.
     */
    public function resolveAppointmentForChart(int $patientId): Appointment
    {
        $patient = Patient::whereNull('deleted_at')->findOrFail($patientId);

        $existing = $this->getLatestAppointment((int) $patient->id);
        if ($existing) {
            return $existing;
        }

        // 取消/爽约等也复用，避免仅为图表再插一条出现在排班/今日工作里
        $anyAppointment = Appointment::where('patient_id', $patient->id)
            ->orderByDesc('id')
            ->first();
        if ($anyAppointment) {
            return $anyAppointment;
        }

        $user = Auth::user();
        if (!$user) {
            throw new \RuntimeException(__('odontogram.no_doctor_for_chart'));
        }

        $doctorId = $this->resolveDoctorIdForChart();
        if (!$doctorId) {
            throw new \RuntimeException(__('odontogram.no_doctor_for_chart'));
        }

        $today = now()->toDateString();
        $nowTime = now()->format('H:i:s');

        return Appointment::create([
            'appointment_no'    => Appointment::AppointmentNo(),
            'patient_id'        => $patient->id,
            'doctor_id'         => $doctorId,
            'start_date'        => $today,
            'end_date'          => $today,
            'start_time'        => $nowTime,
            'duration_minutes'  => 30,
            'appointment_type'  => 'consultation',
            'source'            => 'walk_in',
            'visit_information' => Appointment::VISIT_WALK_IN,
            'status'            => Appointment::STATUS_SCHEDULED,
            // 不写面向用户的备注：会污染「就诊记录」摘要；仅作图表数据载体即可
            'notes'             => null,
            'branch_id'         => $user->branch_id,
            'sort_by'           => $today . ' ' . $nowTime,
            '_who_added'        => $user->id,
        ]);
    }

    /**
     * Get patient model for dental chart editor page.
     */
    public function getPatientForChart(int $appointmentId): ?Patient
    {
        $appointment = Appointment::where('id', $appointmentId)->first();
        if (!$appointment) {
            return null;
        }

        return Patient::whereNull('deleted_at')->find($appointment->patient_id);
    }

    private function resolveDoctorIdForChart(): ?int
    {
        $user = Auth::user();
        if ($user && $user->is_doctor) {
            return (int) $user->id;
        }

        $doctorId = User::where('is_doctor', true)
            ->where('status', User::STATUS_ACTIVE)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->value('id');

        return $doctorId ? (int) $doctorId : null;
    }

    /**
     * Replace all dental chart entries for a patient with new data.
     */
    public function replaceChartData(int $appointmentId, array $chartData): bool
    {
        $appointment = Appointment::where('id', $appointmentId)->first();
        if (!$appointment) {
            throw new \InvalidArgumentException(__('odontogram.patient_not_found'));
        }

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
                '_who_added' => Auth::id(),
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
        if (!$appointment) {
            return collect();
        }

        return DB::table('dental_charts')
            ->leftJoin('appointments', 'appointments.id', 'dental_charts.appointment_id')
            ->whereNull('dental_charts.deleted_at')
            ->where('appointments.patient_id', $appointment->patient_id)
            ->select('dental_charts.*')
            ->get();
    }
}
