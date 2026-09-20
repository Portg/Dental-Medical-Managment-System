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
    <meta name="theme-color" content="#00838F"/>
    <meta content="clinic management system" name="description"/>
    <meta content="" name="author"/>
    {{-- Backend CSS Bundle (compiled via npm run dev/prod) --}}
    <link href="{{ asset('css/backend-bundle.css') }}" rel="stylesheet" type="text/css"/>
    {{-- Self-hosted Noto Sans SC. OFL.txt is shipped with the font assets. --}}
    <link href="{{ asset('fonts/noto-sans-sc/font-face.css') }}?v={{ filemtime(public_path('fonts/noto-sans-sc/font-face.css')) }}" rel="stylesheet" type="text/css"/>
    {{-- Icon fonts stay at their original paths so relative font URLs resolve correctly. --}}
    <link href="{{ asset('backend/assets/global/plugins/simple-line-icons/simple-line-icons.min.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('backend/assets/global/plugins/font-awesome/css/all.min.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('backend/assets/global/plugins/font-awesome/css/v4-shims.min.css') }}" rel="stylesheet" type="text/css"/>
    {{--
        select2 的 bootstrap 主题。app.min.js（Metronic 核心，每页都加载）里写死了
        $.fn.select2.defaults.set("theme","bootstrap")，而 backend-bundle 里只打包了
        Metronic 对该主题的几条覆盖（字体、阴影），没有画边框和底色的主题本体。
        结果是：凡在 $(document).ready 里初始化的 select2（那时 app.min.js 已经把默认
        主题改成 bootstrap 了）全都渲染成透明无边框的一块 —— 标签下面看着空无一物。
        在 script 之前把主题本体补上，比逐处传 theme:'default' 靠谱。
    --}}
    <link href="{{ asset('backend/assets/global/plugins/select2/css/select2-bootstrap.min.css') }}" rel="stylesheet" type="text/css"/>
    {{-- 日期+时间组合控件（partials/datetime_picker.blade.php），替代原生 datetime-local --}}
    <link href="{{ asset('css/datetime-picker.css') }}?v={{ filemtime(public_path('css/datetime-picker.css')) }}" rel="stylesheet" type="text/css"/>
    {{-- 表格「操作」下拉按钮：撤销 Metronic 把 .btn-group 绝对定位的 hack，
         必须排在 backend-bundle.css 之后才能覆盖掉它 --}}
    <link href="{{ asset('css/table-actions.css') }}?v={{ filemtime(public_path('css/table-actions.css')) }}" rel="stylesheet" type="text/css"/>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}"/>
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}"/>

    {{-- Purple Theme --}}
    <link href="{{ asset('css/theme-purple.css') }}" rel="stylesheet" type="text/css"/>

    @yield('css')
    {{-- Load last so page and vendor CSS cannot reintroduce Open Sans/Helvetica. --}}
    <link href="{{ asset('css/typography.css') }}?v={{ filemtime(public_path('css/typography.css')) }}" rel="stylesheet" type="text/css"/>
</head>

<body class="page-header-fixed page-sidebar-fixed page-sidebar-closed-hide-logo page-content-white">
    <script>if(document.cookie.indexOf('sidebar_closed=1')!==-1){document.body.classList.add('page-sidebar-closed')}</script>
    {{-- Top Bar --}}
    @include('partials.topbar')

    <div class="clearfix"></div>

    {{-- Sidebar + Content Container --}}
    <div class="page-container">
        {{-- Sidebar --}}
        @include('partials.sidebar-dynamic')

        {{-- Content --}}
        <div class="page-content-wrapper">
            <div class="page-content">
                @php
                    $breadcrumbCurrentLabel = isset($breadcrumb_current)
                        ? trim(strip_tags((string) $breadcrumb_current))
                        : trim(strip_tags($__env->yieldContent('page_title')));
                @endphp
                {{--
                    面包屑先由服务端提供页面级标题，再由 breadcrumb-auto.js 补齐侧栏层级。
                    data-* 是新增/编辑/详情页的契约；否则前端只能把 /resource/create
                    当作 /resource 列表页，顶部永远缺少「添加」「编辑」这一层。
                --}}
                <div class="page-head" style="visibility:hidden"
                     data-breadcrumb-current="{{ $breadcrumbCurrentLabel }}"
                     data-breadcrumb-add="{{ __('common.add') }}"
                     data-breadcrumb-edit="{{ __('common.edit') }}"
                     data-breadcrumb-details="{{ __('common.details') }}">
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
                            @elseif (!empty(trim($__env->yieldContent('page_title'))))
                                <li class="current">@yield('page_title')</li>
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
    {{-- 必须排在 clockface 与 datepicker 之后：它要拿这两个插件初始化组合控件 --}}
    <script src="{{ asset('include_js/datetime_picker.js') }}?v={{ filemtime(public_path('include_js/datetime_picker.js')) }}" type="text/javascript"></script>
    {{-- 表格「操作」下拉菜单展开时改用 fixed 定位，绕开 .table-scrollable 的裁剪 --}}
    <script src="{{ asset('include_js/table_dropdown.js') }}?v={{ filemtime(public_path('include_js/table_dropdown.js')) }}" type="text/javascript"></script>

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
    <script src="{{ asset('js/i18n/language-manager.js') }}?v={{ filemtime(public_path('js/i18n/language-manager.js')) }}" type="text/javascript"></script>
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
            defaultLocale: '{{ config('app.locale', 'zh-CN') }}'
        });

        // Load common translations used across all pages
        LanguageManager.loadAllFromPHP({
            'common': @json(__('common')),
            'validation': @json(__('validation')),
            'patient': @json(__('patient'))
        });

        // Inject clinic settings for JS consumption
        window._clinicSettings = @json(\App\SystemSetting::getGroup('clinic'));

        // 服务端时间基准（应用时区：{{ config('app.timezone') }}），取自页面渲染时刻。
        // 前端填充日期/时间控件一律用它，不要 new Date() 自行推算：客户端时区与
        // 时钟未必和诊所一致，且 toISOString() 会把本地时间转成 UTC —— 在东八区
        // 会让「今天」在早上 8 点前变成昨天、datetime 控件恒早 8 小时。
        // datetime 已按 datetime-local 控件所需的 YYYY-MM-DDTHH:MM 格式给出。
        {{-- 数组先在 PHP 块中组装：json 指令按括号配对解析参数，多行数组字面量会让它误判 --}}
        @php
            $serverNow = [
                'date'     => now()->format('Y-m-d'),
                'datetime' => now()->format('Y-m-d\TH:i'),
                'timezone' => config('app.timezone'),
            ];
        @endphp
        window._serverNow = @json($serverNow);

        // Set Select2 default language globally
        if (typeof $.fn.select2 !== 'undefined') {
            $.fn.select2.defaults.set('language', '{{ app()->getLocale() }}');
        }

        // Set Datepicker default language globally
        if (typeof $.fn.datepicker !== 'undefined') {
            $.fn.datepicker.defaults.language = '{{ app()->getLocale() }}';
        }

        /*
         * sweetalert 的按钮默认文案是英文「OK」「Cancel」，弹窗正文是中文、按钮是英文。
         * 全站 74 个文件调 swal()，其中只有部分显式传了 confirmButtonText —— 剩下的
         * 一律走默认值。在这里设一次全局默认，比逐个调用点补参数靠谱。
         * 显式传了文案的调用不受影响（setDefaults 只填未指定的项）。
         */
        if (typeof swal !== 'undefined' && typeof swal.setDefaults === 'function') {
            swal.setDefaults({
                confirmButtonText: @json(__('common.ok')),
                cancelButtonText: @json(__('common.cancel')),
            });
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

        // 建档日期（新建患者）：补录老患者时要能往回选，所以不限制最大日期
        $('#registered_at_picker').datepicker({
            autoclose: true,
            todayHighlight: true,
            format: 'yyyy-mm-dd',
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

        /*
         * 全站日期输入统一走 bootstrap-datepicker（上面已按 app locale 载入中文语言包）。
         *
         * 原来有 46 个原生日期输入框。原生日期框的显示格式跟着**浏览器界面语言**走，
         * 不跟 <html lang> 也不跟系统区域设置走 —— 英文界面的 Chrome 上就是 mm/dd/yyyy，
         * 和同一个页面里 bootstrap-datepicker 渲染的 yyyy-mm-dd 并排出现，既不像中文习惯，
         * 也自相矛盾。改成 .js-date + 文本框，显示格式由我们自己定死。
         *
         * 用 .js-date 而不是复用 .datepicker：有 20 多个页面自己按 .datepicker 初始化并传了
         * startDate / endDate / format:'yyyy-mm' 等参数，而插件是「首次初始化的参数生效、
         * 后续调用直接忽略」（bootstrap-datepicker.js:1652 的 if (!data)）。这段脚本比页面
         * 脚本先执行，若复用同一个类名就会把那些页面的参数全部吃掉。
         *
         * format 保持 yyyy-mm-dd，与原生 type=date 的提交值一致，后端和读写 .val() 的
         * 既有 JS 都不用改。
         */
        $('input.js-date').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true,
            clearBtn: true,
            // 默认 'auto' 在弹窗里常判成往上弹，日历会盖住上面的表单区；
            // 'bottom auto' 优先往下，位置真不够时才翻上去
            orientation: 'bottom auto',
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
            {{-- 快捷短语浮层按权限初始化：QuickPhrasePicker.init() 内部会立即预取全部短语
                 （GET /quick-phrases-search，需 edit-patients），而本布局是全局布局，
                 此前每个页面都会发这个请求。库管不持有 edit-patients，于是每打开一个
                 库存/薪酬页面就吃一个 403。无此权限的角色本来也进不了诊断、治疗计划、
                 病程记录三个书写页，浮层对他们没有用武之地，不初始化不损失任何功能。
                 TemplatePicker 只绑定按键事件、不预取，故不需要同样处理。 --}}
            @can('edit-patients')
                // Initialize Quick Phrase Picker
                if (typeof QuickPhrasePicker !== 'undefined') {
                    QuickPhrasePicker.init({ baseUrl: '{{ url('/') }}' });
                }
            @endcan
        });
    </script>
    <script src="{{ asset('include_js/topbar_patient_search.js') }}?v={{ filemtime(public_path('include_js/topbar_patient_search.js')) }}"></script>
    @yield('js')
    <script src="{{ asset('js/breadcrumb-auto.js') }}?v={{ filemtime(public_path('js/breadcrumb-auto.js')) }}"></script>
</body>


</html>
