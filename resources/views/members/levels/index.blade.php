@extends('layouts.list-page')

@section('page_title', __('members.member_levels'))

@section('table_id', 'levels_table')

@section('header_actions')
    <button type="button" class="btn btn-default" onclick="window.location.href='{{ url('members') }}'">
        {{ __('members.back_to_members') }}
    </button>
    <button type="button" class="btn btn-primary" onclick="createRecord()">
        {{ __('members.add_level') }}
    </button>
@endsection

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('members.level_name') }}</th>
    <th>{{ __('members.level_code') }}</th>
    <th>{{ __('members.discount') }}</th>
    <th>{{ __('members.min_consumption') }}</th>
    <th>{{ __('members.points_rate') }}</th>
    <th>{{ __('members.status') }}</th>
    <th>{{ __('common.edit') }}</th>
    <th>{{ __('common.delete') }}</th>
@endsection

@section('empty_icon', 'fa-star')
@section('empty_title', __('members.no_levels_found'))

@section('modals')
    @include('members.levels.create')
    @include('members.levels.edit')
@endsection

@section('page_js')
<script>
    LanguageManager.loadAllFromPHP({
        'members': @json(__('members')),
        'messages': @json(__('messages'))
    });

    function createRecord() {
        addLevel();
    }
</script>
<script src="{{ asset('include_js/member_levels.js') }}"></script>
@endsection
