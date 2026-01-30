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
            <div class="template-triggers">
                <button type="button" class="template-btn" onclick="insertTemplate('{{ $id }}', 'cleaning')">
                    {{ __('medical_cases.template_cleaning') }}
                </button>
                <button type="button" class="template-btn" onclick="insertTemplate('{{ $id }}', 'extraction')">
                    {{ __('medical_cases.template_extraction') }}
                </button>
                <button type="button" class="template-btn" onclick="insertTemplate('{{ $id }}', 'filling')">
                    {{ __('medical_cases.template_filling') }}
                </button>
            </div>
        @endif
    </div>
</div>
