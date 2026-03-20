@extends('layouts.list-page')

@section('page_title', __('holidays.title'))

@section('table_id', 'holidays_table')

@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createRecord()">
        {{ __('common.add_new') }}
    </button>
@endsection

@section('filter_area')
    <div class="row filter-row">
        <div class="col-md-3">
            <div class="form-group">
                <label>{{ __('holidays.holiday_name') }}</label>
                <input type="text" class="form-control" id="filter_name" placeholder="{{ __('holidays.enter_holiday_name') }}">
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                <label>{{ __('holidays.repeat_every_year') }}</label>
                <select class="form-control" id="filter_repeat">
                    <option value="">{{ __('common.all') }}</option>
                    <option value="Yes">{{ __('common.yes') }}</option>
                    <option value="No">{{ __('common.no') }}</option>
                </select>
            </div>
        </div>
        <div class="col-md-3 text-right filter-actions">
            <div class="form-group">
                <div>
                    <button type="button" class="btn btn-primary" onclick="doSearch()">{{ __('common.search') }}</button>
                    <button type="button" class="btn btn-default" onclick="clearFilters()">{{ __('common.reset') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('holidays.added_date') }}</th>
    <th>{{ __('holidays.holiday_name') }}</th>
    <th>{{ __('holidays.date_of_the_year') }}</th>
    <th>{{ __('holidays.repeat_every_year') }}</th>
    <th>{{ __('holidays.added_by') }}</th>
    <th>{{ __('common.action') }}</th>
@endsection

@section('modals')
@include('holidays.create')
@endsection

@section('page_js')
<script type="text/javascript">
    LanguageManager.loadFromPHP(@json(__('holidays')), 'holidays');
    window.HolidaysConfig = { baseUrl: "{{ url('/holidays') }}" };
</script>
<script src="{{ asset('include_js/holidays_index.js') }}?v={{ filemtime(public_path('include_js/holidays_index.js')) }}"></script>
@endsection
