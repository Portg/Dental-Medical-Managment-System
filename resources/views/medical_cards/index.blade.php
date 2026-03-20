@extends('layouts.list-page')

@section('page_title', __('medical_cards.cards'))
@section('table_id', 'sample_1')

@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createRecord()">{{ __('common.add_new') }}</button>
@endsection

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('common.date') }}</th>
    <th>{{ __('medical_cards.patient') }}</th>
    <th>{{ __('medical_cards.card_type') }}</th>
    <th>{{ __('medical_cards.added_by') }}</th>
    <th>{{ __('medical_cards.view_cards') }}</th>
    <th>{{ __('common.delete') }}</th>
    <th><button type="button" name="bulk_delete" id="bulk_delete" class="btn btn-danger btn-xs"><i class="glyphicon glyphicon-remove"></i></button></th>
@endsection

@section('modals')
    @include('medical_cards.create')
@endsection

@section('page_css')
    <link rel="stylesheet" href="{{ asset('css/medical-cards.css') }}">
@endsection

@section('page_js')
    <script>
        window.MedicalCardsConfig = {
            medicalCardsUrl: "{{ url('medical-cards') }}",
            massRemoveUrl: "{{ url('ajaxdata.massremove') }}",
            locale: "{{ app()->getLocale() }}",
            translations: {
                'patient': @json(__('patient')),
                'medical_cards': @json(__('medical_cards'))
            }
        };
    </script>
    <script src="{{ asset('include_js/medical_cards_index.js') }}?v={{ filemtime(public_path('include_js/medical_cards_index.js')) }}"></script>
@endsection
