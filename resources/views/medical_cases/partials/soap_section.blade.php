{{--
    Reusable SOAP Section Component
    Parameters:
    - $id: Field ID
    - $title: Section title
    - $hint: Optional hint text
    - $placeholder: Textarea placeholder
    - $value: Current value
    - $required: Whether field is required
    - $maxlength: Optional max length
    - $showCounter: Whether to show character counter
    - $showTemplates: Whether to show template buttons
--}}
<div class="soap-section">
    <div class="soap-section-header">
        <div class="soap-section-title">
            {{ $title }}
            @if($required ?? false)
                <span class="required">*</span>
            @endif
        </div>
        @if(isset($hint))
            <div class="soap-section-hint">{{ $hint }}</div>
        @endif
    </div>
    <div class="soap-section-body">
        <textarea
            name="{{ $id }}"
            id="{{ $id }}"
            class="soap-textarea"
            placeholder="{{ $placeholder ?? '' }}"
            @if(isset($maxlength)) maxlength="{{ $maxlength }}" @endif
            @if($required ?? false) required @endif
        >{{ $value ?? '' }}</textarea>

        @if($showCounter ?? false)
            <div class="char-counter">
                <span id="{{ $id }}_count">{{ strlen($value ?? '') }}</span>/{{ $maxlength ?? 500 }}
            </div>
        @endif

        @if($showTemplates ?? false)
            {{-- 常用模板按钮由 JS 从库里取（按使用次数排序），不写死在这里。

                 原来这三个按钮是硬编码的中文串（洁牙/拔牙/补牙），与
                 medical_templates 表里同名的模板内容并不一致 —— 库里的「拔牙主诉」
                 是「患者要求拔除__牙，该牙反复疼痛/松动/无法保留」，硬编码那条是
                 断了半句的「患者要求拔除牙齿，该牙」，而且没有 __ 牙位占位符。
                 诊所在「病历模板」页改了内容，这三个按钮也不会跟着变。

                 现在按钮和 / 触发的选择器走同一份数据、同一个插入函数
                 （handleTemplateInsert），__ 替换与 SOAP 多字段填充的行为完全一致。
                 保留按钮是因为 / 这个入口不可见，新人不知道有这功能。 --}}
            <div class="template-triggers js-template-quick-buttons"
                 data-field="{{ $id }}"
                 data-template-type="{{ $templateType ?? $id }}"></div>
        @endif
    </div>
</div>
