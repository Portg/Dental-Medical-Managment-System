{{--
    Text Field Component
    ====================

    Usage:
    @include('components.form.text-field', [
        'name' => 'surname',
        'label' => __('patient.surname'),
        'required' => true,              // Optional, default: false
        'placeholder' => 'Enter name',   // Optional
        'type' => 'text',                // Optional: text, email, tel, number, password
        'maxlength' => 50,               // Optional
        'hint' => 'Hint text',           // Optional
        'value' => $patient->surname,    // Optional
        'id' => 'surname_input',         // Optional, defaults to name
        'labelWidth' => 4,               // Optional, default: 4 (Bootstrap col-md-X)
        'inputWidth' => 8,               // Optional, default: 8
        'disabled' => false,             // Optional
        'readonly' => false,             // Optional
    ])
--}}

@php
    $required = $required ?? false;
    $type = $type ?? 'text';
    $placeholder = $placeholder ?? '';
    $maxlength = $maxlength ?? null;
    $hint = $hint ?? null;
    $value = $value ?? '';
    $id = $id ?? $name;
    $labelWidth = $labelWidth ?? 4;
    $inputWidth = $inputWidth ?? 8;
    $disabled = $disabled ?? false;
    $readonly = $readonly ?? false;
@endphp

<div class="form-group">
    <label class="control-label col-md-{{ $labelWidth }}">
        @if($required)
            <span class="required-asterisk">*</span>
        @endif
        {{ $label }}
    </label>
    <div class="col-md-{{ $inputWidth }}">
        <input type="{{ $type }}"
               name="{{ $name }}"
               id="{{ $id }}"
               class="form-control"
               placeholder="{{ $placeholder }}"
               value="{{ $value }}"
               @if($maxlength) maxlength="{{ $maxlength }}" @endif
               @if($required) required @endif
               @if($disabled) disabled @endif
               @if($readonly) readonly @endif
        >
        @if($hint)
            <div class="field-hint">{{ $hint }}</div>
        @endif
        <div class="validation-message" id="{{ $id }}-validation"></div>
    </div>
</div>
