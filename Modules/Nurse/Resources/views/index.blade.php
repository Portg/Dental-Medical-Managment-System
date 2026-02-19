@extends(\App\Http\Helper\FunctionsHelper::navigation())

@section('content')

    <div class="note note-success">
        <p class="text-black-50"><a href="{{ url('profile') }}" class="text-primary">{{ __('dashboard.my_profile') }}</a>
            / {{ Auth::User()->full_name }} <span class="text-primary">[ {{  Auth::User()->UserRole->name }}  ]</span>
        </p>
    </div>
    <div class="row">
        <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
            <a class="dashboard-stat dashboard-stat-v2 green" href="{{ url('waiting-queue') }}">
                <div class="visual">
                    <i class="fa fa-users"></i>
                </div>
                <div class="details">
                    <div class="number">
                        <span data-counter="counterup"
                              data-value="{{ $waiting_queue_count }}">{{ $waiting_queue_count }}</span>
                    </div>
                    <div class="desc"> {{ __('dashboard.waiting_queue') }}</div>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
            <a class="dashboard-stat dashboard-stat-v2 blue" href="{{ url('appointments') }}">
                <div class="visual">
                    <i class="fa fa-calendar-check-o"></i>
                </div>
                <div class="details">
                    <div class="number">
                        <span data-counter="counterup"
                              data-value="{{ $today_appointments }}">{{ $today_appointments }}</span>
                    </div>
                    <div class="desc"> {{ __('dashboard.today_appointments') }}</div>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
            <a class="dashboard-stat dashboard-stat-v2 red" href="#">
                <div class="visual">
                    <i class="fa fa-exclamation-triangle"></i>
                </div>
                <div class="details">
                    <div class="number">
                        <span data-counter="counterup"
                              data-value="{{ $overdue_followups }}">{{ $overdue_followups }}</span>
                    </div>
                    <div class="desc"> {{ __('dashboard.overdue_followups') }}</div>
                </div>
            </a>
        </div>
    </div>

@endsection
