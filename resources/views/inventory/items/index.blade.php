@extends('layouts.list-page')

@section('page_title', __('inventory.items'))
@section('table_id', 'items-table')

@section('header_actions')
    <button class="btn btn-primary" onclick="createRecord()">{{ __('inventory.add_item') }}</button>
@endsection

@section('filter_primary')
    <div class="col-md-4">
        <div class="filter-label">{{ __('inventory.category') }}</div>
        <select id="filter-category" class="form-control">
            <option value="">{{ __('inventory.select_category') }}</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
@endsection

@section('table_headers')
    <th>{{ __('inventory.sn') }}</th>
    <th>{{ __('inventory.item_code') }}</th>
    <th>{{ __('inventory.item_name') }}</th>
    <th>{{ __('inventory.specification') }}</th>
    <th>{{ __('inventory.unit') }}</th>
    <th>{{ __('inventory.category') }}</th>
    <th>{{ __('inventory.current_stock') }}</th>
    <th>{{ __('inventory.status') }}</th>
    <th>{{ __('common.edit') }}</th>
    <th>{{ __('common.delete') }}</th>
@endsection

@section('modals')
    @include('inventory.items.create')
@endsection

@section('page_js')
    <script type="text/javascript">
        LanguageManager.loadAllFromPHP({
            'inventory': @json(__('inventory')),
            'common': @json(__('common'))
        });
    </script>
    <script src="{{ asset('include_js/inventory_items.js') }}" type="text/javascript"></script>
@endsection
