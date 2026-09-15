{{-- 治疗计划（SOAP 的 P 之一）：打算做什么，跨次不变。

     与下面的「治疗」段分开 —— 活动义齿要来好几次，计划一直是「活动义齿修复」，
     而处置这次是「制取印模」、下次是「试戴」。修复、正畸、种植这类疗程性治疗，
     不拆开就只能把两件事揉进同一段文字里。参考产品的病历也是这么拆的。

     与 treatment_plans 表（报价单 + 风险告知 + 患者电子签名）不是一回事，
     那是商务与法律文书，入口不在病历页。 --}}
@include('medical_cases.partials.case_items_section', [
    'section'     => 'treatment_plan',
    'title'       => __('medical_cases.treatment_plan_section'),
    'hint'        => __('medical_cases.treatment_plan_hint'),
    'rows'        => ($caseItems['treatment_plan'] ?? []),
    'required'    => false,
])
