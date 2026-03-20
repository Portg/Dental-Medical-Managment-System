@extends('layouts.list-page')

@section('page_title', __('online_bookings.appointments_online_bookings'))
@section('table_id', 'bookings-table')

@section('filter_area')
    <div class="row filter-row">
        <div class="col-md-3">
            <div class="filter-label">{{ __('online_bookings.period') }}</div>
            <select class="form-control" id="period_selector">
                <option>{{ __('online_bookings.all') }}</option>
                <option value="Today">{{ __('online_bookings.today') }}</option>
                <option value="Yesterday">{{ __('online_bookings.yesterday') }}</option>
                <option value="This week">{{ __('online_bookings.this_week') }}</option>
                <option value="Last week">{{ __('online_bookings.last_week') }}</option>
                <option value="This Month">{{ __('online_bookings.this_month') }}</option>
                <option value="Last Month">{{ __('online_bookings.last_month') }}</option>
            </select>
        </div>
        <div class="col-md-3">
            <div class="filter-label">{{ __('online_bookings.start_date') }}</div>
            <input type="text" class="form-control start_date" placeholder="{{ __('online_bookings.start_date') }}">
        </div>
        <div class="col-md-3">
            <div class="filter-label">{{ __('online_bookings.end_date') }}</div>
            <input type="text" class="form-control end_date" placeholder="{{ __('online_bookings.end_date') }}">
        </div>
        <div class="col-md-3 text-right filter-actions">
            <button type="button" class="btn btn-default" onclick="clearFilters()">{{ __('online_bookings.clear') }}</button>
            <button type="button" id="customFilterBtn" class="btn btn-primary">{{ __('online_bookings.filter_bookings') }}</button>
        </div>
    </div>
@endsection

@section('table_headers')
    <th>{{ __('online_bookings.id') }}</th>
    <th>{{ __('online_bookings.booking_date') }}</th>
    <th>{{ __('online_bookings.patient') }}</th>
    <th>{{ __('online_bookings.phone_no') }}</th>
    <th>{{ __('online_bookings.email') }}</th>
    <th>{{ __('online_bookings.preferred_appointment_date') }}</th>
    <th>{{ __('online_bookings.preferred_appointment_time') }}</th>
    <th>{{ __('online_bookings.is_new_patient') }}</th>
    <th>{{ __('online_bookings.status') }}</th>
    <th>{{ __('online_bookings.action') }}</th>
@endsection

@section('modals')
    @include('online_bookings.preview_booking')
@endsection

@section('page_js')
    <script>
        window.OnlineBookingsConfig = {
            bookingsUrl: "{{ url('/online-bookings/') }}",
            locale: "{{ app()->getLocale() }}",
            translations: {
                'online_bookings': @json(__('online_bookings'))
            }
        };
    </script>
    <script src="{{ asset('include_js/online_bookings_index.js') }}?v={{ filemtime(public_path('include_js/online_bookings_index.js')) }}"></script>
@endsection
