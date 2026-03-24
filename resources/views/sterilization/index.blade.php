@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/list-page.css') }}?v={{ filemtime(public_path('css/list-page.css')) }}">
<link rel="stylesheet" href="{{ asset('css/form-modal.css') }}?v={{ filemtime(public_path('css/form-modal.css')) }}">
<link rel="stylesheet" href="{{ asset('css/sterilization.css') }}">
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered sterilization-page">
            <div class="portlet-body">
                <div class="page-header-l1">
                    <div>
                        <h1 class="page-title">{{ __('menu.sterilization_management') }}</h1>
                    </div>
                </div>

                <ul class="nav nav-tabs sterilization-tabs" id="sterilizationTabs" role="tablist">
                    <li class="active">
                        <a data-toggle="tab" href="#tab-records" role="tab">
                            {{ __('sterilization.records_tab') }}
                        </a>
                    </li>
                    <li>
                        <a data-toggle="tab" href="#tab-kits" role="tab">
                            {{ __('sterilization.kits_tab') }}
                        </a>
                    </li>
                </ul>

                <div class="tab-content sterilization-tab-content">
                    <div class="tab-pane active" id="tab-records" role="tabpanel">
                        @include('sterilization._tab_records')
                    </div>
                    <div class="tab-pane" id="tab-kits" role="tabpanel">
                        @include('sterilization._tab_kits')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('sterilization._modal_record')
@include('sterilization._modal_kit')
@include('sterilization._modal_use')
@endsection

@section('js')
<script>
LanguageManager.loadFromPHP(@json(__('sterilization')), 'sterilization');
const sterilizationKits = @json($kits);
</script>
<script src="{{ asset('include_js/sterilization.js') }}?v={{ filemtime(public_path('include_js/sterilization.js')) }}"></script>
@endsection
