{{-- Layout4 Top Header Bar - Shared across all roles --}}
<div class="page-header navbar navbar-fixed-top">
    <div class="page-header-inner">
        {{-- Logo --}}
        <div class="page-logo">
            <a href="{{ url('home') }}">
                <img src="{{ asset('images/logo.png') }}" alt="logo" class="logo-default">
            </a>
            <div class="menu-toggler sidebar-toggler">
                <span></span>
            </div>
        </div>
        {{-- Responsive Menu Toggler --}}
        <a href="javascript:;" class="menu-toggler responsive-toggler" data-toggle="collapse" data-target=".navbar-collapse">
            <span></span>
        </a>
        {{-- Top Navigation Menu --}}
        <div class="top-menu">
            <ul class="nav navbar-nav pull-right">
                {{-- Search --}}
                <li class="dropdown dropdown-extended dropdown-search">
                    <a href="javascript:;" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" data-close-others="true">
                        <i class="icon-magnifier"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-right">
                        <li class="header-search-form">
                            <form action="{{ url('patients') }}" method="GET">
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="{{ __('menu.search_placeholder') }}" autocomplete="off">
                                    <span class="input-group-btn">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="icon-magnifier"></i>
                                        </button>
                                    </span>
                                </div>
                            </form>
                        </li>
                    </ul>
                </li>
                {{-- Notifications --}}
                <li class="dropdown dropdown-extended dropdown-notification">
                    <a href="javascript:;" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" data-close-others="true">
                        <i class="icon-bell"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="external">
                            <h3>{{ __('menu.notifications') }}</h3>
                        </li>
                        <li>
                            <ul class="dropdown-menu-list scroller" style="height: 150px;" data-always-visible="1" data-rail-visible="1">
                                <li>
                                    <a href="{{ url('outbox-sms') }}">
                                        <i class="icon-envelope"></i> {{ __('menu.appointment_sms_reminders') }}
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ url('billing-notifications') }}">
                                        <i class="icon-paper-plane"></i> {{ __('menu.email_sent_invoice_quotations') }}
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </li>
                {{-- Language Switch --}}
                <li class="dropdown dropdown-extended dropdown-language">
                    <a href="javascript:;" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" data-close-others="true">
                        <i class="icon-globe"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-right">
                        <li><a href="{{ route('language.switch', 'en') }}"><i class="icon-flag"></i> English</a></li>
                        <li><a href="{{ route('language.switch', 'zh-CN') }}"><i class="icon-flag"></i> 中文</a></li>
                    </ul>
                </li>
                {{-- User Profile --}}
                <li class="dropdown dropdown-user">
                    <a href="javascript:;" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" data-close-others="true">
                        @if(\Illuminate\Support\Facades\Auth::User()->photo != null)
                            <img alt="" class="img-circle" src="{{ asset('uploads/users/'.\Illuminate\Support\Facades\Auth::User()->photo) }}">
                        @else
                            <img alt="" class="img-circle" src="{{ asset('backend/assets/pages/media/profile/profile_user.jpg') }}">
                        @endif
                        <span class="username username-hide-on-mobile">{{ Auth::User()->surname }}</span>
                        <i class="fa fa-angle-down"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-default">
                        <li>
                            <a href="{{ url('profile') }}">
                                <i class="icon-user"></i> {{ __('menu.my_profile') }}
                            </a>
                        </li>
                        @auth
                        <li class="divider"></li>
                        <li>
                            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="icon-logout"></i> {{ __('menu.logout') }}
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">{{ csrf_field() }}</form>
                        </li>
                        @endauth
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>
