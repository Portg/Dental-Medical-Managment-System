{{--
    Radio Field Component
    =====================

    Usage:
    @include('components.form.radio-field', [
        'name' => 'gender',
        'label' => __('patient.gender'),
        'required' => true,
        'options' => [
            ['value' => 'Male', 'text' => __('patient.male')],
            ['value' => 'Female', 'text' => __('patient.female')],
        ],
        'selected' => 'Male',            // Optional
        'inline' => true,                // Optional, default: true
        'labelWidth' => 4,               // Optional
        'inputWidth' => 8,               // Optional
    ])
--}}

@php
    $required = $required ?? false;
    $inline = $inline ?? true;
    $selected = $selected ?? null;
    $labelWidth = $labelWidth ?? 4;
    $inputWidth = $inputWidth ?? 8;
@endphp

<div class="form-group">
    <label class="control-label col-md-{{ $labelWidth }}">
        @if($required)
            <span class="required-asterisk">*</span>
        @endif
        {{ $label }}
    </label>
    <div class="col-md-{{ $inputWidth }}" style="padding-top: 7px;">
        @foreach($options as $option)
            <label class="{{ $inline ? 'radio-inline' : 'radio' }}">
                <input type="radio"
                       name="{{ $name }}"
                       value="{{ $option['value'] }}"
                       @if($option['value'] == $selected) checked @endif
                       @if($required) required @endif
                > {{ $option['text'] }}
            </label>
        @endforeach
    </div>
</div>
