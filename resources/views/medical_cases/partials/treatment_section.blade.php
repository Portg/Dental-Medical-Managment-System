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

{{-- 治疗项目（关联收费项目）已去掉。

     划价面板是唯一的开单入口（92f20f8 把两套开单 UI 合成一套之后），这里再放一处
     选项目，等于同一件事有两个录入口 —— 两处对不上时谁也说不清以哪个为准。
     医生写病历只管临床叙述；这次做了哪些收费项目，在划价面板里划。

     treatment_services 这一列没有删，老病历上存过的项目仍能读出来（打印、详情页、
     API 都在消费它），只是不再从病历页录入。 --}}
