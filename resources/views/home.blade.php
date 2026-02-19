@extends('layouts.app')

@section('content')
    <div class="note note-success hidden">
        <p class="text-black-50"><a href="{{ url('profile') }}" class="text-primary">{{ __('dashboard.my_profile') }}</a>
            / {{ Auth::User()->full_name }}
            <span class="text-primary">[ {{  Auth::User()->UserRole->name }}]</span>
        </p>
    </div>
    <div class="row">
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
            <a class="dashboard-stat dashboard-stat-v2 yellow" href="{{ url('todays-cash') }}">
                <div class="visual">
                    <i class="fa fa-money"></i>
                </div>
                <div class="details">
                    <div class="number">
                        <span data-counter="counterup"
                              data-value="{{ number_format($today_cash_amount) }}">{{ number_format($today_cash_amount) }}</span>
                    </div>
                    <div class="desc"> {{ __('dashboard.today_cash_amount') }}</div>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
            <a class="dashboard-stat dashboard-stat-v2 blue-chambray" href="{{ url('invoices') }}">
                <div class="visual">
                    <i class="fa fa-file-text-o"></i>
                </div>
                <div class="details">
                    <div class="number">
                        <span data-counter="counterup"
                              data-value="{{ number_format($pending_receivable_amount) }}">{{ number_format($pending_receivable_amount) }}</span>
                    </div>
                    <div class="desc"> {{ __('dashboard.pending_receivable_amount') }}</div>
                </div>
            </a>
        </div>

        <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
            <a class="dashboard-stat dashboard-stat-v2 green-seagreen" href="{{ url('todays-expenses') }}">
                <div class="visual">
                    <i class="fa fa-credit-card"></i>
                </div>
                <div class="details">
                    <div class="number">
                        <span data-counter="counterup"
                              data-value="{{ number_format($today_expense_amount) }}">{{ number_format($today_expense_amount) }}</span>
                    </div>
                    <div class="desc">{{ __('dashboard.today_expense_amount') }}</div>
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
                        <span class="caption-subject font-dark bold uppercase">{{ __('dashboard.monthly_revenue_trend') }}</span>
                    </div>
                </div>
                <div class="portlet-body">
                    {!! $monthlyCashFlows->container() !!}
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-xs-12 col-sm-12">
            <div class="portlet light bordered">
                <div class="portlet-title">
                    <div class="caption">
                        <i class="icon-share font-red-sunglo hide"></i>
                        <span class="caption-subject font-dark bold uppercase">{{ __('dashboard.monthly_expense_trend') }}</span>
                    </div>
                </div>
                <div class="portlet-body">
                    {!! $monthlyExpenses->container() !!}
                </div>
            </div>
        </div>
    </div>
    <div class="clearfix"></div>
    <div class="row">
        <div class="col-lg-6 col-xs-12 col-sm-12">
            <div class="portlet light bordered">
                <div class="portlet-title">
                    <div class="caption">
                        <i class="icon-bar-chart font-dark hide"></i>
                        <span class="caption-subject font-dark bold uppercase">{{ __('dashboard.income_by_payment_method') }}</span>
                    </div>
                </div>
                <div class="portlet-body">
                    {!! $monthlyOverRollIncome->container() !!}
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-xs-12 col-sm-12">
            <div class="portlet light bordered">
                <div class="portlet-title">
                    <div class="caption">
                        <i class="icon-share font-red-sunglo hide"></i>
                        <span class="caption-subject font-dark bold uppercase">{{ __('dashboard.income_vs_expense') }}</span>
                    </div>
                </div>
                <div class="portlet-body">
                    {!! $MonthlyOverRollIncomeExpense->container() !!}
                </div>
            </div>
        </div>
    </div>
    {!! $monthlyCashFlows->script() !!}
    {!! $monthlyExpenses->script() !!}
    {!! $monthlyOverRollIncome->script() !!}
    {!! $MonthlyOverRollIncomeExpense->script() !!}
@endsection
