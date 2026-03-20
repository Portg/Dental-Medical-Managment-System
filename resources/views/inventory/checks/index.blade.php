@extends('layouts.list-page')

@section('page_title', __('inventory.inventory_checks'))
@section('table_id', 'checks-table')

@section('page_css')
    <link rel="stylesheet" href="{{ asset('css/inventory-check.css') }}">
@endsection

@section('header_actions')
    <a class="btn btn-primary" href="{{ url('inventory-checks/create') }}">
        {{ __('inventory.create_check') }} <i class="fa fa-plus"></i>
    </a>
@endsection

@section('filter_area')
    <div class="row filter-row">
        <div class="col-md-3">
            <div class="filter-label">{{ __('inventory.status') }}</div>
            <select id="filter-status" class="form-control">
                <option value="">{{ __('common.all') }}</option>
                <option value="draft">{{ __('inventory.status_draft') }}</option>
                <option value="confirmed">{{ __('inventory.status_confirmed') }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <div class="filter-label">{{ __('inventory.category') }}</div>
            <select id="filter-category" class="form-control">
                <option value="">{{ __('common.all') }}</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 text-right filter-actions">
            <button class="btn btn-default" onclick="clearFilters()">{{ __('common.reset') }}</button>
            <button class="btn btn-primary" onclick="filterTable()">{{ __('inventory.filter') }}</button>
        </div>
    </div>
@endsection

@section('table_headers')
    <th>{{ __('inventory.sn') }}</th>
    <th>{{ __('inventory.check_no') }}</th>
    <th>{{ __('inventory.category') }}</th>
    <th>{{ __('inventory.check_date') }}</th>
    <th>{{ __('inventory.items_count') }}</th>
    <th>{{ __('inventory.status') }}</th>
    <th>{{ __('inventory.added_by') }}</th>
    <th>{{ __('inventory.created_at') }}</th>
    <th>{{ __('common.action') }}</th>
@endsection

@section('page_js')
    <script>
    LanguageManager.loadAllFromPHP({
        'inventory': @json(__('inventory')),
        'common':    @json(__('common'))
    });
    window.InventoryCheckConfig = { csrfToken: '{{ csrf_token() }}' };
    </script>
    <script src="{{ asset('include_js/inventory_check.js') }}?v={{ filemtime(public_path('include_js/inventory_check.js')) }}" type="text/javascript"></script>
    <script>
    $(function () { loadCheckTable(); });
    </script>
@endsection
