@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('title', __('medical_cases.medical_record'))
@section('page_title', __('medical_cases.medical_record'))

{{-- 病历纸 —— 病历的呈现态。
     录入在表单里（medical_cases/edit.blade.php），这里只呈现，不编辑。
     一个字段一个输入框的形态是给录入用的；病历交到患者、同行或病案室手里时，
     它该是一份文书。

     屏幕查看与浏览器打印共用这一份：屏幕上看到的就是打印出来的。
     归档 PDF（export-pdf / archive-pdf）另走 medical_cases/print.blade.php ——
     那条路是 DomPDF 渲染的，不支持 flex / grid，没法和这里共用版式。 --}}

@php
    $patient = $case->patient ?? null;

    $patientAge = null;
    if ($patient) {
        if ($patient->date_of_birth) {
            $patientAge = \Carbon\Carbon::parse($patient->date_of_birth)->age;
        } elseif ($patient->age !== null && $patient->age !== '') {
            $patientAge = (int) $patient->age;
        }
    }

    // 纸上的段落顺序与表单一致：检查 → 辅助检查 → 诊断 → 治疗 → 医嘱。
    // 诊断不在 caseItems 里（它走 diagnoses 表，带 ICD 编码），单独渲染。
    $itemSections = [
        'examination'           => __('medical_cases.examination_section'),
        'auxiliary_examination' => __('medical_cases.auxiliary_section'),
    ];
@endphp

@section('css')
    <link rel="stylesheet" href="{{ asset('css/medical-record-paper.css') }}?v={{ filemtime(public_path('css/medical-record-paper.css')) }}">
@endsection

@section('content')
{{-- 工具条不在纸上，也不打印 --}}
<div class="mr-toolbar">
    <div class="mr-toolbar-title">
        <a href="{{ url('medical-cases') }}" class="text-primary">{{ __('medical_cases.page_title') }}</a>
        / {{ __('medical_cases.medical_record') }}
        @if($case->is_draft)
            <span class="label label-warning">{{ __('medical_cases.draft_status') }}</span>
        @endif
    </div>
    <div class="mr-toolbar-actions">
        @can('manage-medical-cases')
            <a class="btn btn-default" href="{{ url('medical-cases/' . $case->id . '/edit') }}">
                <i class="fa fa-pencil"></i> {{ __('common.edit') }}
            </a>
        @endcan
        <a class="btn btn-default" href="{{ url('medical-cases/' . $case->id . '/export-pdf') }}">
            <i class="fa fa-file-pdf-o"></i> PDF
        </a>
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <i class="fa fa-print"></i> {{ __('common.print') }}
        </button>
    </div>
</div>

<div class="mr-stage">
    <div class="mr-paper">
        {{-- 抬头 --}}
        <div class="mr-sheet-head">
            <div class="mr-clinic">{{ __('company.name') }}</div>
            <div class="mr-sheet-title">{{ __('medical_cases.medical_record') }}</div>
            <div class="mr-sheet-meta">
                {{ __('medical_cases.case_no') }}: {{ $case->case_no }}
                @if($case->version_number)
                    &nbsp;&nbsp;{{ __('medical_cases.version_number') }}: v{{ $case->version_number }}
                @endif
            </div>
        </div>
        <div class="mr-rule-double"></div>

        {{-- 患者身份 --}}
        <div class="mr-identity">
            <div class="mr-cell">
                <span class="mr-cell-key">{{ __('common.patient') }}</span>
                <span class="mr-cell-val">{{ $patient->full_name ?? '-' }}</span>
            </div>
            <div class="mr-cell">
                <span class="mr-cell-key">{{ __('common.gender') }}/{{ __('common.age') }}</span>
                <span class="mr-cell-val">
                    @if($patient){{ $patient->gender == 'Male' ? __('patient.male') : __('patient.female') }}@endif
                    @if($patientAge !== null) {{ $patientAge }}{{ __('common.years_old') }}@endif
                </span>
            </div>
            <div class="mr-cell">
                <span class="mr-cell-key">{{ __('common.phone') }}</span>
                <span class="mr-cell-val">{{ $patient->phone_no ?? '-' }}</span>
            </div>
        </div>

        {{-- 过敏史印在病历上，不只是界面提醒 —— 接诊时必须看见的医疗信息 --}}
        @if($patient && $patient->drug_allergies_other)
            <div class="mr-allergy">
                {{ __('medical_cases.patient_allergy') }}：{{ $patient->drug_allergies_other }}
            </div>
        @endif

        {{-- 就诊信息 --}}
        <div class="mr-identity mr-visit">
            <div class="mr-cell">
                <span class="mr-cell-key">{{ __('medical_cases.case_date') }}</span>
                <span class="mr-cell-val">{{ $case->case_date ? $case->case_date->format('Y-m-d') : '-' }}</span>
            </div>
            <div class="mr-cell">
                <span class="mr-cell-key">{{ __('medical_cases.attending_doctor') }}</span>
                <span class="mr-cell-val">{{ $case->doctor->full_name ?? '-' }}</span>
            </div>
            <div class="mr-cell">
                <span class="mr-cell-key">{{ __('medical_cases.visit_type') }}</span>
                <span class="mr-cell-val">{{ $case->visit_type ? __('medical_cases.visit_type_' . $case->visit_type) : '-' }}</span>
            </div>
        </div>

        <div class="mr-rule"></div>

        {{-- 主诉 / 现病史 / 既往史 --}}
        <div class="mr-row">
            <div class="mr-label">{{ __('medical_cases.chief_complaint_section') }}</div>
            <div class="mr-value">{{ $case->chief_complaint }}</div>
        </div>
        <div class="mr-row">
            <div class="mr-label">{{ __('medical_cases.present_illness_section') }}</div>
            <div class="mr-value">{{ $case->history_of_present_illness }}</div>
        </div>
        <div class="mr-row">
            <div class="mr-label">{{ __('medical_cases.past_history_section') }}</div>
            <div class="mr-value">{{ $case->past_medical_history }}</div>
        </div>

        {{-- 检查 / 辅助检查：分行明细 --}}
        @foreach($itemSections as $section => $label)
            <div class="mr-row">
                <div class="mr-label">{{ $label }}</div>
                <div class="mr-value mr-value-items">
                    @forelse($caseItems[$section] ?? [] as $row)
                        <div class="mr-item">
                            @if(!empty($row['tooth_no']))
                                <span class="mr-tooth">{{ $row['tooth_no'] }}</span>
                            @endif
                            <span class="mr-item-text">{{ $row['content'] }}</span>
                        </div>
                    @empty
                        <div class="mr-item mr-item-blank"></div>
                    @endforelse
                </div>
            </div>
        @endforeach

        {{-- 诊断：走 diagnoses 表，带 ICD 编码。

             diagnoses 一条都没有时回落到 medical_cases.diagnosis 那段派生文本 ——
             2026-08-25 把诊断接进 diagnoses 表之前建的病历，诊断只存在那一列里。
             不兜这一下，老病历打出来诊断栏是空的，而检查与治疗因为
             getCaseItemsForEdit 自带回退反而印得出来，一份病历缺一段，比整份
             印不出来更容易被漏掉。 --}}
        @php($legacyDiagnosis = trim((string) ($case->diagnosis ?? '')))
        <div class="mr-row">
            <div class="mr-label">{{ __('medical_cases.diagnosis_section') }}</div>
            <div class="mr-value mr-value-items">
                @forelse($diagnoses as $diagnosis)
                    <div class="mr-item">
                        @if($diagnosis->tooth_no)
                            <span class="mr-tooth">{{ $diagnosis->tooth_no }}</span>
                        @endif
                        <span class="mr-item-text">{{ $diagnosis->diagnosis_name }}</span>
                    </div>
                @empty
                    @if($legacyDiagnosis !== '')
                        <div class="mr-item"><span class="mr-item-text">{{ $legacyDiagnosis }}</span></div>
                    @else
                        <div class="mr-item mr-item-blank"></div>
                    @endif
                @endforelse
            </div>
        </div>

        {{-- 治疗计划：打算做什么（跨次不变）。没写就不占一行 ——
             一次做完的治疗（补牙、拔牙）本来就没有跨次计划。 --}}
        @if(!empty($caseItems['treatment_plan'] ?? []))
            <div class="mr-row">
                <div class="mr-label">{{ __('medical_cases.treatment_plan_section') }}</div>
                <div class="mr-value mr-value-items">
                    @foreach($caseItems['treatment_plan'] as $row)
                        <div class="mr-item">
                            @if(!empty($row['tooth_no']))
                                <span class="mr-tooth">{{ $row['tooth_no'] }}</span>
                            @endif
                            <span class="mr-item-text">{{ $row['content'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- 治疗：这次实际做了什么 --}}
        <div class="mr-row">
            <div class="mr-label">{{ __('medical_cases.treatment_section') }}</div>
            <div class="mr-value mr-value-items">
                @forelse($caseItems['treatment'] ?? [] as $row)
                    <div class="mr-item">
                        @if(!empty($row['tooth_no']))
                            <span class="mr-tooth">{{ $row['tooth_no'] }}</span>
                        @endif
                        <span class="mr-item-text">{{ $row['content'] }}</span>
                    </div>
                @empty
                    <div class="mr-item mr-item-blank"></div>
                @endforelse
            </div>
        </div>

        {{-- 医嘱 --}}
        <div class="mr-row">
            <div class="mr-label">{{ __('medical_cases.medical_orders_section') }}</div>
            <div class="mr-value">{{ $case->medical_orders }}</div>
        </div>

        @if($case->next_visit_date || $case->next_visit_note)
            <div class="mr-rule"></div>
            <div class="mr-row">
                <div class="mr-label">{{ __('medical_cases.next_visit_date') }}</div>
                <div class="mr-value mr-value-items">
                    {{ $case->next_visit_date ? $case->next_visit_date->format('Y-m-d') : '' }}
                    @if($case->next_visit_note)
                        &nbsp;&nbsp;{{ $case->next_visit_note }}
                    @endif
                </div>
            </div>
        @endif

        {{-- ICD 编码单独成一行，不混进诊断正文。

             ICD 是给医保结算与病案统计用的编码，不是给人读的临床叙述 ——
             印给患者的病历上，「急性牙髓炎」是内容，「K04.0」是元数据。缀在诊断
             后面既没帮到懂的人（他要的是能导出结算的结构化数据，不是纸上一行字），
             又干扰了不懂的人。参考的那套桌面软件也是把 ICD 放在独立字段里。

             编码与名称都印：只印编码看不懂，只印名称对不上医保。 --}}
        @php($coded = $diagnoses->filter(fn ($d) => filled($d->icd_code)))
        @if($coded->isNotEmpty())
            <div class="mr-coding">
                <span class="mr-coding-key">{{ __('medical_cases.icd_code') }}</span>
                @foreach($coded as $d)
                    <span class="mr-coding-item">{{ $d->icd_code }}@if($d->icd_name) {{ $d->icd_name }}@endif</span>
                @endforeach
            </div>
        @endif

        {{-- 落款 --}}
        <div class="mr-sign">
            <div class="mr-sign-item">
                <div class="mr-sign-slot">
                    @if($case->signature && str_starts_with($case->signature, 'data:image'))
                        <img src="{{ $case->signature }}" alt="{{ __('medical_cases.doctor_signature') }}">
                    @endif
                </div>
                <div class="mr-sign-line">{{ __('medical_cases.doctor_signature') }}</div>
            </div>
            <div class="mr-sign-item">
                <div class="mr-sign-slot">
                    <span class="mr-sign-date">{{ $case->case_date ? $case->case_date->format('Y-m-d') : '' }}</span>
                </div>
                <div class="mr-sign-line">{{ __('medical_cases.case_date') }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
