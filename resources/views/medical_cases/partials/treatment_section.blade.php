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
            <input type="hidden" name="treatment_services" id="treatment_services"
                   value="{{ json_encode($treatmentServices) }}">
        </div>
    </div>
</div>
