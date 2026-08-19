{{-- 诊断（A - Assessment）：分行录入「牙位 + 文字」。

     原来的「关联牙位」标签列表（related_teeth）改为每行一个牙位，
     related_teeth 由行派生。 --}}
@include('medical_cases.partials.case_items_section', [
    'section'     => 'diagnosis',
    'title'       => __('medical_cases.diagnosis_section'),
    'hint'        => __('medical_cases.diagnosis_hint'),
    'rows'        => ($caseItems['diagnosis'] ?? []),
    'required'    => true,
])
