{{--
    Time Period Selector Component
    ==============================

    Usage:
    @include('components.filters.time-period', [
        'id' => 'period_selector',
        'label' => __('datetime.time_period'),  // optional
        'colClass' => 'col-md-2',               // optional
        'includeAll' => true,                   // optional, include "All" option
    ])
--}}

@php
    $colClass = $colClass ?? 'col-md-2';
    $includeAll = $includeAll ?? true;
@endphp

<div class="{{ $colClass }}">
    @if(isset($label))
    <div class="filter-label">{{ $label }}</div>
    @endif
    <select class="form-control" id="{{ $id }}">
        @if($includeAll)
        <option value="">{{ __('datetime.time_periods.all') }}</option>
        @endif
        <option value="Today">{{ __('datetime.time_periods.today') }}</option>
        <option value="Yesterday">{{ __('datetime.time_periods.yesterday') }}</option>
        <option value="This week">{{ __('datetime.time_periods.this_week') }}</option>
        <option value="Last week">{{ __('datetime.time_periods.last_week') }}</option>
        <option value="This Month">{{ __('datetime.time_periods.this_month') }}</option>
        <option value="Last Month">{{ __('datetime.time_periods.last_month') }}</option>
    </select>
</div>
