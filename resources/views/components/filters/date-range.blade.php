{{--
    Date Range Component
    ====================

    Usage:
    @include('components.filters.date-range', [
        'startId' => 'start_date',
        'endId' => 'end_date',
        'label' => __('datetime.date_range.title'),  // optional
        'colClass' => 'col-md-6',                    // optional
        'startClass' => 'start_date',                // optional, additional class
        'endClass' => 'end_date',                    // optional, additional class
    ])
--}}

@php
    $colClass = $colClass ?? 'col-md-6';
    $startClass = $startClass ?? 'start_date';
    $endClass = $endClass ?? 'end_date';
@endphp

<div class="{{ $colClass }}">
    @if(isset($label))
    <div class="filter-label">{{ $label }}</div>
    @endif
    <div class="date-range-row">
        <div class="date-input">
            <input type="text"
                   id="{{ $startId ?? 'start_date' }}"
                   class="form-control {{ $startClass }}"
                   placeholder="{{ __('datetime.date_range.start_date') }}">
        </div>
        <span class="date-separator">&rarr;</span>
        <div class="date-input">
            <input type="text"
                   id="{{ $endId ?? 'end_date' }}"
                   class="form-control {{ $endClass }}"
                   placeholder="{{ __('datetime.date_range.end_date') }}">
        </div>
    </div>
</div>
