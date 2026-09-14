<?php

namespace App\Http\Controllers;

use App\AccessLog;
use App\MedicalCaseAmendment;
use App\Services\MedicalCaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MedicalCaseController extends Controller
{
    private MedicalCaseService $medicalCaseService;

    public function __construct(MedicalCaseService $medicalCaseService)
    {
        $this->medicalCaseService = $medicalCaseService;

        // 读写分权：护士要录生命体征/护理记录就得先打开病历，但不该因此获得
        // 病历的完整读写（建档、改写、删除、修改审批、归档）。
        // 只读动作走 view-medical-cases，写动作仍要 manage-medical-cases。
        $this->middleware('can:view-medical-cases');
        $this->middleware('can:manage-medical-cases')->except([
            'index', 'patientCases', 'show', 'getCase',
            'printCase', 'exportPdf', 'searchIcd10',
            'amendments', 'versionHistory',
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->medicalCaseService->getAllCases([
                'search'     => $request->input('search.value', ''),
                'status'     => $request->input('status'),
                'doctor_id'  => $request->input('doctor_id'),
                'patient_id' => $request->input('patient_id'),
                'start_date' => $request->input('start_date'),
                'end_date'   => $request->input('end_date'),
            ]);

            return $this->medicalCaseService->buildIndexDataTable($data);
        }

        return view('medical_cases.index');
    }

    /**
     * Display cases for a specific patient.
     *
     * @param Request $request
     * @param int $patient_id
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function patientCases(Request $request, $patient_id)
    {
        if ($request->ajax()) {
            $data = $this->medicalCaseService->getPatientCases((int) $patient_id);

            return $this->medicalCaseService->buildPatientCasesDataTable($data);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    /**
     * 提交了分段明细时，先由行派生出文本列再校验。
     *
     * 「非草稿必须有检查/诊断/治疗」这条业务规则表达在下面的 validator 里，
     * 而分行录入之后前端只提交 case_items，不再提交 examination/diagnosis/treatment。
     * 不在这里补上的话，规则会因为字段缺失而误报「必填」，而病历其实填了。
     *
     * 派生只做一次、结果同时供校验和 buildCaseData 使用，两边看到的是同一份文字。
     */
    private function mergeDerivedCaseText(Request $request): void
    {
        if (!$request->has('case_items') && !$request->has('diagnosis_rows')) {
            return;
        }

        // 两者各自可选：只改诊断不动检查/治疗也要能保存
        $columns = $request->has('case_items')
            ? $this->medicalCaseService->normalizeCaseItems($request->input('case_items'))['columns']
            : [];

        // 诊断段走 diagnoses 表，文本列由它派生 —— 不并进来的话，
        // 「非草稿必须有诊断」那条规则会因为字段缺失而误报必填。
        if ($request->has('diagnosis_rows')) {
            $columns += $this->medicalCaseService
                ->normalizeDiagnoses($request->input('diagnosis_rows'))['columns'];
        }

        // 牙位列是数组，merge 进 request 后 buildCaseData 会当 JSON 字符串再解一次，
        // 这里只补文本段；牙位列由 buildCaseData 自己从行派生。
        $request->merge(array_filter(
            $columns,
            fn ($v, $k) => is_string($v) && !str_ends_with($k, '_teeth'),
            ARRAY_FILTER_USE_BOTH
        ));
    }

    public function store(Request $request)
    {
        $isDraft = $request->input('is_draft', '1') === '1';

        $this->mergeDerivedCaseText($request);

        $rules = [
            'patient_id' => 'required|exists:patients,id',
            'case_date' => 'required|date',
        ];

        if (!$isDraft) {
            $rules['chief_complaint'] = 'required|string|min:10';
            $rules['examination'] = 'required|string';
            $rules['diagnosis'] = 'required|string';
            $rules['treatment'] = 'required|string';
        }

        Validator::make($request->all(), $rules, [
            'patient_id.required' => __('validation.custom.patient_id.required'),
            'case_date.required' => __('validation.custom.case_date.required'),
            'chief_complaint.required' => __('medical_cases.chief_complaint_required'),
            'chief_complaint.min' => __('medical_cases.chief_complaint_min'),
            'examination.required' => __('medical_cases.examination_required'),
            'diagnosis.required' => __('medical_cases.diagnosis_required'),
            'treatment.required' => __('medical_cases.treatment_required'),
        ])->validate();

        $data = $this->medicalCaseService->buildCaseData($request->only([
            'patient_id', 'case_date', 'chief_complaint', 'history_of_present_illness', 'past_medical_history',
            'examination', 'examination_teeth', 'auxiliary_examination', 'related_images',
            'diagnosis', 'diagnosis_code', 'related_teeth', 'treatment', 'treatment_services',
            'medical_orders', 'next_visit_date', 'next_visit_note', 'auto_create_followup',
            'visit_type', 'doctor_id', 'appointment_id',
            // 分段明细（牙位 + 文字）；带上时 examination/auxiliary_examination/
            // diagnosis/treatment 及其牙位列改由行派生，见 buildCaseData
            'case_items',
            // 诊断段走 diagnoses 表（带 ICD 编码），见 buildCaseData
            'diagnosis_rows',
        ]));
        $case = $this->medicalCaseService->createCase($data, $isDraft);

        if ($case) {
            // Sign the case if signature data provided
            if (!$isDraft && $request->filled('signature')) {
                $case->sign($request->input('signature'));
            }

            return response()->json([
                'message' => $isDraft ? __('medical_cases.draft_saved') : __('medical_cases.record_submitted'),
                'status' => true,
                'id' => $case->id
            ]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        AccessLog::log('MedicalCase', 'view', $id);
        $detail = $this->medicalCaseService->getCaseDetail((int) $id);

        return view('medical_cases.show', $detail);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $data = $this->medicalCaseService->getCaseForEdit((int) $id);

        return view('medical_cases.edit', $data);
    }

    /**
     * Get case data as JSON for AJAX requests.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function getCase($id)
    {
        return response()->json($this->medicalCaseService->getCase((int) $id));
    }

    /**
     * 某份病历的可复用内容 —— 复诊「带入本次」用。
     *
     * 复诊是新建一份病历（一次就诊一份），而复诊的内容多半是在上次基础上改几个字：
     * 牙位一样、诊断一样、治疗接着上次做。没有这个接口就得对着侧栏一行行重打。
     *
     * 只给临床内容（检查/辅助检查/治疗的分行 + 诊断行 + 既往史 + 医嘱），
     * **不给主诉与现病史** —— 那是患者这次怎么说的，每次都不同，带过来反而要删。
     *
     * 单开一个接口而不是改 getCase：那个返回的是模型本身，已有别的消费方，
     * 往上加字段会改掉它的契约。
     */
    public function reusableContent($id)
    {
        $case = \App\MedicalCase::findOrFail((int) $id);

        // 只能带自己有权看的病历 —— 控制器已有 view-medical-cases 中间件，
        // 这里再确认一次不是别人的患者
        return response()->json([
            'status' => true,
            'data'   => [
                'case_items'          => $this->medicalCaseService->getCaseItemsForEdit($case),
                'diagnosis_rows'      => $this->medicalCaseService->getDiagnosesForEdit($case),
                'past_medical_history' => $case->past_medical_history,
                'medical_orders'      => $case->medical_orders,
            ],
        ]);
    }

    /**
     * Show the form for creating a new medical case.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data = $this->medicalCaseService->getCreateData();

        return view('medical_cases.edit', $data);
    }

    /**
     * Create a new medical case for a patient (form view).
     *
     * @param int $patient_id
     * @return \Illuminate\Http\Response
     */
    /**
     * 某患者的病历历史（渲染好的侧栏片段）。
     *
     * 走「新建病历」通用入口时患者是后选的，而历史记录是服务端渲染的 ——
     * 不补这一条，选完患者侧栏永远停在「暂无历史记录」，医生看不到这个人以前
     * 看过什么。从患者进来新建、或编辑已有病历这两条路本来就带着历史，
     * 只有通用入口断在这里。
     *
     * 返回片段而不是 JSON：省得把这段 Blade 在 JS 里再写一遍，两处各自漂移。
     */
    public function patientHistory($patient_id)
    {
        return response()->view('medical_cases.partials.sidebar_history_body', [
            'historyRecords' => $this->medicalCaseService->getPatientHistory((int) $patient_id),
        ]);
    }

    public function createForPatient(Request $request, $patient_id)
    {
        $data = $this->medicalCaseService->getCreateForPatientData((int) $patient_id);

        // 工作台「开病历」带着这次就诊过来（?appointment_id=）。带上它，
        // 保存时这份病历就挂在今天这次就诊上，而不是一份无主的记录；
        // 接诊医生与就诊类型也跟着这次挂号走 —— 挂号时刚选过，不该再问一遍。
        $appointmentId = $request->query('appointment_id');
        $data['appointmentId'] = $appointmentId;

        if ($appointmentId) {
            $appointment = \App\Appointment::where('id', (int) $appointmentId)
                ->where('patient_id', (int) $patient_id)   // 只认这位患者自己的就诊
                ->first();

            if ($appointment) {
                $data['prefillDoctorId'] = $appointment->doctor_id;
                // 预约的 first_visit/revisit 与病历的 initial/revisit 是两套写法
                $data['prefillVisitType'] = $appointment->appointment_type === 'revisit'
                    ? 'revisit'
                    : 'initial';
            }
        }

        return view('medical_cases.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $isDraft = $request->input('is_draft', '1') === '1';

        // 与 store() 同步：漏这一句的话，编辑已有病历时必填校验会误报
        $this->mergeDerivedCaseText($request);

        $allowedStatuses = implode(',', [\App\MedicalCase::STATUS_OPEN, \App\MedicalCase::STATUS_CLOSED, \App\MedicalCase::STATUS_FOLLOW_UP]);
        $rules = [
            'patient_id' => 'required|exists:patients,id',
            'case_date'  => 'required|date',
            'status'     => 'nullable|in:' . $allowedStatuses,
        ];

        if (!$isDraft) {
            $rules['chief_complaint'] = 'required|string|min:10';
            $rules['examination'] = 'required|string';
            $rules['diagnosis'] = 'required|string';
            $rules['treatment'] = 'required|string';
        }

        Validator::make($request->all(), $rules, [
            'patient_id.required' => __('validation.custom.patient_id.required'),
            'case_date.required' => __('validation.custom.case_date.required'),
            'chief_complaint.required' => __('medical_cases.chief_complaint_required'),
            'chief_complaint.min' => __('medical_cases.chief_complaint_min'),
            'examination.required' => __('medical_cases.examination_required'),
            'diagnosis.required' => __('medical_cases.diagnosis_required'),
            'treatment.required' => __('medical_cases.treatment_required'),
        ])->validate();

        $data = $this->medicalCaseService->buildCaseData($request->only([
            'patient_id', 'case_date', 'chief_complaint', 'history_of_present_illness', 'past_medical_history',
            'examination', 'examination_teeth', 'auxiliary_examination', 'related_images',
            'diagnosis', 'diagnosis_code', 'related_teeth', 'treatment', 'treatment_services',
            'medical_orders', 'next_visit_date', 'next_visit_note', 'auto_create_followup',
            'visit_type', 'doctor_id',
            // 与 store() 同步：漏这一项的话，新建能分行、编辑一保存就退回整段
            'case_items',
            // 诊断段走 diagnoses 表（带 ICD 编码），见 buildCaseData
            'diagnosis_rows',
        ]), isUpdate: true);
        $result = $this->medicalCaseService->updateCase(
            (int) $id,
            $data,
            $isDraft,
            $request->modification_reason,
            $request->status,
            $request->closing_notes
        );

        if (!empty($result['require_reason'])) {
            return response()->json([
                'message' => __('medical_cases.edit_requires_approval'),
                'status' => false,
                'require_reason' => true
            ]);
        }

        if (!empty($result['invalid_transition'])) {
            return response()->json([
                'message' => __('medical_cases.invalid_status_transition'),
                'status' => false,
            ]);
        }

        if (!empty($result['amendment_id'])) {
            return response()->json([
                'message' => __('medical_cases.amendment_submitted'),
                'status' => true,
                'amendment_id' => $result['amendment_id'],
                'id' => $id
            ]);
        }

        if ($result['status']) {
            return response()->json([
                'message' => $isDraft ? __('medical_cases.draft_saved') : __('medical_cases.case_updated_successfully'),
                'status' => true,
                'id' => $id
            ]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $status = $this->medicalCaseService->deleteCase((int) $id);
        if ($status) {
            return response()->json(['message' => __('medical_cases.case_deleted_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }

    /**
     * Print the specified medical case.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    /**
     * 病历纸（浏览器查看 / 打印）。
     *
     * 走 medical_cases.sheet 而不是 medical_cases.print：后者是 DomPDF 的模板
     * （表格版式、内联 style），归档 PDF 仍旧用它，见 exportPdf()。浏览器这条路
     * 用 sheet，屏幕上看到的就是打印出来的。
     */
    public function printCase($id)
    {
        AccessLog::log('MedicalCase', 'print', $id);
        $data = $this->medicalCaseService->getPrintData((int) $id);

        return view('medical_cases.sheet', $data);
    }

    /**
     * Export medical case as PDF download.
     */
    public function exportPdf($id)
    {
        AccessLog::log('MedicalCase', 'export_pdf', $id);
        $data = $this->medicalCaseService->getPrintData((int) $id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('medical_cases.print', $data)
            ->setPaper('a4');

        $filename = $data['case']->case_no . '_v' . ($data['case']->version_number ?? 1) . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Archive medical case PDF to storage.
     */
    public function archivePdf($id)
    {
        $data = $this->medicalCaseService->getPrintData((int) $id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('medical_cases.print', $data)
            ->setPaper('a4');

        $filename = $data['case']->case_no . '_v' . ($data['case']->version_number ?? 1) . '.pdf';
        $path = 'medical_records/' . $filename;

        \Illuminate\Support\Facades\Storage::put($path, $pdf->output());

        return response()->json([
            'message' => __('medical_cases.pdf_archived'),
            'status' => true,
            'path' => $path,
        ]);
    }

    /**
     * Search ICD-10 codes for diagnosis.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function searchIcd10(Request $request)
    {
        $query = $request->input('q', '');
        return response()->json($this->medicalCaseService->searchIcd10($query));
    }

    /**
     * List amendments for a medical case.
     */
    public function amendments($id)
    {
        $amendments = MedicalCaseAmendment::forCase($id)
            ->with(['requestedBy', 'approvedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['status' => true, 'data' => $amendments]);
    }

    /**
     * Approve an amendment request.
     */
    public function approveAmendment(Request $request, $amendmentId)
    {
        $this->authorize('approve-medical-case-amendment');

        $amendment = MedicalCaseAmendment::findOrFail($amendmentId);

        if ($amendment->status !== MedicalCaseAmendment::STATUS_PENDING) {
            return response()->json([
                'message' => __('medical_cases.amendment_already_reviewed'),
                'status' => false,
            ]);
        }

        $amendment->approve(auth()->id(), $request->input('review_notes'));

        return response()->json([
            'message' => __('medical_cases.amendment_approved'),
            'status' => true,
        ]);
    }

    /**
     * Reject an amendment request.
     */
    public function rejectAmendment(Request $request, $amendmentId)
    {
        $this->authorize('approve-medical-case-amendment');

        $amendment = MedicalCaseAmendment::findOrFail($amendmentId);

        if ($amendment->status !== MedicalCaseAmendment::STATUS_PENDING) {
            return response()->json([
                'message' => __('medical_cases.amendment_already_reviewed'),
                'status' => false,
            ]);
        }

        Validator::make($request->all(), [
            'review_notes' => 'required|string|min:5',
        ])->validate();

        $amendment->reject(auth()->id(), $request->input('review_notes'));

        return response()->json([
            'message' => __('medical_cases.amendment_rejected'),
            'status' => true,
        ]);
    }

    /**
     * Get version history (audit trail) for a medical case.
     */
    public function versionHistory($id)
    {
        $case = \App\MedicalCase::findOrFail($id);
        $history = $case->versionHistory();

        return response()->json(['status' => true, 'data' => $history]);
    }
}
