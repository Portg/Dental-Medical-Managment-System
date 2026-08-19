{{-- 检查（O - Objective）：分行录入「牙位 + 文字」。

     原来是一个 textarea + 整段共享的牙位标签列表（examination_teeth），
     文字和牙位对不上号 —— 见 case_items_section 的注释。
     牙位列与文本列现在都由行在服务端派生。 --}}
@include('medical_cases.partials.case_items_section', [
    'section'     => 'examination',
    'title'       => __('medical_cases.examination_section'),
    'hint'        => __('medical_cases.examination_hint'),
    'rows'        => ($caseItems['examination'] ?? []),
    'required'    => true,
])
