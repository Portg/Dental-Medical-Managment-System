@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    @include('layouts.page_loader')
@endsection

<div class="note note-success">
    <p class="text-black-50"><a href="{{ url('doctor-claims')}}" class="text-primary">{{ __('doctor_claim.payments.view_claims') }}
        </a> / @if(isset($doctor)) {{ $doctor->full_name }} ) @endif
    </p>
</div>

<input type="hidden" value="{{ $claim_id }}" id="global_claim_id">
<div class="row">

    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption">
                    <span class="caption-subject bold uppercase">{{ __('doctor_claim.payments.title') }}</span>
                    &nbsp; &nbsp; &nbsp;

                </div>
            </div>
            <div class="portlet-body">
                <table class="table table-hover" id="sample_1">
                    <thead>
                    <tr>
                        <th>{{ __('doctor_claim.payments.hash') }}</th>
                        <th>{{ __('doctor_claim.payments.payment_date') }}</th>
                        <th>{{ __('doctor_claim.payments.amount') }}</th>
                        <th>{{ __('common.edit') }}</th>
                        <th>{{ __('common.delete') }}</th>
                    </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>

            </div>
        </div>
    </div>
</div>

<div class="loading">
    <i class="fa fa-refresh fa-spin fa-2x fa-fw"></i><br/>
    <span>{{ __('common.loading') }}</span>
</div>
@include('doctor_claims.payments.create')
@endsection

@section('js')
    <script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
    <script>
        LanguageManager.loadFromPHP(@json(__('doctor_claims')), 'doctor_claim');
    </script>
    <script src="{{ asset('include_js/doctor_claim_payments.js') }}?v={{ filemtime(public_path('include_js/doctor_claim_payments.js')) }}" type="text/javascript"></script>
@endsection





