<!DOCTYPE html>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<meta http-equiv="content-type" content="text/html;charset=UTF-8"/>
<head>
    <meta charset="utf-8"/>
    <title>{{ config('app.name', 'Laravel') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @yield('head')
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport"/>
    <meta content="clinic management system" name="description"/>
    <meta content="" name="author"/>
    <link href="{{ asset('backend/assets/global/plugins/font-awesome/css/fontawesome.min.css') }}" rel="stylesheet"
          type="text/css"/>
    <link href="{{ asset('backend/assets/global/plugins/simple-line-icons/simple-line-icons.min.css') }}"
          rel="stylesheet" type="text/css"/>
    <link href="{{ asset('backend/assets/global/plugins/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet"
          type="text/css"/>
    <link href="{{ asset('backend/assets/global/plugins/bootstrap-switch/css/bootstrap-switch.min.css') }}"
          rel="stylesheet"
          type="text/css"/>
    <link href="{{ asset('backend/assets/pages/css/select2.min.css') }}" rel="stylesheet" type="text/css"/>

    <link href="{{ asset('backend/assets/global/plugins/bootstrap-sweetalert/sweetalert.css') }}" rel="stylesheet"
          type="text/css"/>

    <link href="{{ asset('backend/assets/global/plugins/bootstrap-daterangepicker/daterangepicker.min.css') }}"
          rel="stylesheet"
          type="text/css"/>
    <link href="{{ asset('backend/assets/global/plugins/morris/morris.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('backend/assets/global/plugins/fullcalendar/fullcalendar.min.css') }}" rel="stylesheet"
          type="text/css"/>
    <link href="{{ asset('backend/assets/global/plugins/jqvmap/jqvmap/jqvmap.css') }}" rel="stylesheet"
          type="text/css"/>
    <link href="{{ asset('backend/assets/global/css/components.min.css') }}" rel="stylesheet" id="style_components"
          type="text/css"/>
    <link href="{{ asset('backend/assets/global/css/plugins.min.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('backend/assets/layouts/layout4/css/layout.min.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('backend/assets/layouts/layout4/css/themes/default.min.css') }}" rel="stylesheet"
          type="text/css"
          id="style_color"/>
    <link href="{{ asset('backend/assets/layouts/layout4/css/custom.min.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('backend/assets/global/plugins/datatables/datatables.min.css') }}" rel="stylesheet"
          type="text/css"/>
    <link href="{{ asset('backend/assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.css') }}"
          rel="stylesheet" type="text/css"/>
    <link href="{{ asset('backend/assets/pages/css/profile.min.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('backend/assets/global/plugins/bootstrap-fileinput/bootstrap-fileinput.css') }}"
          rel="stylesheet" type="text/css"/>
    <link href="{{ asset('backend/assets/global/plugins/bootstrap-toastr/toastr.min.css') }}" rel="stylesheet"
          type="text/css"/>

    <link href="{{ asset('backend/assets/global/css/bootstrap-datepicker.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('backend/assets/global/css/clockface.css') }}"
          rel="stylesheet" type="text/css"/>
    <link rel="stylesheet"
          href="{{ asset('backend/assets/global/css/magnific-popup.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/assets/global/css/jquery.fancybox.min.css') }}" media="screen">
    <link rel="stylesheet" href="{{ asset('backend/assets/global/css/intlTelInput.css') }}" media="screen">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}"/>

    {{-- Purple Theme --}}
    <link href="{{ asset('css/theme-purple.css') }}" rel="stylesheet" type="text/css"/>

    @yield('css')
</head>

<body class="page-header-fixed page-sidebar-fixed page-sidebar-closed-hide-logo page-content-white">
    <script>if(document.cookie.indexOf('sidebar_closed=1')!==-1){document.body.classList.add('page-sidebar-closed')}</script>
    {{-- Top Bar --}}
    @include('partials.topbar')

    <div class="clearfix"></div>

    {{-- Sidebar + Content Container --}}
    <div class="page-container">
        {{-- Sidebar --}}
        @include('partials.sidebar-doctor')

        {{-- Content --}}
        <div class="page-content-wrapper">
            <div class="page-content">
                {{-- Breadcrumb --}}
                <div class="page-head">
                    <div class="container-fluid">
                        <ul class="page-breadcrumb">
                            <li class="home-icon"><a href="{{ url('home') }}"><i class="icon-home"></i></a></li>
                            <li class="separator"></li>
                            @if(isset($breadcrumb_parent))
                                <li><a href="{{ $breadcrumb_parent_url ?? '#' }}">{{ $breadcrumb_parent }}</a></li>
                                <li class="separator"></li>
                            @endif
                            @if(isset($breadcrumb_current))
                                <li class="current">{{ $breadcrumb_current }}</li>
                            @else
                                <li class="current">{{ __('menu.dashboard') }}</li>
                            @endif
                        </ul>
                    </div>
                </div>
                {{-- Main Content --}}
                <div class="page-content-inner">
                    @if(isset($breadcrum)) {!! $breadcrum !!} @endif
                    <div class="mt-content-body">
                        @yield('content')
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    @include('partials.footer')

    {{-- Scripts --}}
    <script src="{{ asset('backend/assets/global/plugins/jquery.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('backend/assets/global/plugins/bootstrap/js/bootstrap.min.js') }}"
            type="text/javascript"></script>
    <script src="{{ asset('backend/assets/global/plugins/js.cookie.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('backend/assets/global/plugins/jquery-slimscroll/jquery.slimscroll.min.js') }}"
            type="text/javascript"></script>
    <script src="{{ asset('backend/assets/global/plugins/jquery.blockui.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('backend/assets/global/plugins/bootstrap-switch/js/bootstrap-switch.min.js') }}"
            type="text/javascript"></script>
    <script src="{{ asset('backend/assets/global/plugins/bootstrap-sweetalert/sweetalert.min.js') }}"
            type="text/javascript"></script>
    <script src="{{ asset('backend/assets/global/plugins/moment.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('backend/assets/global/plugins/bootstrap-daterangepicker/daterangepicker.min.js') }}"
            type="text/javascript"></script>
    <script src="{{ asset('backend/assets/global/plugins/fullcalendar/fullcalendar.min.js') }}"
            type="text/javascript"></script>
    @if(app()->getLocale() == 'zh-CN')
    <script src="{{ asset('backend/assets/global/plugins/fullcalendar/lang/zh-cn.js') }}" type="text/javascript"></script>
    @endif
    <script src="{{ asset('backend/assets/global/scripts/app.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('backend/assets/global/plugins/datatables/datatables.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('backend/assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.js') }}"
            type="text/javascript"></script>
    <script src="{{ asset('backend/assets/layouts/layout4/scripts/layout.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('backend/assets/layouts/layout4/scripts/demo.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('backend/assets/layouts/global/scripts/quick-sidebar.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('backend/assets/layouts/global/scripts/quick-nav.min.js') }}" type="text/javascript"></script>

    <script src="{{ asset('backend/assets/global/plugins/clockface.js') }}"
            type="text/javascript"></script>
    <script src="{{ asset('backend/assets/global/plugins/bootstrap-toastr/toastr.min.js') }}"
            type="text/javascript"></script>
    <script src="{{ asset('backend/assets/pages/scripts/ui-toastr.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('backend/assets/global/plugins/bootstrap-fileinput/bootstrap-fileinput.js') }}"
            type="text/javascript"></script>

    <script src="{{ asset('backend/assets/global/plugins/bootstrap-datepicker.js') }}" type="text/javascript"></script>
    <script src="{{ asset('backend/assets/global/plugins/bootstrap-datepicker/js/locales/bootstrap-datepicker.' . app()->getLocale() . '.js') }}" type="text/javascript"></script>

    <script src="{{ asset('backend/assets/pages/scripts/select2.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('backend/assets/global/plugins/select2/js/i18n/' . app()->getLocale() . '.js') }}" type="text/javascript"></script>

    <script type="text/javascript"
            src="{{ asset('backend/assets/global/plugins/jquery.magnific-popup.js') }}"></script>
    <script type="text/javascript"
            src="{{ asset('backend/assets/pages/scripts/bootstrap3-typeahead.min.js') }}"></script>
    {{-- dashboard staticts charts library--}}
    <script src="{{ asset('backend/assets/global/scripts/Chart.min.js') }}" charset="utf-8"></script>

    <script src="{{ asset('backend/assets/global/scripts/jquery.fancybox.min.js') }}"></script>
    <script src="{{ asset('backend/assets/global/scripts/intlTelInput.min.js') }}"></script>
    <script src="{{ asset('backend/assets/global/scripts/loadingoverlay.js') }}"></script>
    <script src="{{ asset('backend/assets/global/scripts/loadingoverlay.min.js') }}"></script>

    {{-- Language Manager for i18n --}}
    <script src="{{ asset('js/i18n/language-manager.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/i18n/lang-' . app()->getLocale() . '.js') }}" type="text/javascript"></script>

    <script type="text/javascript">
        // Global CSRF token setup for all AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Initialize Language Manager globally
        LanguageManager.init({
            availableLocales: @json(config('app.available_locales')),
            currentLocale: '{{ app()->getLocale() }}',
            defaultLocale: '{{ config('app.fallback_locale', 'en') }}'
        });

        // Load common translations used across all pages
        LanguageManager.loadAllFromPHP({
            'common': @json(__('common')),
            'validation': @json(__('validation'))
        });

        // Set Select2 default language globally
        if (typeof $.fn.select2 !== 'undefined') {
            $.fn.select2.defaults.set('language', '{{ app()->getLocale() }}');
        }

        // Set Datepicker default language globally
        if (typeof $.fn.datepicker !== 'undefined') {
            $.fn.datepicker.defaults.language = '{{ app()->getLocale() }}';
        }

        // Restore sidebar collapsed state from cookie
        if (typeof Cookies !== 'undefined' && Cookies.get('sidebar_closed') === '1') {
            $('body').addClass('page-sidebar-closed');
            $('.page-sidebar-menu').addClass('page-sidebar-menu-closed');
        }

        $(document).ready(function () {
            $.LoadingOverlay("show"); // Show full page LoadingOverlay

            $(window).load(function (e) {
                $.LoadingOverlay("hide"); // after page loading hide the progress bar
            });

            // Global handler: Clear validation messages when any modal is closed
            $(document).on('hidden.bs.modal', '.modal', function () {
                var $modal = $(this);
                $modal.find('.alert-danger').hide().find('ul').empty();
                $modal.find('.alert-danger p').remove();
            });

        });
        $('#datepicker').datepicker({
            autoclose: true,
            todayHighlight: true,
        });


        $('#datepicker2').datepicker({
            autoclose: true,
            todayHighlight: true,
        });

        $('.start_date').datepicker({
            autoclose: true,
            todayHighlight: true,
        });

        $('.end_date').datepicker({
            autoclose: true,
            todayHighlight: true,
        });


        function formatAMPM(date) {
            var hours = date.getHours();
            var minutes = date.getMinutes();
            var ampm = hours >= 12 ? 'pm' : 'am';
            hours = hours % 12;
            hours = hours ? hours : 12; // the hour '0' should be '12'
            minutes = minutes < 10 ? '0' + minutes : minutes;
            var strTime = hours + ':' + minutes + '' + ampm;
            return strTime;
        }

        let time_plus_6 = new Date(new Date().getTime());

        $('#start_time').clockface();

        $('#appointment_time').clockface();


        $('#monthsOnly').datepicker({
            autoclose: true,
            format: 'yyyy-mm',
            todayHighlight: true,
        });

    </script>
    {{-- Template Picker for Medical Templates --}}
    <script src="{{ asset('include_js/template_picker.js') }}" type="text/javascript"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            // Initialize Template Picker
            if (typeof TemplatePicker !== 'undefined') {
                TemplatePicker.init({ baseUrl: '{{ url('/') }}' });
            }
            // Initialize Quick Phrase Picker
            if (typeof QuickPhrasePicker !== 'undefined') {
                QuickPhrasePicker.init({ baseUrl: '{{ url('/') }}' });
            }
        });
    </script>
    @yield('js')
    <script src="{{ asset('js/breadcrumb-auto.js') }}"></script>
</body>


</html>
