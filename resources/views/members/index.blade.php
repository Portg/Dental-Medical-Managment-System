@extends('layouts.list-page')

@section('page_title', __('members.page_title'))

@section('table_id', 'members_table')

@section('header_actions')
    <button type="button" class="btn btn-default" onclick="window.location.href='{{ url('member-levels') }}'">
        {{ __('members.manage_levels') }}
    </button>
    <button type="button" class="btn btn-primary" onclick="createRecord()">
        {{ __('members.register_member') }}
    </button>
@endsection

@section('filter_area')
    <div class="row">
        <div class="col-md-4">
            <select id="filter_level" class="form-control" onchange="doSearch()">
                <option value="">{{ __('members.all_levels') }}</option>
                @foreach($levels as $level)
                    <option value="{{ $level->id }}">{{ $level->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <select id="filter_status" class="form-control" onchange="doSearch()">
                <option value="">{{ __('members.all_statuses') }}</option>
                <option value="Active">{{ __('members.status_active') }}</option>
                <option value="Expired">{{ __('members.status_expired') }}</option>
            </select>
        </div>
    </div>
@endsection

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('members.member_no') }}</th>
    <th>{{ __('members.patient_name') }}</th>
    <th>{{ __('members.level') }}</th>
    <th>{{ __('members.balance') }}</th>
    <th>{{ __('members.points') }}</th>
    <th>{{ __('members.member_since') }}</th>
    <th>{{ __('members.status') }}</th>
    <th>{{ __('common.view') }}</th>
    <th>{{ __('members.deposit') }}</th>
    <th>{{ __('common.edit') }}</th>
@endsection

@section('empty_icon', 'fa-users')
@section('empty_title', __('members.no_members_found'))

@section('modals')
    @include('members.create')
    @include('members.edit')
    @include('members.deposit')
@endsection

@section('page_js')
<script>
    var levels = @json($levels);
    var patients = @json($patients);

    LanguageManager.loadAllFromPHP({
        'members': @json(__('members')),
        'messages': @json(__('messages'))
    });

    function createRecord() {
        addMember();
    }
</script>
<script src="{{ asset('include_js/members.js') }}"></script>
@endsection
