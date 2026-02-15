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

    // Icons
    'public/backend/assets/global/plugins/font-awesome/css/fontawesome.min.css',
    'public/backend/assets/global/plugins/simple-line-icons/simple-line-icons.min.css',

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
