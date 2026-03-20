@extends('layouts.list-page')

@section('page_title', __('chairs.chairs_management'))
@section('table_id', 'chairs-table')

@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createRecord()">{{ __('common.add_new') }}</button>
@endsection

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('chairs.chair_code') }}</th>
    <th>{{ __('chairs.chair_name') }}</th>
    <th>{{ __('chairs.branch') }}</th>
    <th>{{ __('chairs.status') }}</th>
    <th>{{ __('chairs.added_by') }}</th>
    <th>{{ __('common.action') }}</th>
@endsection

@section('modals')
    @include('chairs.create')
@endsection

@section('page_js')
<script type="text/javascript">
    LanguageManager.loadFromPHP(@json(__('chairs')), 'chairs');
    window.ChairsConfig = { baseUrl: "{{ url('/chairs') }}" };
</script>
<script src="{{ asset('include_js/chairs_index.js') }}?v={{ filemtime(public_path('include_js/chairs_index.js')) }}"></script>
@endsection
