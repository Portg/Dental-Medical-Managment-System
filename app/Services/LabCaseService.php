<?php

namespace App\Services;

use App\LabCase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LabCaseService
{
    /**
     * Get lab cases list with filters.
     */
    public function getLabCaseList(array $filters = []): Collection
    {
        $query = DB::table('lab_cases')
            ->join('patients', 'patients.id', 'lab_cases.patient_id')
            ->join('users', 'users.id', 'lab_cases.doctor_id')
            ->join('labs', 'labs.id', 'lab_cases.lab_id')
            ->whereNull('lab_cases.deleted_at')
            ->select(
                'lab_cases.*',
                DB::raw(app()->getLocale() === 'zh-CN'
                    ? "CONCAT(patients.surname, patients.othername) as patient_name"
                    : "CONCAT(patients.surname, ' ', patients.othername) as patient_name"),
                'patients.patient_no',
                'users.othername as doctor_name',
                'labs.name as lab_name'
            );

        if (!empty($filters['status'])) {
            $query->where('lab_cases.status', $filters['status']);
        }

        if (!empty($filters['lab_id'])) {
            $query->where('lab_cases.lab_id', $filters['lab_id']);
        }

        if (!empty($filters['doctor_id'])) {
            $query->where('lab_cases.doctor_id', $filters['doctor_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('lab_cases.lab_case_no', 'like', "%{$search}%")
                  ->orWhere('patients.surname', 'like', "%{$search}%")
                  ->orWhere('patients.othername', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('lab_cases.updated_at', 'desc')->get();
    }

    /**
     * Get overdue lab cases.
     */
    public function getOverdueCases(): Collection
    {
        return DB::table('lab_cases')
            ->join('patients', 'patients.id', 'lab_cases.patient_id')
            ->join('labs', 'labs.id', 'lab_cases.lab_id')
            ->whereNull('lab_cases.deleted_at')
            ->whereIn('lab_cases.status', ['sent', 'in_production'])
            ->whereNotNull('lab_cases.expected_return_date')
            ->where('lab_cases.expected_return_date', '<', now()->format('Y-m-d'))
            ->whereNull('lab_cases.actual_return_date')
            ->select(
                'lab_cases.*',
                DB::raw(app()->getLocale() === 'zh-CN'
                    ? "CONCAT(patients.surname, patients.othername) as patient_name"
                    : "CONCAT(patients.surname, ' ', patients.othername) as patient_name"),
                'labs.name as lab_name'
            )
            ->orderBy('lab_cases.expected_return_date')
            ->get();
    }

    /**
     * Create a lab case.
     */
    public function createLabCase(array $data): LabCase
    {
        $data['lab_case_no'] = LabCase::generateCaseNo();
        $data['_who_added'] = Auth::id();

        return LabCase::create($data);
    }

    /**
     * Update a lab case.
     */
    public function updateLabCase(int $id, array $data): bool
    {
        return (bool) LabCase::where('id', $id)->update($data);
    }

    /**
     * Update lab case status with side effects.
     */
    public function updateStatus(int $id, string $status, array $extra = []): bool
    {
        $update = ['status' => $status];

        if ($status === 'sent' && empty($extra['sent_date'])) {
            $update['sent_date'] = now()->format('Y-m-d');
        }

        if (in_array($status, ['returned', 'try_in', 'completed']) && empty($extra['actual_return_date'])) {
            $update['actual_return_date'] = now()->format('Y-m-d');
        }

        if ($status === 'rework') {
            $case = LabCase::find($id);
            if ($case) {
                $update['rework_count'] = $case->rework_count + 1;
                $update['actual_return_date'] = null;
            }
            if (!empty($extra['rework_reason'])) {
                $update['rework_reason'] = $extra['rework_reason'];
            }
        }

        return (bool) LabCase::where('id', $id)->update(array_merge($update, $extra));
    }

    /**
     * Delete a lab case (soft).
     */
    public function deleteLabCase(int $id): bool
    {
        return (bool) LabCase::where('id', $id)->delete();
    }

    /**
     * Get a single lab case with relationships.
     */
    public function getLabCase(int $id): ?LabCase
    {
        return LabCase::with(['patient', 'doctor', 'lab', 'appointment'])->find($id);
    }

    /**
     * Get lab cases for a patient.
     */
    public function getPatientCases(int $patientId): Collection
    {
        return LabCase::with(['lab', 'doctor'])
            ->where('patient_id', $patientId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get lab case statistics.
     */
    public function getStatistics(): array
    {
        $stats = DB::table('lab_cases')
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status IN ('pending','sent','in_production') THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'rework' THEN 1 ELSE 0 END) as rework,
                SUM(lab_fee) as total_lab_fee,
                SUM(patient_charge) as total_patient_charge,
                SUM(patient_charge - lab_fee) as total_profit
            ")
            ->first();

        $overdue = DB::table('lab_cases')
            ->whereNull('deleted_at')
            ->whereIn('status', ['sent', 'in_production'])
            ->whereNotNull('expected_return_date')
            ->where('expected_return_date', '<', now()->format('Y-m-d'))
            ->whereNull('actual_return_date')
            ->count();

        return [
            'total'                => (int) ($stats->total ?? 0),
            'active'               => (int) ($stats->active ?? 0),
            'completed'            => (int) ($stats->completed ?? 0),
            'rework'               => (int) ($stats->rework ?? 0),
            'overdue'              => $overdue,
            'total_lab_fee'        => (float) ($stats->total_lab_fee ?? 0),
            'total_patient_charge' => (float) ($stats->total_patient_charge ?? 0),
            'total_profit'         => (float) ($stats->total_profit ?? 0),
        ];
    }

    /**
     * Get print data for a lab case.
     */
    public function getPrintData(int $id): ?LabCase
    {
        return LabCase::with(['patient', 'doctor', 'lab', 'appointment', 'addedBy'])->find($id);
    }
}
