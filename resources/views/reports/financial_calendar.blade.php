@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
<link rel="stylesheet" href="{{ asset('css/financial-calendar.css') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
@endsection

<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <i class="icon-calendar"></i>
                    <span class="caption-subject">{{ __('report.financial_calendar') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                <div class="calendar-legend">
                    <div class="legend-item"><span class="legend-dot income"></span> {{ __('report.income') }}</div>
                    <div class="legend-item"><span class="legend-dot expense"></span> {{ __('report.expenditure') }}</div>
                    <div class="legend-item"><span class="legend-dot refund"></span> {{ __('report.refund_amount') }}</div>
                    <div class="legend-item"><span class="legend-dot net"></span> {{ __('report.net_amount') }}</div>
                </div>
                <div id="financialCalendar"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/zh-cn.js"></script>
<script type="text/javascript">
    LanguageManager.loadFromPHP(@json(__('report')), 'report');
    window.FinancialCalendarConfig = {
        locale:  '{{ app()->getLocale() === "zh-CN" ? "zh-cn" : "en" }}',
        dataUrl: '{{ url("financial-calendar-data") }}'
    };
</script>
<script src="{{ asset('include_js/financial_calendar.js') }}?v={{ filemtime(public_path('include_js/financial_calendar.js')) }}"></script>
@endsection
