const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/app.js', 'public/js')
   .sass('resources/sass/app.scss', 'public/css');

/*
 |--------------------------------------------------------------------------
 | Backend CSS Bundle
 |--------------------------------------------------------------------------
 |
 | Combines all core backend CSS files into a single bundle for better
 | performance (fewer HTTP requests).
 |
 | Run: npm run dev / npm run prod
 |
 */
mix.styles([
    // Core Framework
    'public/backend/assets/global/plugins/bootstrap/css/bootstrap.min.css',
    'public/backend/assets/global/plugins/bootstrap-switch/css/bootstrap-switch.min.css',

    // Icons —— 刻意**不**打进这个 bundle，别再加回来。
    //
    // 这两个图标库的 CSS 里字体是相对路径（simple-line-icons 写的是
    // url(fonts/…)，font-awesome 写的是 url(../webfonts/…)）。mix.styles 只做
    // 拼接、不重写 URL，产物又落在 public/css/ 下，于是相对路径被解析成
    // /css/fonts/… 和 /webfonts/… —— 全部 404，图标显示为空白方块。
    // 另外 bundle 里原来收的是 font-awesome/css/fontawesome.min.css，那份只有
    // 基础样式、根本不含 @font-face，所以全站 283 处 fa fa-* 一直没有字体可用。
    //
    // 现在改由 layouts/app.blade.php 从各自的原目录直接 <link>：
    //   simple-line-icons/simple-line-icons.min.css
    //   font-awesome/css/all.min.css      （含 @font-face，指向 ../webfonts/）
    //   font-awesome/css/v4-shims.min.css （视图里 283 处用的是 v4 的 fa fa-* 类名）
    // 相对路径以各自所在目录为基准，自然就对了。
    // 注意：仓库里已提交的 public/css/backend-bundle.css 是更早的产物，**仍然**
    // 含着这两份图标 CSS（包括那份路径已坏的 @font-face）。所以在有人重新跑
    // npm run prod 之前，layout 里那三行 <link> 必须排在 backend-bundle.css
    // **之后** —— 靠后定义的 @font-face 覆盖前面那份坏的。
    // （试过顺手重新生成 bundle：产物与已提交那份差了约 3600 个规则块，压缩器
    //   年代不同，属于要单独复核 UI 的改动，不该夹在图标修复里。）
    // tests/Unit/MenuIconAssetsTest.php 同时守着「这里不许再收」与「link 的顺序」。

    // UI Components
    'public/backend/assets/global/plugins/bootstrap-sweetalert/sweetalert.css',
    'public/backend/assets/global/plugins/bootstrap-daterangepicker/daterangepicker.min.css',
    'public/backend/assets/global/plugins/bootstrap-toastr/toastr.min.css',
    'public/backend/assets/global/plugins/bootstrap-fileinput/bootstrap-fileinput.css',

    // DataTables
    'public/backend/assets/global/plugins/datatables/datatables.min.css',
    'public/backend/assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.css',

    // Form Components
    'public/backend/assets/pages/css/select2.min.css',
    'public/backend/assets/global/css/bootstrap-datepicker.css',
    'public/backend/assets/global/css/clockface.css',

    // Calendar & Charts
    'public/backend/assets/global/plugins/fullcalendar/fullcalendar.min.css',
    'public/backend/assets/global/plugins/morris/morris.css',
    'public/backend/assets/global/plugins/jqvmap/jqvmap/jqvmap.css',

    // Media & Popups
    'public/backend/assets/global/css/magnific-popup.css',
    'public/backend/assets/global/css/jquery.fancybox.min.css',
    'public/backend/assets/global/css/intlTelInput.css',

    // Theme Core
    'public/backend/assets/global/css/components.min.css',
    'public/backend/assets/global/css/plugins.min.css',

    // Layout
    'public/backend/assets/layouts/layout4/css/layout.min.css',
    'public/backend/assets/layouts/layout4/css/themes/default.min.css',
    'public/backend/assets/layouts/layout4/css/custom.min.css',

    // Page-specific
    'public/backend/assets/pages/css/profile.min.css',

], 'public/css/backend-bundle.css');

// Production optimizations
if (mix.inProduction()) {
    mix.version();
}
