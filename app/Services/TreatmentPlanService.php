<?php

namespace App\Services;

use App\TreatmentPlan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TreatmentPlanService
{
    /**
     * Get all treatment plans for DataTables.
     */
    public function getAllPlans(): Collection
    {
        return DB::table('treatment_plans')
            ->leftJoin('patients', 'patients.id', 'treatment_plans.patient_id')
            ->leftJoin('medical_cases', 'medical_cases.id', 'treatment_plans.medical_case_id')
            ->leftJoin('users', 'users.id', 'treatment_plans._who_added')
            ->whereNull('treatment_plans.deleted_at')
            ->whereNull('patients.deleted_at')
            ->orderBy('treatment_plans.created_at', 'desc')
            ->select(
                'treatment_plans.*',
                'patients.patient_no',
                DB::raw(app()->getLocale() === 'zh-CN' ? "CONCAT(patients.surname, patients.othername) as patient_name" : "CONCAT(patients.surname, ' ', patients.othername) as patient_name"),
                'medical_cases.case_no',
                'medical_cases.title as case_title',
                DB::raw(app()->getLocale() === 'zh-CN' ? "CONCAT(users.surname, users.othername) as added_by" : "CONCAT(users.surname, ' ', users.othername) as added_by")
            )
            ->get();
    }

    /**
     * Get treatment plans for a specific patient.
     */
    public function getPatientPlans(int $patientId): Collection
    {
        return DB::table('treatment_plans')
            ->leftJoin('medical_cases', 'medical_cases.id', 'treatment_plans.medical_case_id')
            ->leftJoin('users', 'users.id', 'treatment_plans._who_added')
            ->whereNull('treatment_plans.deleted_at')
            ->where('treatment_plans.patient_id', $patientId)
            ->orderBy('treatment_plans.created_at', 'desc')
            ->select(
                'treatment_plans.*',
                'medical_cases.case_no',
                'medical_cases.title as case_title',
                DB::raw(app()->getLocale() === 'zh-CN' ? "CONCAT(users.surname, users.othername) as added_by" : "CONCAT(users.surname, ' ', users.othername) as added_by")
            )
            ->get();
    }

    /**
     * Get treatment plans for a specific medical case.
     */
    public function getCasePlans(int $caseId): Collection
    {
        return DB::table('treatment_plans')
            ->leftJoin('users', 'users.id', 'treatment_plans._who_added')
            ->whereNull('treatment_plans.deleted_at')
            ->where('treatment_plans.medical_case_id', $caseId)
            ->orderBy('treatment_plans.created_at', 'desc')
            ->select(
                'treatment_plans.*',
                DB::raw(app()->getLocale() === 'zh-CN' ? "CONCAT(users.surname, users.othername) as added_by" : "CONCAT(users.surname, ' ', users.othername) as added_by")
            )
            ->get();
    }

    /**
     * Get treatment plan detail with relationships.
     */
    public function getPlanDetail(int $id): TreatmentPlan
    {
        return TreatmentPlan::with(['patient', 'medicalCase', 'addedBy'])->findOrFail($id);
    }

    /**
     * Get treatment plan for editing.
     */
    public function getPlanForEdit(int $id): ?TreatmentPlan
    {
        return TreatmentPlan::where('id', $id)->first();
    }

    /**
     * Create a new treatment plan.
     */
    public function createPlan(array $data): ?TreatmentPlan
    {
        return TreatmentPlan::create([
            'plan_name' => $data['plan_name'],
            'description' => $data['description'] ?? null,
            'planned_procedures' => $data['planned_procedures'] ?? null,
            'estimated_cost' => $data['estimated_cost'] ?? null,
            'status' => $data['status'] ?? 'Planned',
            'priority' => $data['priority'] ?? 'Medium',
            'start_date' => $data['start_date'] ?? null,
            'target_completion_date' => $data['target_completion_date'] ?? null,
            'medical_case_id' => $data['medical_case_id'] ?? null,
            'patient_id' => $data['patient_id'],
            '_who_added' => Auth::User()->id,
        ]);
    }

    /**
     * Update an existing treatment plan.
     */
    public function updatePlan(int $id, array $data): bool
    {
        $updateData = [
            'plan_name' => $data['plan_name'],
            'description' => $data['description'] ?? null,
            'planned_procedures' => $data['planned_procedures'] ?? null,
            'estimated_cost' => $data['estimated_cost'] ?? null,
            'actual_cost' => $data['actual_cost'] ?? null,
            'status' => $data['status'] ?? null,
            'priority' => $data['priority'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'target_completion_date' => $data['target_completion_date'] ?? null,
        ];

        if (($data['status'] ?? null) == 'Completed') {
            $updateData['actual_completion_date'] = $data['actual_completion_date'] ?? now();
            $updateData['completion_notes'] = $data['completion_notes'] ?? null;
        }

        return (bool) TreatmentPlan::where('id', $id)->update($updateData);
    }

    /**
     * Delete a treatment plan (soft-delete).
     */
    public function deletePlan(int $id): bool
    {
        return (bool) TreatmentPlan::where('id', $id)->delete();
    }
}
