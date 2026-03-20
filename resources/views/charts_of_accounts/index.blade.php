@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    @include('layouts.page_loader')
@endsection
<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <span class="caption-subject">{{ __('charts_of_accounts.title') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                <div class="table-toolbar">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="btn-group">
                                <button type="button" class="btn blue btn-outline sbold" onclick="createRecord()">{{ __('common.add_new') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
                @if(session()->has('success'))
                    <div class="alert alert-info">
                        <button class="close" data-dismiss="alert"></button> {{ session()->get('success') }}!
                    </div>
                @endif
                <div class="row">
                    <div class="tabbable-line col-md-12">
                        <ul class="nav nav-tabs">
                            @if(isset($AccountingEquations))
                                @foreach($AccountingEquations as $row)
                                    <li class="@if($row->active_tab) active @endif">
                                        <a href="#{{ $row->id . '_tab' }}" data-toggle="tab">{{ $row->name }}</a>
                                    </li>
                                @endforeach
                            @endif
                        </ul>
                        <div class="tab-content">
                            @if(isset($AccountingEquations))
                                @foreach($AccountingEquations as $row)
                                    <div class="tab-pane @if($row->active_tab) active @endif" id="{{ $row->id . '_tab' }}">
                                        @foreach($row->Categories as $cat)
                                            <div class="portlet">
                                                <div class="portlet-body">
                                                    <div class="mt-element-list">
                                                        <div class="mt-list-head list-default ext-1 bg-grey">
                                                            <div class="row">
                                                                <div class="col-xs-12">
                                                                    <div class="list-head-title-container">
                                                                        <h3 class="list-title">{{ $cat->name }}</h3>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="mt-list-container list-default ext-1">
                                                            <ul>
                                                                @foreach($cat->Items as $item)
                                                                    <li class="mt-list-item done">
                                                                        <div class="list-icon-container">
                                                                            <a href="javascript:;">
                                                                                <i class="icon-check"></i>
                                                                            </a>
                                                                        </div>
                                                                        <div class="list-datetime">
                                                                            <a href="javascript:;" onclick="editRecord('{{ $item->id }}')">{{ __('common.edit') }}</a>
                                                                        </div>
                                                                        <div class="list-item-content">
                                                                            <h3 class="uppercase">
                                                                                <a href="javascript:;">{{ $item->name }}</a>
                                                                            </h3>
                                                                            <p>{{ $item->description }}</p>
                                                                        </div>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            @endif
                        </div>
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
@include('charts_of_accounts.create')
@endsection
@section('js')
<script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
<script>
    LanguageManager.loadFromPHP(@json(__('charts_of_accounts')), 'charts_of_accounts');
    window.ChartsOfAccountsConfig = {
        lang: @json(__('charts_of_accounts')),
        langCommon: @json(__('common')),
        storeUrl: "{{ url('/charts-of-accounts-items') }}",
        editUrl: "{{ url('/charts-of-accounts-items') }}"
    };
</script>
<script src="{{ asset('include_js/charts_of_accounts_index.js') }}?v={{ filemtime(public_path('include_js/charts_of_accounts_index.js')) }}" type="text/javascript"></script>
@endsection
