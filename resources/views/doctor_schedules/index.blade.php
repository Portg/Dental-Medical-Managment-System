@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    @include('layouts.page_loader')
    <link rel="stylesheet" href="{{ asset('css/doctor-schedule-grid.css') }}">
@endsection
<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <i class="icon-calendar"></i>
                    <span class="caption-subject">{{ __('doctor_schedules.title') }}</span>
                </div>
                <div class="actions">
                    <button type="button" class="btn blue btn-outline sbold" onclick="createRecord()">{{ __('common.add_new') }}</button>
                </div>
            </div>
            <div class="portlet-body">
                <ul class="nav nav-tabs">
                    <li class="active">
                        <a href="#tab_list" data-toggle="tab">{{ __('doctor_schedules.list_view') }}</a>
                    </li>
                    <li>
                        <a href="#tab_calendar" data-toggle="tab">{{ __('doctor_schedules.calendar_view') }}</a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active" id="tab_list">
                        <div class="table-toolbar">
                            <div class="row">
                                <div class="col-md-4">
                                    <select id="filter_doctor" class="form-control">
                                        <option value="">{{ __('doctor_schedules.all_doctors') }}</option>
                                        @foreach($doctors as $doctor)
                                            <option value="{{ $doctor->id }}">{{ $doctor->full_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <table class="table table-striped table-bordered table-hover" id="schedules_table">
                            <thead>
                            <tr>
                                <th>{{ __('common.id') }}</th>
                                <th>{{ __('doctor_schedules.doctor') }}</th>
                                <th>{{ __('doctor_schedules.date') }}</th>
                                <th>{{ __('doctor_schedules.time_range') }}</th>
                                <th>{{ __('doctor_schedules.max_patients') }}</th>
                                <th>{{ __('doctor_schedules.recurring') }}</th>
                                <th>{{ __('doctor_schedules.branch') }}</th>
                                <th>{{ __('common.edit') }}</th>
                                <th>{{ __('common.delete') }}</th>
                            </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="tab-pane" id="tab_calendar">
                        <div id="schedule_calendar" class="schedule-calendar"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="loading">
    <i class="fa fa-refresh fa-spin fa-2x fa-fw"></i><br/>
    <span>{{ __('common.loading') }}</span>
</div>
@include('doctor_schedules.create')
@endsection
@section('js')
<script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
<script src="{{ asset('backend/assets/global/plugins/fullcalendar/fullcalendar.min.js') }}" type="text/javascript"></script>
@if(app()->getLocale() == 'zh-CN')
<script src="{{ asset('backend/assets/global/plugins/fullcalendar/lang/zh-cn.js') }}" type="text/javascript"></script>
@endif
<script>
LanguageManager.loadFromPHP(@json(__('doctor_schedules')), 'doctor_schedules');
window.DoctorSchedulesConfig = {
    locale: '{{ app()->getLocale() }}',
    urls: {
        data:     '{{ url('/doctor-schedules/') }}',
        calendar: '{{ url('/doctor-schedules/calendar') }}',
        base:     '{{ url('/doctor-schedules') }}'
    }
};
</script>
<script src="{{ asset('include_js/doctor_schedules_index.js') }}?v={{ filemtime(public_path('include_js/doctor_schedules_index.js')) }}"></script>
@endsection
