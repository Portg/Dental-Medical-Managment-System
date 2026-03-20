@extends('layouts.list-page')

@section('page_title', __('templates.quick_phrases'))
@section('table_id', 'phrases_table')

@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createPhrase()">{{ __('common.add_new') }}</button>
@endsection

@section('filter_primary')
    <div class="col-md-3">
        <select id="filter_scope" class="form-control">
            <option value="">{{ __('templates.all_scopes') }}</option>
            <option value="system">{{ __('templates.system') }}</option>
            <option value="personal">{{ __('templates.personal') }}</option>
        </select>
    </div>
    <div class="col-md-3">
        <select id="filter_category" class="form-control">
            <option value="">{{ __('templates.all_categories') }}</option>
            <option value="examination">{{ __('templates.examination') }}</option>
            <option value="diagnosis">{{ __('templates.diagnosis') }}</option>
            <option value="treatment">{{ __('templates.treatment') }}</option>
            <option value="other">{{ __('templates.other') }}</option>
        </select>
    </div>
@endsection

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('templates.shortcut') }}</th>
    <th>{{ __('templates.phrase') }}</th>
    <th>{{ __('templates.category') }}</th>
    <th>{{ __('templates.scope') }}</th>
    <th>{{ __('common.status') }}</th>
    <th>{{ __('common.action') }}</th>
@endsection

@section('modals')
    @include('quick_phrases.create')
@endsection

@section('page_js')
<script type="text/javascript">
    LanguageManager.loadAllFromPHP({
        'templates': @json(__('templates'))
    });

    window.QuickPhrasesConfig = {
        baseUrl: "{{ url('/quick-phrases') }}",
        listUrl: "{{ url('/quick-phrases/') }}"
    };
</script>
<script src="{{ asset('include_js/quick_phrases_index.js') }}?v={{ filemtime(public_path('include_js/quick_phrases_index.js')) }}"></script>
@endsection
