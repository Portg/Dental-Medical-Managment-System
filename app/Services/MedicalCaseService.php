<?php

namespace App\Services;

use App\Diagnosis;
use App\Http\Helper\ActionColumnHelper;
use App\MedicalCase;
use App\MedicalCaseAmendment;
use App\OperationLog;
use App\Patient;
use App\PatientFollowup;
use App\TreatmentPlan;
use App\User;
use App\VitalSign;
use App\DictItem;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class MedicalCaseService
{
    private PatientFollowupService $followupService;

    public function __construct(PatientFollowupService $followupService)
    {
        $this->followupService = $followupService;
    }

    /**
     * Get all medical cases for DataTables (query builder, server-side pagination).
     * Filters are pushed to SQL — avoids loading all records into PHP memory.
     */
    public function getAllCases(array $filters): Builder
    {
        $locale = app()->getLocale();
        $isCn   = $locale === 'zh-CN';

        // 搜索词兼容 'search' 和 'search_term' 两个 key（历史遗留）
        $search = $filters['search'] ?? $filters['search_term'] ?? null;

        return DB::table('medical_cases')
            ->leftJoin('patients', 'patients.id', '=', 'medical_cases.patient_id')
            ->leftJoin('users as doctors', 'doctors.id', '=', 'medical_cases.doctor_id')
            ->leftJoin('users as added_by', 'added_by.id', '=', 'medical_cases._who_added')
            ->whereNull('medical_cases.deleted_at')
            ->when($search, function ($q, $term) use ($isCn) {
                $like = '%' . $term . '%';
                $nameExpr = $isCn
                    ? DB::raw("CONCAT(IFNULL(patients.surname,''), IFNULL(patients.othername,''))")
                    : DB::raw("CONCAT(IFNULL(patients.surname,''), ' ', IFNULL(patients.othername,''))");
                $q->where(function ($inner) use ($like, $nameExpr) {
                    $inner->where('medical_cases.case_no', 'like', $like)
                          ->orWhere('medical_cases.title', 'like', $like)
                          ->orWhere($nameExpr, 'like', $like);
                });
            })
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('medical_cases.status', $v))
            ->when($filters['doctor_id'] ?? null, fn ($q, $v) => $q->where('medical_cases.doctor_id', $v))
            ->when($filters['patient_id'] ?? null, fn ($q, $v) => $q->where('medical_cases.patient_id', $v))
            ->when($filters['start_date'] ?? null, fn ($q, $v) => $q->where('medical_cases.case_date', '>=', $v))
            ->when($filters['end_date'] ?? null, fn ($q, $v) => $q->where('medical_cases.case_date', '<=', $v))
            ->select(
                'medical_cases.*',
                DB::raw($isCn
                    ? "CONCAT(IFNULL(patients.surname,''), IFNULL(patients.othername,'')) as patient_name"
                    : "CONCAT(IFNULL(patients.surname,''), ' ', IFNULL(patients.othername,'')) as patient_name"),
                'patients.patient_no',
                DB::raw($isCn
                    ? "CONCAT(IFNULL(doctors.surname,''), IFNULL(doctors.othername,'')) as doctor_name"
                    : "CONCAT(IFNULL(doctors.surname,''), ' ', IFNULL(doctors.othername,'')) as doctor_name"),
                DB::raw($isCn
                    ? "CONCAT(IFNULL(added_by.surname,''), IFNULL(added_by.othername,'')) as added_by_name"
                    : "CONCAT(IFNULL(added_by.surname,''), ' ', IFNULL(added_by.othername,'')) as added_by_name"),
                DB::raw("(SELECT COUNT(*) FROM medical_case_amendments
                          WHERE medical_case_amendments.medical_case_id = medical_cases.id
                          AND medical_case_amendments.status = 'pending') as pending_amendments_count")
            )
            ->orderBy('medical_cases.created_at', 'desc');
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
        $pendingAppointments = collect([]);

        return compact('doctors', 'patients', 'pendingAppointments');
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

        // 查询已完成但未写病历的预约（补充病历候选）
        $pendingAppointments = \App\Appointment::where('patient_id', $patientId)
            ->whereNull('medical_case_id')
            ->whereNull('deleted_at')
            ->whereIn('status', [\App\Appointment::STATUS_COMPLETED, \App\Appointment::STATUS_TREATMENT_COMPLETE])
            ->orderBy('start_date', 'desc')
            ->limit(20)
            ->select('id', 'start_date', 'start_time', 'doctor_id', 'visit_information')
            ->with('doctor:id,surname,othername')
            ->get();

        return compact('patient', 'doctors', 'historyRecords', 'hasExistingCase', 'pendingAppointments');
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

        // appointment_id 不存入 medical_cases 表，仅用于后续关联
        if (!empty($input['appointment_id'])) {
            $data['appointment_id'] = (int) $input['appointment_id'];
        }

        return $data;
    }

    /**
     * Create a new medical case.
     */
    public function createCase(array $data, bool $isDraft): ?MedicalCase
    {
        return DB::transaction(function () use ($data, $isDraft) {
            // 提取 appointment_id（不存入 medical_cases 表）
            $appointmentId = $data['appointment_id'] ?? null;
            unset($data['appointment_id']);

            $data['is_draft'] = $isDraft;
            $data['version_number'] = 1;
            $case = MedicalCase::create($data);

            if ($case && !$isDraft) {
                $case->lock();
            }

            if ($case) {
                OperationLog::logCreate('medical', 'MedicalCase', $case->id, $case->toArray());

                // 关联预约
                if ($appointmentId) {
                    \App\Appointment::where('id', $appointmentId)
                        ->whereNull('medical_case_id')
                        ->update(['medical_case_id' => $case->id]);
                }

                // 草稿不生成复诊待办：草稿会反复保存，而且「草稿」本身就意味着还没定。
                if (!$isDraft) {
                    $this->syncFollowupFromCase($case);
                }
            }

            return $case;
        });
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

        // Validate state transition
        if ($closingStatus && $closingStatus !== $case->status) {
            $allowedTransitions = [
                MedicalCase::STATUS_OPEN       => [MedicalCase::STATUS_CLOSED, MedicalCase::STATUS_FOLLOW_UP],
                MedicalCase::STATUS_FOLLOW_UP  => [MedicalCase::STATUS_CLOSED, MedicalCase::STATUS_OPEN],
                MedicalCase::STATUS_CLOSED     => [],
            ];
            if (!in_array($closingStatus, $allowedTransitions[$case->status] ?? [])) {
                return ['status' => false, 'invalid_transition' => true];
            }
            $data['status'] = $closingStatus;
            if ($closingStatus === MedicalCase::STATUS_CLOSED) {
                $data['closed_date'] = now();
                $data['closing_notes'] = $closingNotes;
            }
        }

        $case->increment('version_number');
        $status = MedicalCase::where('id', $id)->update($data);

        // If transitioning from draft to submitted, lock the record
        if (!$isDraft && $case->is_draft) {
            $case->refresh();
            $case->lock();
        }

        // 复诊待办跟着最新的 next_visit_date 走。必须 refresh：上面走的是
        // MedicalCase::where()->update()，$case 手里还是更新前的值，不刷新会拿旧日期。
        if ($status !== false && !$isDraft) {
            $case->refresh();
            $this->syncFollowupFromCase($case);
        }

        return ['status' => $status !== false];
    }

    /**
     * 把病例里的「下次复诊日期」同步成一条随访待办。
     *
     * 为什么写进 patient_followups 而不是 appointments：
     *   医生写「两周后复诊」是**医嘱**，不是排期 —— 它没有时间、没有椅位，
     *   病人也还不知道。写进 appointments 会被当成已排期的号：
     *   NurseDashboardService / PharmacyDashboardService / ReceptionistDashboardService
     *   三处都是无条件的 Appointment::today()->count()，会把复诊建议算进「今日预约数」，
     *   护士看到 12 个、实际来 8 个。而 patient_followups 本来就是为这件事建的：
     *   它有 medical_case_id / appointment_id / scheduled_date / status，
     *   还有现成的 scopeOverdue()，护士仪表盘已经在显示 overdue_followups。
     *
     * 幂等键是 medical_case_id：一份病例最多一条自动生成的复诊待办。医生改了日期
     * 是 update 而不是再插一条 —— 否则改三次日期就留三条垃圾待办，比不做更糟。
     * 只处理 Pending 的那条：已经被人约掉（Completed）或取消的，不再回头改动。
     */
    public function syncFollowupFromCase(MedicalCase $case): void
    {
        $existing = PatientFollowup::where('medical_case_id', $case->id)
            ->where('status', PatientFollowup::STATUS_PENDING)
            ->first();

        // 两种情况都要撤销待办，不留孤儿：
        //   日期被清空          = 医嘱撤回
        //   医生取消了勾选      = 条件性复诊（「若症状持续，两周后复诊」）——那是写给
        //                        病人和病历看的，不该让前台去打召回电话。无差别建待办，
        //                        前台会召回一批本来不用来的人，几周后就没人信这个列表了。
        if (empty($case->next_visit_date) || !$case->auto_create_followup) {
            if ($existing) {
                $existing->update(['status' => PatientFollowup::STATUS_CANCELLED]);
            }
            return;
        }

        $scheduledDate = $case->next_visit_date instanceof \DateTimeInterface
            ? $case->next_visit_date->format('Y-m-d')
            : (string) $case->next_visit_date;
        $purpose = $case->next_visit_note ?: __('medical_cases.followup_from_case_purpose');

        if ($existing) {
            $existing->update([
                'scheduled_date' => $scheduledDate,
                'purpose'        => $purpose,
            ]);
            return;
        }

        // followup_type 是 enum('Phone','SMS','Email','Visit','Other')：到店复诊用 Visit
        $this->followupService->createFollowup([
            'followup_type'   => 'Visit',
            'scheduled_date'  => $scheduledDate,
            'purpose'         => $purpose,
            'notes'           => $case->next_visit_note,
            'patient_id'      => $case->patient_id,
            'medical_case_id' => $case->id,
        ]);
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
                $badgeMap = [
                    MedicalCase::STATUS_OPEN => 'success',
                    MedicalCase::STATUS_CLOSED => 'danger',
                    MedicalCase::STATUS_FOLLOW_UP => 'warning',
                ];
                $class = $badgeMap[$row->status] ?? 'default';
                $label = DictItem::nameByCode('medical_case_status', $row->status) ?? $row->status;
                $html = '<span class="label label-' . $class . '">' . e($label) . '</span>';
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
                $badgeMap = [
                    MedicalCase::STATUS_OPEN => 'success',
                    MedicalCase::STATUS_CLOSED => 'danger',
                    MedicalCase::STATUS_FOLLOW_UP => 'warning',
                ];
                $class = $badgeMap[$row->status] ?? 'default';
                $label = DictItem::nameByCode('medical_case_status', $row->status) ?? $row->status;
                return '<span class="label label-' . $class . '">' . e($label) . '</span>';
            })
            ->rawColumns(['viewBtn', 'statusBadge'])
            ->make(true);
    }

    /**
     * Get active doctors.
     */
    private function getDoctors(): Collection
    {
        return User::where('is_doctor', true)->whereNull('deleted_at')->where('status', User::STATUS_ACTIVE)->orderBy('surname')->get();
    }
}
