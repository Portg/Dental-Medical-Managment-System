@extends('layouts.list-page')

@section('page_title', __('leaves.leave_requests'))

@section('table_id', 'leave-requests_table')

@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createRecord()">
        {{ __('common.add_new') }}
    </button>
@endsection

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('leaves.request_date') }}</th>
    <th>{{ __('leaves.leave_type') }}</th>
    <th>{{ __('leaves.start_date') }}</th>
    <th>{{ __('leaves.duration') }}</th>
    <th>{{ __('leaves.status') }}</th>
    <th>{{ __('common.edit') }}</th>
    <th>{{ __('common.delete') }}</th>
@endsection

@section('modals')
@include('leave_requests.create')
@endsection

@section('page_js')
<script type="text/javascript">
    LanguageManager.loadAllFromPHP({
        'leaves': @json(__('leaves'))
    });

    window.LeaveRequestsConfig = {
        urls: {
            leaveRequests: "{{ url('/leave-requests') }}",
            getAllLeaveTypes: "{{ url('/get-all-leave-types') }}"
        },
        locale: "{{ app()->getLocale() }}"
    };
</script>
<script src="{{ asset('include_js/leave_requests_index.js') }}?v={{ filemtime(public_path('include_js/leave_requests_index.js')) }}"></script>
@endsection
