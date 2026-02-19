@extends(\App\Http\Helper\FunctionsHelper::navigation())

@section('content')

    <div class="note note-success">
        <p class="text-black-50"><a href="{{ url('profile') }}" class="text-primary">{{ __('dashboard.my_profile') }}</a>
            / {{ Auth::User()->full_name }} <span class="text-primary">[ {{  Auth::User()->UserRole->name }}  ]</span>
        </p>
    </div>
    <div class="row">
        <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
            <a class="dashboard-stat dashboard-stat-v2 blue" href="#">
                <div class="visual">
                    <i class="fa fa-calendar-check-o"></i>
                </div>
                <div class="details">
                    <div class="number">
                        <span data-counter="counterup" data-value="{{ $appointments }}">{{ $appointments }}</span>
                    </div>
                    <div class="desc"> {{ __('dashboard.today_appointments') }}</div>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
            <a class="dashboard-stat dashboard-stat-v2 green" href="#">
                <div class="visual">
                    <i class="fa fa-clock-o"></i>
                </div>
                <div class="details">
                    <div class="number">
                        <span data-counter="counterup"
                              data-value="{{ $pending_appointments }}">{{ $pending_appointments }}</span>
                    </div>
                    <div class="desc"> {{ __('dashboard.pending_appointments') }}</div>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
            <a class="dashboard-stat dashboard-stat-v2 red" href="#">
                <div class="visual">
                    <i class="fa fa-user-plus"></i>
                </div>
                <div class="details">
                    <div class="number">
                        <span data-counter="counterup" data-value="{{ $new_patients }}">{{ $new_patients }}</span>
                    </div>
                    <div class="desc"> {{ __('dashboard.today_new_patients') }}</div>
                </div>
            </a>
        </div>

    </div>
    <div class="clearfix"></div>
    <div class="row">
        <div class="col-lg-6 col-xs-12 col-sm-12">
            <div class="portlet light bordered">
                <div class="portlet-title">
                    <div class="caption">
                        <i class="icon-bar-chart font-dark hide"></i>
                        <span class="caption-subject font-dark bold uppercase">{{ __('dashboard.monthly_appointments_trend') }}</span>
                    </div>
                    <div class="actions">

                    </div>
                </div>
                <div class="portlet-body">
                    {!! $monthly_appointments->container() !!}
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-xs-12 col-sm-12">
            <div class="portlet light bordered">
                <div class="portlet-title">
                    <div class="caption">
                        <i class="icon-share font-red-sunglo hide"></i>
                        <span
                            class="caption-subject font-dark bold uppercase">{{ __('dashboard.appointment_classification') }}</span>

                    </div>
                    <div class="actions">

                    </div>
                </div>
                <div class="portlet-body">
                    {!! $monthly_appointments_classification->container() !!}
                </div>
            </div>
        </div>
    </div>

    {!! $monthly_appointments->script() !!}
    {!! $monthly_appointments_classification->script() !!}
@endsection
