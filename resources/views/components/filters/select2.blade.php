{{--
    Select2 Component
    =================

    Usage:
    @include('components.filters.select2', [
        'id' => 'filter_source',
        'name' => 'filter_source',
        'label' => __('patient_tags.source'),
        'placeholder' => __('patient_tags.select_source'),
        'colClass' => 'col-md-2',           // optional
        'multiple' => false,                 // optional
        'options' => $sources,               // optional, array of ['id' => x, 'text' => 'label']
        'ajaxUrl' => '/search-source',       // optional, for ajax loading
        'minInputLength' => 2,               // optional, for ajax
    ])
--}}

@php
    $colClass = $colClass ?? 'col-md-3';
    $multiple = $multiple ?? false;
    $options = $options ?? [];
@endphp

<div class="{{ $colClass }}">
    @if(isset($label))
    <div class="filter-label">{{ $label }}</div>
    @endif
    <select id="{{ $id }}"
            name="{{ $name ?? $id }}{{ $multiple ? '[]' : '' }}"
            class="form-control select2"
            style="width: 100%;"
            {{ $multiple ? 'multiple' : '' }}
            data-placeholder="{{ $placeholder ?? '' }}"
            @if(isset($ajaxUrl)) data-ajax-url="{{ $ajaxUrl }}" @endif
            @if(isset($minInputLength)) data-min-input="{{ $minInputLength }}" @endif>
        @if(!$multiple && isset($placeholder))
        <option value="">{{ $placeholder }}</option>
        @endif
        @foreach($options as $option)
        <option value="{{ $option['id'] }}">{{ $option['text'] }}</option>
        @endforeach
    </select>
</div>
