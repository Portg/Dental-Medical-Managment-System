@extends('layouts.list-page')

@section('page_title', __('inventory.requisitions'))
@section('table_id', 'requisitions-table')

@section('page_css')
    <link rel="stylesheet" href="{{ asset('css/requisition.css') }}">
@endsection

@section('header_actions')
    @can('request-inventory')
        <a class="btn btn-primary" href="{{ url('requisitions/create') }}">
            {{ __('inventory.create_requisition') }} <i class="fa fa-plus"></i>
        </a>
    @endcan
@endsection

@section('filter_area')
    <div class="row filter-row">
        <div class="col-md-3">
            <div class="filter-label">{{ __('inventory.status') }}</div>
            <select id="filter-status" class="form-control">
                <option value="">{{ __('common.all') }}</option>
                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>{{ __('inventory.status_draft') }}</option>
                <option value="pending_approval" {{ request('status') == 'pending_approval' ? 'selected' : '' }}>{{ __('inventory.status_pending_approval') }}</option>
                <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>{{ __('inventory.status_confirmed') }}</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>{{ __('inventory.status_rejected') }}</option>
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
    <th>{{ __('inventory.stock_out_no') }}</th>
    <th>{{ __('inventory.requisition_date') }}</th>
    <th>{{ __('inventory.items') }}</th>
    <th>{{ __('inventory.quantity') }}</th>
    <th>{{ __('inventory.added_by') }}</th>
    <th>{{ __('inventory.status') }}</th>
    <th>{{ __('inventory.created_at') }}</th>
    <th>{{ __('common.action') }}</th>
@endsection

@section('page_js')
    <script>
    LanguageManager.loadAllFromPHP({
        'inventory': @json(__('inventory')),
        'common':    @json(__('common'))
    });
    window.RequisitionListConfig = { initialStatus: '{{ request('status') }}' };
    </script>
    <script src="{{ asset('include_js/requisition_list.js') }}?v={{ filemtime(public_path('include_js/requisition_list.js')) }}" type="text/javascript"></script>
    <script>
    $(function () {
        var cfg = window.RequisitionListConfig || {};
        if (cfg.initialStatus) { $('#filter-status').val(cfg.initialStatus); }
        loadTable();
    });
    </script>
@endsection
