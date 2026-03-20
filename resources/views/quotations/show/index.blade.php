@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    @include('layouts.page_loader')
@endsection
<div class="note note-success">
    <p class="text-black-50">
        <a href="{{ url('quotations')}}" class="text-primary">{{ __('quotations.go_back_to_quotations') }}</a> /
        @if(isset($patient))  {{ $patient->full_name  }} @endif
    </p>
</div>
<input type="hidden" value="{{ $quotation_id }}" id="global_quotation_id">
<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption">
                    <span class="caption-subject bold uppercase">{{ __('quotations.quotation') }}</span>
                    &nbsp; &nbsp; &nbsp

                </div>
            </div>
            <div class="portlet-body">
                <div class="table-toolbar">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="btn-group">
                                <button type="button" class="btn blue btn-outline sbold" onclick="createRecord()">{{ __('quotations.add_new') }}</button>
                            </div>
                        </div>
                        <div class="col-md-6">

                        </div>
                    </div>
                </div>
                <table class="table table-hover" id="quotation-items-table">
                    <thead>
                    <tr>
                        <th> {{ __('quotations.hash') }}</th>
                        <th>{{ __('quotations.procedure') }}</th>
                        <th>{{ __('quotations.tooth_no') }}</th>
                        <th>{{ __('quotations.qty') }}</th>
                        <th>{{ __('quotations.unit_price') }}</th>
                        <th>{{ __('quotations.total_amount') }}</th>
                        <th>{{ __('quotations.added_by') }}</th>
                        <th>{{ __('quotations.edit') }}</th>
                        <th>{{ __('quotations.delete') }}</th>
                    </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>

            </div>
        </div>
    </div>
</div>


@include('quotations.show.edit_quotation')
@endsection
@section('js')
    <script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
    <script type="text/javascript">
        LanguageManager.loadFromPHP(@json(__('quotations')), 'quotations');
        window.QuotationShowConfig = { locale: "{{ app()->getLocale() }}" };
    </script>
    <script src="{{ asset('include_js/quotations_show_index.js') }}?v={{ filemtime(public_path('include_js/quotations_show_index.js')) }}"></script>
@endsection





