<?php

namespace App\Services;

use App\Diagnosis;
use App\Http\Helper\ActionColumnHelper;
use App\MedicalCase;
use App\MedicalCaseAmendment;
use App\OperationLog;
use App\Patient;
use App\TreatmentPlan;
use App\User;
use App\VitalSign;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class MedicalCaseService
{
    /**
     * Get all medical cases for DataTables.
     */
    public function getAllCases(array $filters): Collection
    {
        $data = DB::table('medical_cases')
            ->leftJoin('patients', 'patients.id', 'medical_cases.patient_id')
            ->leftJoin('users as doctors', 'doctors.id', 'medical_cases.doctor_id')
            ->leftJoin('users as added_by', 'added_by.id', 'medical_cases._who_added')
            ->whereNull('medical_cases.deleted_at')
            ->orderBy('medical_cases.created_at', 'desc')
            ->select(
                'medical_cases.*',
                DB::raw(app()->getLocale() === 'zh-CN' ? "CONCAT(patients.surname, patients.othername) as patient_name" : "CONCAT(patients.surname, ' ', patients.othername) as patient_name"),
                'patients.patient_no',
                DB::raw(app()->getLocale() === 'zh-CN' ? "CONCAT(doctors.surname, doctors.othername) as doctor_name" : "CONCAT(doctors.surname, ' ', doctors.othername) as doctor_name"),
                DB::raw(app()->getLocale() === 'zh-CN' ? "CONCAT(added_by.surname, added_by.othername) as added_by_name" : "CONCAT(added_by.surname, ' ', added_by.othername) as added_by_name"),
                DB::raw("(SELECT COUNT(*) FROM medical_case_amendments WHERE medical_case_amendments.medical_case_id = medical_cases.id AND medical_case_amendments.status = 'pending') as pending_amendments_count")
            )
            ->get();

        // Apply filters
        if (!empty($filters['search_term'])) {
            $searchTerm = $filters['search_term'];
            $data = $data->filter(function ($item) use ($searchTerm) {
                return stripos($item->case_no, $searchTerm) !== false ||
                       stripos($item->title, $searchTerm) !== false ||
                       stripos($item->patient_name, $searchTerm) !== false;
            });
        }
        if (!empty($filters['status'])) {
            $status = $filters['status'];
            $data = $data->filter(function ($item) use ($status) {
                return $item->status == $status;
            });
        }
        if (!empty($filters['doctor_id'])) {
            $doctorId = $filters['doctor_id'];
            $data = $data->filter(function ($item) use ($doctorId) {
                return $item->doctor_id == $doctorId;
            });
        }
        if (!empty($filters['patient_id'])) {
            $patientId = $filters['patient_id'];
            $data = $data->filter(function ($item) use ($patientId) {
                return $item->patient_id == $patientId;
            });
        }
        if (!empty($filters['start_date'])) {
            $startDate = $filters['start_date'];
            $data = $data->filter(function ($item) use ($startDate) {
                return $item->case_date >= $startDate;
            });
        }
        if (!empty($filters['end_date'])) {
            $endDate = $filters['end_date'];
            $data = $data->filter(function ($item) use ($endDate) {
                return $item->case_date <= $endDate;
            });
        }

        return $data;
    }

    /**
     * Get cases for a specific patient.
     */
    public function getPatientCases(int $patientId): Collection
    {
        return DB::table('medical_cases')
            ->leftJoin('users as doctors', 'doctors.id', 'medical_cases.doctor_id')
            ->whereNull('medical_cases.deleted_at')
            ->where('medical_cases.patient_id', $patientId)
            ->orderBy('medical_cases.created_at', 'desc')
            ->select(
                'medical_cases.*',
                DB::raw(app()->getLocale() === 'zh-CN' ? "CONCAT(doctors.surname, doctors.othername) as doctor_name" : "CONCAT(doctors.surname, ' ', doctors.othername) as doctor_name")
            )
            ->get();
    }

    /**
     * Get case with relationships for show view.
     */
    public function getCaseDetail(int $id): array
    {
        $case = MedicalCase::with(['patient', 'doctor', 'addedBy'])->findOrFail($id);
        $doctors = $this->getDoctors();

        return compact('case', 'doctors');
    }

    /**
     * Get case with relationships for edit view.
     */
    public function getCaseForEdit(int $id): array
    {
        $case = MedicalCase::with(['patient', 'doctor'])->findOrFail($id);
        $doctors = $this->getDoctors();

        $historyRecords = MedicalCase::where('patient_id', $case->patient_id)
            ->where('id', '!=', $id)
            ->whereNull('deleted_at')
            ->orderBy('case_date', 'desc')
            ->limit(10)
            ->get();

        return compact('case', 'doctors', 'historyRecords');
    }

    /**
     * Get case as JSON.
     */
    public function getCase(int $id): ?MedicalCase
    {
        return MedicalCase::where('id', $id)->first();
    }

    /**
     * Get data for create form.
     */
    public function getCreateData(): array
    {
        $doctors = $this->getDoctors();
        $patients = Patient::whereNull('deleted_at')->orderBy('surname')->get();

        return compact('doctors', 'patients');
    }

    /**
     * Get data for creating a case for a specific patient.
     */
    public function getCreateForPatientData(int $patientId): array
    {
        $patient = Patient::findOrFail($patientId);
        $doctors = $this->getDoctors();

        $historyRecords = MedicalCase::where('patient_id', $patientId)
            ->whereNull('deleted_at')
            ->orderBy('case_date', 'desc')
            ->limit(10)
            ->get();

        $hasExistingCase = MedicalCase::where('patient_id', $patientId)->exists();

        return compact('patient', 'doctors', 'historyRecords', 'hasExistingCase');
    }

    /**
     * Build case data array from input.
     */
    public function buildCaseData(array $input, bool $isUpdate = false): array
    {
        $title = !empty($input['chief_complaint'])
            ? mb_substr($input['chief_complaint'], 0, 50)
            : __('medical_cases.medical_record_edit') . ' ' . ($input['case_date'] ?? '');

        $data = [
            'title' => $title,
            'chief_complaint' => $input['chief_complaint'] ?? null,
            'history_of_present_illness' => $input['history_of_present_illness'] ?? null,
            'examination' => $input['examination'] ?? null,
            'examination_teeth' => !empty($input['examination_teeth']) ? json_decode($input['examination_teeth'], true) : null,
            'auxiliary_examination' => $input['auxiliary_examination'] ?? null,
            'related_images' => !empty($input['related_images']) ? json_decode($input['related_images'], true) : null,
            'diagnosis' => $input['diagnosis'] ?? null,
            'diagnosis_code' => $input['diagnosis_code'] ?? null,
            'related_teeth' => !empty($input['related_teeth']) ? json_decode($input['related_teeth'], true) : null,
            'treatment' => $input['treatment'] ?? null,
            'treatment_services' => !empty($input['treatment_services']) ? json_decode($input['treatment_services'], true) : null,
            'medical_orders' => $input['medical_orders'] ?? null,
            'next_visit_date' => $input['next_visit_date'] ?? null,
            'next_visit_note' => $input['next_visit_note'] ?? null,
            'auto_create_followup' => array_key_exists('auto_create_followup', $input),
            'visit_type' => $input['visit_type'] ?? 'initial',
            'case_date' => $input['case_date'] ?? null,
            'patient_id' => $input['patient_id'] ?? null,
            'doctor_id' => $input['doctor_id'] ?? Auth::user()->id,
        ];

        if (!$isUpdate) {
            $data['case_no'] = MedicalCase::CaseNumber();
            $data['status'] = MedicalCase::STATUS_OPEN;
            $data['_who_added'] = Auth::user()->id;
        }

        return $data;
    }

    /**
     * Create a new medical case.
     */
    public function createCase(array $data, bool $isDraft): ?MedicalCase
    {
        $data['is_draft'] = $isDraft;
        $data['version_number'] = 1;
        $case = MedicalCase::create($data);

        if ($case && !$isDraft) {
            $case->lock();
        }

        if ($case) {
            OperationLog::logCreate('medical', 'MedicalCase', $case->id);
        }

        return $case;
    }

    /**
     * Update an existing medical case.
     *
     * @return array{status: bool, require_reason?: bool, amendment_id?: int}
     */
    public function updateCase(int $id, array $data, bool $isDraft, ?string $modificationReason = null, ?string $closingStatus = null, ?string $closingNotes = null): array
    {
        $case = MedicalCase::findOrFail($id);

        // Locked cases require amendment approval (compliance)
        if ($case->is_locked && !$case->canModifyWithoutApproval()) {
            if (!$modificationReason) {
                return ['status' => false, 'require_reason' => true];
            }

            // Create amendment request instead of direct update
            $amendment = $this->createAmendment($case, $data, $modificationReason);
            return ['status' => true, 'amendment_id' => $amendment->id];
        }

        $data['is_draft'] = $isDraft;

        if ($closingStatus == MedicalCase::STATUS_CLOSED) {
            $data['status'] = MedicalCase::STATUS_CLOSED;
            $data['closed_date'] = now();
            $data['closing_notes'] = $closingNotes;
        }

        $case->increment('version_number');
        $status = MedicalCase::where('id', $id)->update($data);

        // If transitioning from draft to submitted, lock the record
        if (!$isDraft && $case->is_draft) {
            $case->refresh();
            $case->lock();
        }

        return ['status' => $status !== false];
    }

    /**
     * Create an amendment request for a locked medical case.
     */
    public function createAmendment(MedicalCase $case, array $newData, string $reason): MedicalCaseAmendment
    {
        // Compute changed fields only
        $oldValues = [];
        $newValues = [];
        $amendmentFields = [];

        foreach ($newData as $key => $value) {
            $original = $case->getOriginal($key);
            if ($original != $value && in_array($key, $case->getFillable())) {
                $oldValues[$key] = $original;
                $newValues[$key] = $value;
                $amendmentFields[] = $key;
            }
        }

        return MedicalCaseAmendment::create([
            'medical_case_id' => $case->id,
            'requested_by' => Auth::id(),
            'amendment_reason' => $reason,
            'amendment_fields' => $amendmentFields,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'status' => MedicalCaseAmendment::STATUS_PENDING,
        ]);
    }

    /**
     * Delete a medical case (soft-delete).
     */
    public function deleteCase(int $id): bool
    {
        OperationLog::logDelete('medical', 'MedicalCase', $id);
        return (bool) MedicalCase::where('id', $id)->delete();
    }

    /**
     * Get print data for a medical case.
     */
    public function getPrintData(int $id): array
    {
        $case = MedicalCase::with(['patient', 'doctor', 'addedBy'])->findOrFail($id);

        $diagnoses = Diagnosis::where('medical_case_id', $id)
            ->whereNull('deleted_at')
            ->orderBy('diagnosis_date', 'desc')
            ->get();

        $treatmentPlans = TreatmentPlan::where('medical_case_id', $id)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->get();

        $latestVitalSign = VitalSign::where('medical_case_id', $id)
            ->whereNull('deleted_at')
            ->orderBy('recorded_at', 'desc')
            ->first();

        // Include audit trail for compliance PDF
        $auditTrail = $case->audits()->with('user')->latest()->take(10)->get();

        return compact('case', 'diagnoses', 'treatmentPlans', 'latestVitalSign', 'auditTrail');
    }

    /**
     * Search ICD-10 codes.
     */
    public function searchIcd10(string $query = ''): array
    {
        $icd10Codes = [
            ['id' => 'K00.0', 'text' => 'K00.0 - ' . __('odontogram.anodontia')],
            ['id' => 'K00.1', 'text' => 'K00.1 - ' . __('odontogram.supernumerary_teeth')],
            ['id' => 'K01.0', 'text' => 'K01.0 - ' . __('odontogram.embedded_teeth')],
            ['id' => 'K01.1', 'text' => 'K01.1 - ' . __('odontogram.impacted_teeth')],
            ['id' => 'K02.0', 'text' => 'K02.0 - ' . __('odontogram.caries_enamel')],
            ['id' => 'K02.1', 'text' => 'K02.1 - ' . __('odontogram.caries_dentin')],
            ['id' => 'K02.2', 'text' => 'K02.2 - ' . __('odontogram.caries_cementum')],
            ['id' => 'K02.3', 'text' => 'K02.3 - ' . __('odontogram.arrested_caries')],
            ['id' => 'K03.0', 'text' => 'K03.0 - ' . __('odontogram.attrition')],
            ['id' => 'K03.1', 'text' => 'K03.1 - ' . __('odontogram.abrasion')],
            ['id' => 'K03.2', 'text' => 'K03.2 - ' . __('odontogram.erosion')],
            ['id' => 'K04.0', 'text' => 'K04.0 - ' . __('odontogram.pulpitis')],
            ['id' => 'K04.1', 'text' => 'K04.1 - ' . __('odontogram.pulp_necrosis')],
            ['id' => 'K04.4', 'text' => 'K04.4 - ' . __('odontogram.acute_apical_periodontitis')],
            ['id' => 'K04.5', 'text' => 'K04.5 - ' . __('odontogram.chronic_apical_periodontitis')],
            ['id' => 'K04.6', 'text' => 'K04.6 - ' . __('odontogram.periapical_abscess')],
            ['id' => 'K04.7', 'text' => 'K04.7 - ' . __('odontogram.periapical_abscess_sinus')],
            ['id' => 'K05.0', 'text' => 'K05.0 - ' . __('odontogram.acute_gingivitis')],
            ['id' => 'K05.1', 'text' => 'K05.1 - ' . __('odontogram.chronic_gingivitis')],
            ['id' => 'K05.2', 'text' => 'K05.2 - ' . __('odontogram.acute_periodontitis')],
            ['id' => 'K05.3', 'text' => 'K05.3 - ' . __('odontogram.chronic_periodontitis')],
            ['id' => 'K05.4', 'text' => 'K05.4 - ' . __('odontogram.periodontosis')],
            ['id' => 'K06.0', 'text' => 'K06.0 - ' . __('odontogram.gingival_recession')],
            ['id' => 'K06.1', 'text' => 'K06.1 - ' . __('odontogram.gingival_enlargement')],
            ['id' => 'K07.3', 'text' => 'K07.3 - ' . __('odontogram.tooth_position_anomaly')],
            ['id' => 'K08.0', 'text' => 'K08.0 - ' . __('odontogram.exfoliation_systemic')],
            ['id' => 'K08.1', 'text' => 'K08.1 - ' . __('odontogram.loss_due_accident')],
            ['id' => 'K08.2', 'text' => 'K08.2 - ' . __('odontogram.loss_due_periodontal')],
            ['id' => 'K08.3', 'text' => 'K08.3 - ' . __('odontogram.retained_root')],
        ];

        if ($query) {
            $icd10Codes = array_filter($icd10Codes, function ($code) use ($query) {
                return stripos($code['id'], $query) !== false || stripos($code['text'], $query) !== false;
            });
        }

        return array_values($icd10Codes);
    }

    /**
     * Build DataTable response for the medical cases index listing.
     *
     * @param Collection $data
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function buildIndexDataTable($data)
    {
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('statusBadge', function ($row) {
                $class = 'default';
                if ($row->status == MedicalCase::STATUS_OPEN) $class = 'success';
                elseif ($row->status == 'In Progress') $class = 'info'; // NOTE: no STATUS_IN_PROGRESS constant; value kept as-is
                elseif ($row->status == MedicalCase::STATUS_CLOSED) $class = 'danger';
                elseif ($row->status == MedicalCase::STATUS_FOLLOW_UP) $class = 'warning';
                $statusKey = strtolower(str_replace([' ', '-'], '_', $row->status));
                $html = '<span class="label label-' . $class . '">' . __('medical_cases.status_' . $statusKey) . '</span>';
                if (!empty($row->pending_amendments_count)) {
                    $html .= ' <span class="label label-warning" title="' . __('medical_cases.amendment_pending') . '"><i class="fa fa-clock-o"></i> ' . $row->pending_amendments_count . '</span>';
                }
                return $html;
            })
            ->addColumn('action', function ($row) {
                return ActionColumnHelper::make($row->id)
                    ->add('view')
                    ->primaryIf($row->deleted_at == null, 'edit')
                    ->add('export_pdf', __('medical_cases.export_pdf'), '/medical-cases/' . $row->id . '/export-pdf')
                    ->add('delete')
                    ->render();
            })
            ->rawColumns(['statusBadge', 'action'])
            ->make(true);
    }

    /**
     * Build DataTable response for a patient's medical cases listing.
     *
     * @param Collection $data
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function buildPatientCasesDataTable($data)
    {
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('viewBtn', function ($row) {
                return '<a href="' . url('medical-cases/' . $row->id) . '" class="btn btn-info btn-sm">' . __('common.view') . '</a>';
            })
            ->addColumn('statusBadge', function ($row) {
                $class = 'default';
                if ($row->status == MedicalCase::STATUS_OPEN) $class = 'success';
                elseif ($row->status == MedicalCase::STATUS_CLOSED) $class = 'danger';
                elseif ($row->status == MedicalCase::STATUS_FOLLOW_UP) $class = 'warning';
                return '<span class="label label-' . $class . '">' . __('medical_cases.status_' . strtolower(str_replace('-', '_', $row->status))) . '</span>';
            })
            ->rawColumns(['viewBtn', 'statusBadge'])
            ->make(true);
    }

    /**
     * Get active doctors.
     */
    private function getDoctors(): Collection
    {
        return User::where('is_doctor', true)->whereNull('deleted_at')->orderBy('surname')->get();
    }
}
