@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/clinic_services.css') }}">
@endsection

@section('content')
<div class="page-content">
    <div class="page-bar">
        <ul class="page-breadcrumb">
            <li>
                <a href="/">{{ __('menu.home') }}</a>
                <i class="fa fa-circle"></i>
            </li>
            <li>
                <span>{{ __('menu.service_items') }}</span>
            </li>
        </ul>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="portlet light bordered">
                <div class="portlet-body">
                    <ul class="nav nav-tabs" id="clinicServicesTabs" role="tablist">
                        <li class="active">
                            <a href="#tab-services" data-toggle="tab" role="tab">
                                {{ __('clinical_services.service_items') }}
                            </a>
                        </li>
                        <li>
                            <a href="#tab-packages" data-toggle="tab" role="tab">
                                {{ __('clinical_services.service_packages') }}
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content" style="margin-top: 15px;">
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
