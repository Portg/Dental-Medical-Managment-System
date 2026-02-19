@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    @include('layouts.page_loader')
@endsection

<style type="text/css">
    .gallery {
        display: inline-block;
        margin-top: 20px;
    }
</style>
<div class="note note-success">
    <p class="text-black-50"><a href="{{ url('doctor-appointments')}}" class="text-primary">{{ __('medical_history.view_appointments') }}
        </a> / {{ __('medical_history.page_title') }}
        / @if(isset($patient)) {{ $patient->full_name  }}  @endif </p>
</div>
<input type="hidden" id="global_patient_id" value="{{ $patient->id }}">
<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption">
                    <span class="caption-subject bold uppercase">{{ __('medical_history.treatment_history') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                <table class="table table-striped table-bordered table-hover table-checkable order-column"
                       id="sample_10">
                    <thead>
                    <tr>
                        <th> #</th>
                        <th>{{ __('medical_history.date') }}</th>
                        <th>{{ __('medical_history.clinical_notes') }}</th>
                        <th>{{ __('medical_history.treatment') }}</th>
                        <th>{{ __('medical_history.doctor') }}</th>
                    </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>

            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption">
                    <span class="caption-subject bold uppercase">{{ __('medical_history.surgical_history') }}</span>
                    <button type="button" class="btn  blue btn-outline btn-circle btn-sm"
                       onclick="AddSurgery({{ $patient->id  }})">
                        {{ __('medical_history.add_surgery') }}
                    </button>
                </div>
            </div>
            <div class="portlet-body">
                <table class="table table-striped table-bordered table-hover table-checkable order-column"
                       id="sample_2">
                    <thead>
                    <tr>
                        <th> #</th>
                        <th>{{ __('medical_history.surgery') }}</th>
                        <th>{{ __('medical_history.surgery_date') }}</th>
                        <th>{{ __('medical_history.notes') }}</th>
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
    <div class="col-md-12">
        <div class="portlet light portlet-fit bordered">
            <div class="portlet-title">
                <div class="caption">
                    <span class="caption-subject font-dark bold uppercase">{{ __('medical_history.chronic_diseases') }}</span>
                    <button type="button" class="btn btn-default btn-circle btn-sm"
                       onclick="AddIllness({{ $patient->id  }})">
                        {{ __('medical_history.add_illness') }}
                    </button>
                </div>
            </div>
            <div class="portlet-body">
                <table class="table table-striped table-bordered table-hover table-checkable order-column"
                       id="sample_3">
                    <thead>
                    <tr>

                        <th> #</th>
                        <th>{{ __('medical_history.illness') }}</th>
                        <th>{{ __('medical_history.status') }}</th>
                        <th>{{ __('common.edit') }}</th>
                        <th>{{ __('common.delete') }}</th>
                    </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>
        <!-- END BORDERED TABLE PORTLET-->
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption">
                    <span class="caption-subject bold uppercase">{{ __('medical_history.drug_allergies') }}</span>
                    <button type="button" class="btn  blue btn-outline btn-circle btn-sm"
                       onclick="AddAllergy({{ $patient->id  }})">
                        {{ __('medical_history.add_allergies') }}
                    </button>
                </div>
            </div>
            <div class="portlet-body">

                <table class="table table-striped table-bordered table-hover table-checkable order-column"
                       id="sample_4">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('medical_history.drug') }}</th>
                        <th>{{ __('medical_history.reaction') }}</th>
                        <th>{{ __('medical_history.status') }}</th>
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
    <div class="col-md-12">
        <div class="portlet light portlet-fit bordered">
            <div class="portlet-title">
                <div class="caption">
                    <span class="caption-subject font-dark bold uppercase">{{ __('medical_history.medical_cards') }}</span>
                </div>
                <div class="actions">
                    <div class="btn-group">

                    </div>
                </div>
            </div>
            <div class="portlet-body">

                <div class="row">
                    <div class='list-group gallery'>
                        @if($medical_cards->count())
                            @foreach($medical_cards as $image)
                                <div class='col-sm-4 col-xs-6 col-md-3 col-lg-6'>
                                    <a class="thumbnail fancybox" rel="ligthbox"
                                       href="/uploads/medical_cards/{{ $image->card_photo }}">
                                        <img class="img-responsive" alt=""
                                             src="/uploads/medical_cards/{{ $image->card_photo }}"/>
                                        <div class='text-center'>
                                            {{--                                            <small class='text-muted'>{{ $image->title }}</small>--}}
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        @endif
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
@include('medical_history.surgery.create')
@include('medical_history.chronic_diseases.create')
@include('medical_history.allergies.create')
@endsection
@section('js')
    <script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
    <script src="{{ asset('include_js/treatment_history.js') }}"></script>
    <script src="{{ asset('include_js/surgery.js') }}"></script>
    <script src="{{ asset('include_js/chronic_diseases.js') }}"></script>
    <script src="{{ asset('include_js/allergies.js') }}"></script>
    <script type="text/javascript">
        $(document).ready(function () {
            $(".fancybox").fancybox({
                openEffect: "none",
                closeEffect: "none"
            });
        });
    </script>
@endsection





