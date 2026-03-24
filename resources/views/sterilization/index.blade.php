@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/sterilization.css') }}">
@endsection

@section('content')
<div class="page-content">
    <div class="page-header">
        <h3>{{ __('menu.sterilization_management') }}</h3>
    </div>

    <ul class="nav nav-tabs" id="sterilizationTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" data-toggle="tab" href="#tab-records" role="tab">
                {{ __('sterilization.records_tab') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#tab-kits" role="tab">
                {{ __('sterilization.kits_tab') }}
            </a>
        </li>
    </ul>

    <div class="tab-content mt-3">
        <div class="tab-pane fade show active" id="tab-records" role="tabpanel">
            @include('sterilization._tab_records')
        </div>
        <div class="tab-pane fade" id="tab-kits" role="tabpanel">
            @include('sterilization._tab_kits')
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
