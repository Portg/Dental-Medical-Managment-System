---
description: Fix internationalization issues in Laravel files
---

# Laravel Internationalization Fix Command

Fix internationalization issues in a specific file or set of files.

## Task

When given a file path or pattern, analyze and fix all hardcoded text that should be internationalized, including Third-Party frontend plugin configurations.

## Input

The user may provide:
- A specific file path: `/app/Http/Controllers/ExampleController.php`
- A glob pattern: `app/Http/Controllers/*Controller.php`
- A module name: `Doctor`, `Nurse`, `Receptionist`, `SuperAdmin`
- A plugin type: `DataTables`, `Select2`, `Datepicker`, `FullCalendar`

## Search Directories

- `app/Http/Controllers/`
- `App/Http/Controllers/`
- `Modules/*/Http/Controllers/`
- `resources/views/**/*.blade.php`
- `Modules/*/Resources/views/**/*.blade.php`
- `public/js/**/*.js` (for frontend plugin configurations)

---

## Fix Process

### Step 1: Analyze the File
Read the file and identify all hardcoded text patterns:
- String literals in JSON responses
- HTML text in DataTables columns
- Alert/dialog messages
- SMS/Email templates
- Validator custom messages
- Blade table headers
- Third-Party plugin configurations without language settings

### Step 2: Determine Translation Keys
For each hardcoded text, determine the appropriate translation key:
- Use existing keys from language files if available
- Create new descriptive keys following naming conventions:
  - `module.feature.message_type` (e.g., `invoices.invoice_created_successfully`)
  - `common.action` for shared UI elements (e.g., `common.edit`, `common.delete`)
  - `messages.descriptive_name` for general messages

### Step 3: Apply Fixes
Replace hardcoded text with translation function calls.

### Step 4: Fix Third-Party Plugin Configurations
Add proper language configuration to frontend plugins.

### Step 5: Update Language Files
Add new translation keys to both language files:
- `resources/lang/en/*.php` - English version
- `resources/lang/zh-CN/*.php` - Chinese version

### Step 6: Validate
Run `php -l` on all modified files to ensure no syntax errors.

---

## Patterns to Fix

### 1. Controller Message Responses

**Before:**
```php
return response()->json(['message' => 'Record saved successfully', 'status' => true]);
```

**After:**
```php
return response()->json(['message' => __('messages.record_saved_successfully'), 'status' => true]);
```

**Before:**
```php
messageResponse("Hardcoded text")
```

**After:**
```php
messageResponse(__('messages.key'))
```

### 2. DataTables Button Text

**Before:**
```php
$btn = '<a href="#" class="btn btn-primary">Edit</a>';
$btn .= '<a href="#" class="btn btn-danger">Delete</a>';
```

**After:**
```php
$btn = '<a href="#" class="btn btn-primary">' . __('common.edit') . '</a>';
$btn .= '<a href="#" class="btn btn-danger">' . __('common.delete') . '</a>';
```

**Before:**
```php
$status = '<span class="label label-success">Active</span>';
```

**After:**
```php
$status = '<span class="label label-success">' . __('common.active') . '</span>';
```

### 3. JavaScript Alert/Dialog Text

**Before:**
```javascript
swal("Alert!", "Something happened", "warning");
confirm("Are you sure you want to delete?");
```

**After:**
```javascript
swal("{{ __('common.alert') }}", "{{ __('messages.something_happened') }}", "warning");
confirm("{{ __('common.confirm_delete') }}");
```

### 4. SMS/Email Message Templates

**Before:**
```php
$message = 'Hello, your appointment is scheduled for tomorrow.';
$subject = 'Appointment Reminder';
```

**After:**
```php
$message = __('sms.appointment_reminder', ['date' => 'tomorrow']);
$subject = __('emails.appointment_reminder_subject');
```

### 5. Validator Custom Messages

**Before:**
```php
Validator::make($request->all(), [
    'name' => 'required',
], [
    'name.required' => 'The name field is required',
]);
```

**Simplified (if no custom messages needed):**
```php
Validator::make($request->all(), [
    'name' => 'required',
]);
// Laravel will auto-translate using resources/lang/{locale}/validation.php
```

**After (with custom messages):**
```php
Validator::make($request->all(), [
    'name' => 'required',
], [
    'name.required' => __('validation.custom.name.required'),
]);
```

### 6. Blade Table Headers

**Before:**
```blade
<th>Name</th>
<th>Action</th>
<th>Status</th>
```

**After:**
```blade
<th>{{ __('common.name') }}</th>
<th>{{ __('common.action') }}</th>
<th>{{ __('common.status') }}</th>
```

---

## Third-Party Plugin Fixes

### DataTables Language Configuration

**Before:**
```javascript
$('#sample_1').DataTable({
    processing: true,
    serverSide: true,
    ajax: '/api/data',
    columns: [...]
});
```

**After:**
```javascript
$('#sample_1').DataTable({
    language: LanguageManager.getDataTableLang(),
    processing: true,
    serverSide: true,
    ajax: '/api/data',
    columns: [...]
});
```

**Note:** Ensure `LanguageManager` is loaded in the layout:
```blade
<script src="{{ asset('js/i18n/language-manager.js') }}"></script>
<script src="{{ asset('js/i18n/lang-' . app()->getLocale() . '.js') }}"></script>
```

### Select2 Language Configuration

**Before:**
```javascript
$('.select2').select2();
// or
$('.select2').select2({
    allowClear: true
});
```

**After:**
```javascript
$('.select2').select2({
    language: '{{ app()->getLocale() }}',
    placeholder: "{{ __('common.select_option') }}"
});
// or
$('.select2').select2({
    language: '{{ app()->getLocale() }}',
    placeholder: "{{ __('common.select_option') }}",
    allowClear: true
});
```

**Note:** Ensure locale JS file is loaded:
```blade
<script src="{{ asset('backend/assets/global/plugins/select2/js/i18n/' . app()->getLocale() . '.js') }}"></script>
```

### Bootstrap Datepicker Language Configuration

**Before:**
```javascript
$('.date-picker').datepicker({
    format: 'yyyy-mm-dd',
    autoclose: true
});
// or
$('.date-picker').datepicker();
```

**After:**
```javascript
$('.date-picker').datepicker({
    language: '{{ app()->getLocale() }}',
    format: 'yyyy-mm-dd',
    autoclose: true
});
```

**Note:** Ensure locale JS file is loaded:
```blade
<script src="{{ asset('backend/assets/global/plugins/bootstrap-datepicker/js/locales/bootstrap-datepicker.' . app()->getLocale() . '.js') }}"></script>
```

### FullCalendar Localization

**Before:**
```javascript
$('#calendar').fullCalendar({
    header: {
        left: 'prev,next today',
        center: 'title',
        right: 'month,agendaWeek,agendaDay'
    }
});
```

**After:**
```javascript
$('#calendar').fullCalendar({
    locale: '{{ app()->getLocale() }}',
    header: {
        left: 'prev,next today',
        center: 'title',
        right: 'month,agendaWeek,agendaDay'
    }
});
```

**Note:** Ensure locale JS file is loaded:
```blade
<script src="{{ asset('backend/assets/global/plugins/fullcalendar/lang/' . strtolower(app()->getLocale()) . '.js') }}"></script>
```

---

## Translation Key Naming Conventions

| Type | Pattern | Example |
|------|---------|---------|
| Success message | `module.action_successfully` | `invoices.invoice_created_successfully` |
| Error message | `messages.error_description` | `messages.error_try_again` |
| Button text | `common.action` | `common.edit`, `common.delete` |
| Status label | `common.status` | `common.active`, `common.inactive` |
| Dialog title | `common.dialog_type` | `common.alert`, `common.warning` |
| Validation | `validation.custom.field.rule` | `validation.custom.name.required` |
| SMS messages | `sms.message_type` | `sms.appointment_reminder` |
| Email subjects | `emails.subject_type` | `emails.appointment_reminder_subject` |

---

## Language File Locations

| Category | English | Chinese |
|----------|---------|---------|
| Common UI | `resources/lang/en/common.php` | `resources/lang/zh-CN/common.php` |
| Messages | `resources/lang/en/messages.php` | `resources/lang/zh-CN/messages.php` |
| Validation | `resources/lang/en/validation.php` | `resources/lang/zh-CN/validation.php` |
| Module-specific | `resources/lang/en/{module}.php` | `resources/lang/zh-CN/{module}.php` |
| SMS | `resources/lang/en/sms.php` | `resources/lang/zh-CN/sms.php` |
| Emails | `resources/lang/en/emails.php` | `resources/lang/zh-CN/emails.php` |

---

## Required Plugin Locale Files

Ensure these files exist for Chinese (zh-CN) support:

| Plugin | Locale File Path |
|--------|------------------|
| DataTables | `public/js/i18n/lang-zh-CN.js` |
| Select2 | `public/backend/assets/global/plugins/select2/js/i18n/zh-CN.js` |
| Datepicker | `public/backend/assets/global/plugins/bootstrap-datepicker/js/locales/bootstrap-datepicker.zh-CN.js` |
| FullCalendar | `public/backend/assets/global/plugins/fullcalendar/lang/zh-cn.js` |

---

## Layout File Updates

When fixing plugin i18n, ensure the layout file (`resources/views/layouts/app.blade.php`) includes:

```blade
@php
    $locale = app()->getLocale();
@endphp

{{-- Language Manager --}}
<script src="{{ asset('js/i18n/language-manager.js') }}"></script>
<script src="{{ asset('js/i18n/lang-' . $locale . '.js') }}"></script>

{{-- Plugin Locale Files --}}
<script src="{{ asset('backend/assets/global/plugins/select2/js/i18n/' . $locale . '.js') }}"></script>
<script src="{{ asset('backend/assets/global/plugins/bootstrap-datepicker/js/locales/bootstrap-datepicker.' . $locale . '.js') }}"></script>

{{-- FullCalendar (if used) --}}
@if(View::hasSection('fullcalendar'))
<script src="{{ asset('backend/assets/global/plugins/fullcalendar/lang/' . strtolower($locale) . '.js') }}"></script>
@endif
```

---

## Search Patterns for Identifying Issues

```bash
# DataTables without language
grep -r "\.DataTable\s*(" --include="*.blade.php" | grep -v "language"
grep -r "\.dataTable\s*(" --include="*.blade.php" | grep -v "language"

# Select2 without language
grep -r "\.select2\s*(" --include="*.blade.php" | grep -v "language"

# Datepicker without language
grep -r "\.datepicker\s*(" --include="*.blade.php" | grep -v "language"

# Hardcoded table headers
grep -r "<th>[A-Z]" --include="*.blade.php" | grep -v "__("

# Hardcoded JSON messages
grep -r "'message' => '" --include="*.php" | grep -v "__("
```

---

## Output

Report all changes made:
1. Files modified
2. Lines changed with before/after comparison
3. New translation keys added
4. Plugin configurations updated
5. Locale files verified/created
6. Validation results

---

## LanguageManager Detailed Initialization

When fixing JavaScript i18n, ensure LanguageManager is properly initialized with PHP translations:

```blade
@section('js')
<script src="{{ asset('js/i18n/lang-en.js') }}"></script>
<script src="{{ asset('js/i18n/lang-zh-CN.js') }}"></script>
<script src="{{ asset('js/i18n/language-manager.js') }}"></script>
<script type="text/javascript">
    $(function () {
        // Initialize LanguageManager with Laravel config
        LanguageManager.init({
            availableLocales: @json(config('app.available_locales')),
            currentLocale: '{{ app()->getLocale() }}',
            defaultLocale: '{{ config('app.fallback_locale', 'en') }}'
        });

        // Load translations from PHP
        LanguageManager.loadAllFromPHP({
            'common': @json(__('common')),
            'messages': @json(__('messages')),
            'validation': @json(__('validation'))
        });

        // Now initialize DataTable with language
        $('#myTable').DataTable({
            language: LanguageManager.getDataTableLang(),
            // ...
        });
    });
</script>
@endsection
```

---

## Module Service Provider Translation Registration

When fixing module translations, ensure the Service Provider registers translations correctly:

```php
// Modules/Doctor/Providers/DoctorServiceProvider.php
public function registerTranslations()
{
    $langPath = resource_path('lang/modules/doctor');

    if (is_dir($langPath)) {
        $this->loadTranslationsFrom($langPath, 'doctor');
    } else {
        $this->loadTranslationsFrom(__DIR__ .'/../Resources/lang', 'doctor');
    }
}
```

Usage in views:
```blade
{{ __('doctor::appointments.title') }}
{{ __('nurse::tasks.pending') }}
{{ __('receptionist::dashboard.welcome') }}
```

---

## Language Switching Implementation

### Middleware for Locale Detection

```php
// app/Http/Middleware/SetLocale.php
public function handle($request, Closure $next)
{
    $locale = session('locale', config('app.locale'));

    if (in_array($locale, array_keys(config('app.available_locales', [])))) {
        app()->setLocale($locale);
    }

    return $next($request);
}
```

### Language Switch Controller

```php
// app/Http/Controllers/LanguageController.php
public function switchLang($lang)
{
    $availableLocales = array_keys(config('app.available_locales', []));

    if (in_array($lang, $availableLocales)) {
        session(['locale' => $lang]);
        app()->setLocale($lang);
    }
    return redirect()->back();
}
```

### Language Switcher in View

```blade
<div class="language-switcher">
    @foreach(config('app.available_locales') as $code => $name)
        <a href="{{ url('lang/' . $code) }}"
           class="{{ app()->getLocale() == $code ? 'active' : '' }}">
            {{ $name }}
        </a>
    @endforeach
</div>
```

### Route Registration

```php
// routes/web.php
Route::get('lang/{locale}', 'LanguageController@switchLang')->name('lang.switch');
```

---

## Date, Time & Number Formatting

### Date Formatting with Locale

```php
// In Controller or View
use Carbon\Carbon;

Carbon::setLocale(app()->getLocale());
$formattedDate = Carbon::parse($date)->translatedFormat('Y年m月d日'); // Chinese
$formattedDate = Carbon::parse($date)->translatedFormat('F j, Y');    // English
```

### Number Formatting

```php
// Currency formatting
number_format($amount, 2, '.', ',')

// In Blade with locale-aware formatting
{{ number_format($amount, 2) }}
```

### JavaScript Date Formatting

```javascript
// Use moment.js with locale
moment.locale(LanguageManager.getCurrentLanguage());
var formattedDate = moment(dateString).format('LL');
```

---

## Troubleshooting

### Common Issues

| Issue | Cause | Solution |
|-------|-------|----------|
| Translation key displayed as-is | Missing key in language file | Add key using `/i18n-add-key` command |
| DataTables shows English text | Missing LanguageManager initialization | Add `LanguageManager.init()` before DataTable |
| Select2/Datepicker in English | Missing locale JS file or language option | Load locale file and add `language` option |
| LanguageManager is undefined | JS files loaded in wrong order | Load `language-manager.js` after jQuery |
| Translations not updating | Browser cache | Clear browser cache or hard refresh |

### Debugging

**JavaScript:**
```javascript
// Check current language
console.log('Current:', LanguageManager.getCurrentLanguage());
console.log('Available:', LanguageManager.getSupportedLanguages());
console.log('Loaded modules:', LanguageManager.getLoadedModules());

// Test translation
console.log(LanguageManager.trans('common.edit'));
```

**PHP:**
```php
// Check current locale in PHP
dd(app()->getLocale());
dd(__('common.edit'));
dd(session('locale'));
```