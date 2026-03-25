@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/list-page.css') }}?v={{ filemtime(public_path('css/list-page.css')) }}">
<link rel="stylesheet" href="{{ asset('css/clinic_services.css') }}">
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-body">
                <div class="page-header-l1 service-page-header">
                    <div>
                        <h1 class="page-title">{{ __('menu.service_items') }}</h1>
                    </div>
                </div>

                <ul class="nav nav-tabs service-module-tabs" id="clinicServicesTabs" role="tablist">
                    <li class="active">
                        <a href="#tab-services" data-toggle="tab" role="tab">
                            {{ __('clinical_services.service_items') }}
                            <span class="module-tab-count" id="services-total-count">0</span>
                        </a>
                    </li>
                    <li>
                        <a href="#tab-packages" data-toggle="tab" role="tab">
                            {{ __('clinical_services.service_packages') }}
                            <span class="module-tab-count" id="packages-total-count">0</span>
                        </a>
                    </li>
                </ul>

                <div class="tab-content service-tab-content">
                    <div class="tab-pane active" id="tab-services">
                        @include('clinical_services._tab_services')
                    </div>
                    <div class="tab-pane" id="tab-packages">
                        @include('clinical_services._tab_packages')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('clinical_services._modal_service')
@include('clinical_services._modal_package')
@include('clinical_services._modal_import')
@include('clinical_services._modal_batch_price')
@endsection

@section('js')
<script>
LanguageManager.loadFromPHP(@json(__('clinical_services')), 'clinical_services');
</script>
<script src="{{ asset('include_js/clinic_services.js') }}?v={{ filemtime(public_path('include_js/clinic_services.js')) }}"></script>
@endsection
