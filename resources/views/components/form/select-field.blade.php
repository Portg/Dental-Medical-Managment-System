{{--
    Select Field Component
    ======================

    Usage:
    @include('components.form.select-field', [
        'name' => 'source_id',
        'label' => __('patient.source'),
        'id' => 'source_id',             // Optional, defaults to name
        'required' => false,              // Optional
        'multiple' => false,              // Optional
        'placeholder' => 'Select...',    // Optional
        'options' => [                    // Optional static options
            ['value' => '1', 'text' => 'Option 1'],
            ['value' => '2', 'text' => 'Option 2'],
        ],
        'selected' => '1',               // Optional, selected value(s)
        'select2' => true,               // Optional, enable Select2
        'ajaxUrl' => '/api/sources',     // Optional, for Select2 AJAX
        'labelWidth' => 4,               // Optional
        'inputWidth' => 8,               // Optional
    ])
--}}

@php
    $required = $required ?? false;
    $multiple = $multiple ?? false;
    $placeholder = $placeholder ?? '';
    $options = $options ?? [];
    $selected = $selected ?? null;
    $select2 = $select2 ?? false;
    $ajaxUrl = $ajaxUrl ?? null;
    $id = $id ?? $name;
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
    <div class="col-md-{{ $inputWidth }}">
        <select name="{{ $name }}{{ $multiple ? '[]' : '' }}"
                id="{{ $id }}"
                class="form-control {{ $select2 ? 'select2' : '' }}"
                style="width: 100%;"
                @if($multiple) multiple @endif
                @if($required) required @endif
                @if($ajaxUrl) data-ajax-url="{{ $ajaxUrl }}" @endif
        >
            @if($placeholder && !$multiple)
                <option value="">{{ $placeholder }}</option>
            @endif
            @foreach($options as $option)
                <option value="{{ $option['value'] }}"
                    @if(is_array($selected) ? in_array($option['value'], $selected) : $option['value'] == $selected) selected @endif
                >{{ $option['text'] }}</option>
            @endforeach
        </select>
    </div>
</div>
