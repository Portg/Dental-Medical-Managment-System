@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('title', __('system_settings.page_title'))

@section('css')
    @include('layouts.page_loader')
    <link rel="stylesheet" href="{{ asset('css/system-settings.css') }}">
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <i class="icon-settings font-dark"></i>
                    <span class="caption-subject bold uppercase">{{ __('system_settings.page_title') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                <ul class="nav nav-tabs" id="settingsTabs">
                    <li class="active">
                        <a href="#tab-clinic" data-toggle="tab">
                            <i class="fa fa-hospital-o"></i> {{ __('system_settings.tab_clinic') }}
                        </a>
                    </li>
                    <li>
                        <a href="#tab-member" data-toggle="tab">
                            <i class="fa fa-id-card"></i> {{ __('system_settings.tab_member') }}
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    {{-- Tab 1: Clinic Settings --}}
                    <div class="tab-pane active" id="tab-clinic">
                        @include('system_settings._clinic_tab')
                    </div>

                    {{-- Tab 2: Member Settings --}}
                    <div class="tab-pane" id="tab-member">
                        @include('system_settings._member_tab')
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
@endsection

@section('js')
<script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
<script>
    LanguageManager.loadAllFromPHP({
        'system_settings': @json(__('system_settings')),
        'messages': @json(__('messages'))
    });
</script>
<script src="{{ asset('include_js/system_settings.js') }}?v={{ filemtime(public_path('include_js/system_settings.js')) }}"></script>
@endsection
