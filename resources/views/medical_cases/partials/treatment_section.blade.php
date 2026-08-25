{{-- 治疗（P - Plan）：分行录入「牙位 + 文字」，另附治疗项目。

     文字部分改成分行明细（见 case_items_section）；治疗项目原样保留 ——
     它连着收费与库存扣减（treatment_services），与分行无关。 --}}
@php
    $treatmentServices = isset($case) && $case->treatment_services ? $case->treatment_services : [];
@endphp

@include('medical_cases.partials.case_items_section', [
    'section'     => 'treatment',
    'title'       => __('medical_cases.treatment_section'),
    'hint'        => __('medical_cases.treatment_hint'),
    'rows'        => ($caseItems['treatment'] ?? []),
    'required'    => true,
])

{{-- 治疗项目原来单占一张卡片，和「治疗」平级 —— 它是这次治疗做了哪些收费项目，
     附属于治疗。并进同一张卡片。 --}}
<div class="soap-section soap-section-attached">
    <div class="soap-section-body">
        <div>
            <label style="font-size: 13px; color: #666; margin-bottom: 6px; display: block;">
                {{ __('medical_cases.treatment_services') }}
            </label>
            <div class="service-tags" id="treatment-service-tags">
                @foreach($treatmentServices as $service)
                    <span class="service-tag" data-id="{{ $service['id'] }}">
                        {{ $service['name'] ?? $service['id'] }}
                        <span class="remove-service" onclick="removeService('{{ $service['id'] }}')">&times;</span>
                    </span>
                @endforeach
                <button type="button" class="add-teeth-btn" onclick="openServiceSelector()">
                    {{ __('medical_cases.add_service') }}
                </button>
            </div>

            {{-- 制定治疗计划 —— 医生是在写完治疗时决定要不要做方案的，入口就该在这儿。

                 治疗计划不是「病历的一段」：它有报价、要患者签字（treatment_plans 上
                 有 estimated_cost / final_price / risk_disclosure / electronic_signature），
                 而且跨多次就诊。所以做成独立文书，从这里带上下文跳过去，
                 而不是塞进病历里 —— 和加工单同样的处理。

                 treatment_plans 此前一直是 0 条，原因和 diagnoses 一样：主流程走不到，
                 只能从病历详情页的 Tab 里单独添加。 --}}
            @can('edit-patients')
                @if(isset($case) && $case->exists)
                    <div style="margin-top: 10px;">
                        <a class="btn btn-xs btn-default"
                           href="{{ url('treatment-plans') }}?{{ http_build_query([
                                'patient_id'      => $case->patient_id,
                                'medical_case_id' => $case->id,
                           ]) }}">
                            <i class="fa fa-list-alt"></i> {{ __('medical_cases.create_treatment_plan') }}
                        </a>
                    </div>
                @endif
            @endcan
            <input type="hidden" name="treatment_services" id="treatment_services"
                   value="{{ json_encode($treatmentServices) }}">
        </div>
    </div>
</div>
