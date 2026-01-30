{{--
    Textarea Field Component
    ========================

    Usage:
    @include('components.form.textarea-field', [
        'name' => 'notes',
        'label' => __('patient.notes'),
        'placeholder' => 'Enter notes...',
        'rows' => 3,                     // Optional, default: 3
        'required' => false,
        'value' => $patient->notes,
        'hint' => 'Optional hint text',
        'labelWidth' => 2,               // Optional
        'inputWidth' => 10,              // Optional
    ])
--}}

@php
    $required = $required ?? false;
    $placeholder = $placeholder ?? '';
    $rows = $rows ?? 3;
    $value = $value ?? '';
    $hint = $hint ?? null;
    $id = $id ?? $name;
    $labelWidth = $labelWidth ?? 4;
    $inputWidth = $inputWidth ?? 8;
    $maxlength = $maxlength ?? null;
@endphp

<div class="form-group">
    <label class="control-label col-md-{{ $labelWidth }}">
        @if($required)
            <span class="required-asterisk">*</span>
        @endif
        {{ $label }}
    </label>
    <div class="col-md-{{ $inputWidth }}">
        <textarea name="{{ $name }}"
                  id="{{ $id }}"
                  class="form-control"
                  rows="{{ $rows }}"
                  placeholder="{{ $placeholder }}"
                  @if($maxlength) maxlength="{{ $maxlength }}" @endif
                  @if($required) required @endif
        >{{ $value }}</textarea>
        @if($hint)
            <div class="field-hint">{{ $hint }}</div>
        @endif
    </div>
</div>
