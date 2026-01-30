{{--
    Checkbox Field Component
    ========================

    Usage (single checkbox):
    @include('components.form.checkbox-field', [
        'name' => 'is_active',
        'label' => __('common.status'),
        'text' => __('common.active'),
        'value' => '1',
        'checked' => true,
    ])

    Usage (checkbox group):
    @include('components.form.checkbox-field', [
        'name' => 'drug_allergies',
        'label' => __('patient.drug_allergy'),
        'options' => [
            ['value' => 'penicillin', 'text' => __('patient.allergy_penicillin')],
            ['value' => 'sulfa', 'text' => __('patient.allergy_sulfa')],
        ],
        'selected' => ['penicillin'],    // Array of selected values
        'inline' => true,
        'showOther' => true,             // Optional, show "other" text input
        'otherPlaceholder' => 'Other...', // Optional
    ])
--}}

@php
    $required = $required ?? false;
    $inline = $inline ?? true;
    $options = $options ?? [];
    $selected = $selected ?? [];
    $checked = $checked ?? false;
    $value = $value ?? '1';
    $text = $text ?? '';
    $showOther = $showOther ?? false;
    $otherPlaceholder = $otherPlaceholder ?? __('common.other');
    $labelWidth = $labelWidth ?? 4;
    $inputWidth = $inputWidth ?? 8;

    // Ensure selected is array
    if (!is_array($selected)) {
        $selected = $selected ? [$selected] : [];
    }
@endphp

<div class="form-group">
    <label class="control-label col-md-{{ $labelWidth }}">
        @if($required)
            <span class="required-asterisk">*</span>
        @endif
        {{ $label }}
    </label>
    <div class="col-md-{{ $inputWidth }}">
        @if(count($options) > 0)
            {{-- Checkbox Group --}}
            <div class="checkbox-group">
                @foreach($options as $option)
                    <label class="{{ $inline ? 'checkbox-inline' : 'checkbox' }}">
                        <input type="checkbox"
                               name="{{ $name }}[]"
                               value="{{ $option['value'] }}"
                               @if(in_array($option['value'], $selected)) checked @endif
                        > {{ $option['text'] }}
                    </label>
                @endforeach
            </div>
            @if($showOther)
                <div style="margin-top: 8px;">
                    <input type="text"
                           name="{{ $name }}_other"
                           class="form-control"
                           placeholder="{{ $otherPlaceholder }}"
                           style="max-width: 300px;">
                </div>
            @endif
        @else
            {{-- Single Checkbox --}}
            <div style="padding-top: 7px;">
                <label class="checkbox-inline">
                    <input type="checkbox"
                           name="{{ $name }}"
                           value="{{ $value }}"
                           @if($checked) checked @endif
                    > {{ $text }}
                </label>
            </div>
        @endif
    </div>
</div>
