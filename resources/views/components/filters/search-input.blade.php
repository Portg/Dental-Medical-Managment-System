{{--
    Search Input Component
    ======================

    Usage:
    @include('components.filters.search-input', [
        'id' => 'quickSearch',
        'placeholder' => __('patient.search_patients'),
        'label' => __('common.search'),  // optional
        'colClass' => 'col-md-3',        // optional, default: col-md-3
    ])
--}}

@php
    $colClass = $colClass ?? 'col-md-3';
    $label = $label ?? null;
@endphp

<div class="{{ $colClass }}">
    @if($label)
    <div class="filter-label">{{ $label }}</div>
    @endif
    <div class="search-input-wrapper">
        <i class="fa fa-search search-icon"></i>
        <input type="text"
               id="{{ $id }}"
               class="form-control"
               placeholder="{{ $placeholder ?? __('common.search') }}">
    </div>
</div>
