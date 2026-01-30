---
description: Check Laravel files for internationalization issues
---

# Laravel Internationalization Check Command

Check Laravel controllers and views for internationalization (i18n) issues, including hardcoded text that should be translated.

## Task

Perform a comprehensive internationalization audit on the codebase. Search for and report hardcoded text patterns that need to be internationalized.

## Patterns to Check

### 1. Controller Message Responses
Search for hardcoded messages in controllers:
- `messageResponse("Hardcoded text"` - should use `messageResponse(__('key'))`
- `response()->json(['message' => 'Hardcoded'` - should use `__('key')`
- `return "Hardcoded text"` - should use `__('key')`

### 2. DataTables Button Text
Search for hardcoded button text in DataTables columns:
- `>Edit</a>` or `>Delete</a>` - should use `__('common.edit')` / `__('common.delete')`
- `>View</a>` or `>Print</a>` - should use translation functions
- Status text like `>Active</span>` or `>Inactive</span>`

### 3. JavaScript Alert/Dialog Text
Search for hardcoded dialog titles:
- `swal("Alert!"` or `swal("Warning!"` - should use `{{ __('common.alert') }}`
- `confirm("Are you sure` - should use translated text

### 4. SMS/Email Message Templates
Search for hardcoded message content:
- `$message = 'Hello,` or `$message = "Dear`
- Email subjects and body text

**Example Fix:**
```php
// WRONG
$message = 'Hello, your appointment is scheduled for tomorrow.';
$subject = 'Appointment Reminder';

// CORRECT
$message = __('sms.appointment_reminder', ['date' => 'tomorrow']);
$subject = __('emails.appointment_reminder_subject');
```

### 5. Validator Custom Messages
Check if validators use `__()` for custom messages:
- `'field.required' => 'The field is required'` - should use `__('validation.custom.field.required')`

### 6. Third-Party Frontend Plugins
Check if frontend plugins are properly configured for i18n:

#### 6.1 DataTables Language Configuration
Search for DataTables initialization without language configuration:
```javascript
// WRONG - Missing language configuration
$('#table').DataTable({
    // no language option
});

// CORRECT - Using LanguageManager
$('#table').DataTable({
    language: LanguageManager.getDataTableLang(),
});
```

**Note:** Ensure `LanguageManager` is loaded in the layout:
```blade
<script src="{{ asset('js/i18n/language-manager.js') }}"></script>
<script src="{{ asset('js/i18n/lang-' . app()->getLocale() . '.js') }}"></script>
```

#### 6.2 Select2 Language Configuration
Search for Select2 without language option:
```javascript
// WRONG
$('.select2').select2();

// CORRECT
$('.select2').select2({
    language: '{{ app()->getLocale() }}',
    placeholder: "{{ __('common.select_option') }}"
});
// or with additional options
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

#### 6.3 Bootstrap Datepicker Language Configuration
Search for datepicker without language option:
```javascript
// WRONG
$('.datepicker').datepicker();

// CORRECT
$('.datepicker').datepicker({
    language: '{{ app()->getLocale() }}',
    format: 'yyyy-mm-dd',
    autoclose: true
});
```

**Note:** Ensure locale JS file is loaded:
```blade
<script src="{{ asset('backend/assets/global/plugins/bootstrap-datepicker/js/locales/bootstrap-datepicker.' . app()->getLocale() . '.js') }}"></script>
```

#### 6.4 FullCalendar Localization
Check if FullCalendar has locale configuration:
```javascript
// WRONG - Missing locale configuration
$('#calendar').fullCalendar({
    header: {
        left: 'prev,next today',
        center: 'title',
        right: 'month,agendaWeek,agendaDay'
    }
});

// CORRECT - With locale configuration
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

### 7. Blade Table Headers
Search for hardcoded table headers in Blade views:
- `<th>Name</th>` - should use `<th>{{ __('common.name') }}</th>`
- `<th>Action</th>` - should use `<th>{{ __('common.action') }}</th>`

## Search Directories
- `app/Http/Controllers/`
- `App/Http/Controllers/`
- `Modules/*/Http/Controllers/`
- `resources/views/**/*.blade.php`
- `Modules/*/Resources/views/**/*.blade.php`
- `public/js/**/*.js` (for frontend plugin configurations)

## Search Patterns for Third-Party Plugins

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

## Output Format

For each issue found, report:
1. File path and line number
2. The hardcoded text or missing configuration found
3. Suggested fix using translation function or proper configuration

## Actions

After identifying issues:
1. Fix the hardcoded text by replacing with `__('key')` translation calls
2. Add language configuration to frontend plugins
3. Ensure locale-specific JS files are loaded for third-party plugins
4. Add missing translation keys to language files:
   - `resources/lang/en/*.php`
   - `resources/lang/zh-CN/*.php`
5. Validate all modified files

## Example Fixes

### Controller Message Fix
**Before:**
```php
return response()->json(['message' => 'Record saved successfully', 'status' => true]);
```

**After:**
```php
return response()->json(['message' => __('messages.record_saved_successfully'), 'status' => true]);
```

### Controller custom validator message fix
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

### DataTables Button Fix
**Before:**
```php
$btn = '<a href="#" class="btn btn-primary">Edit</a>';
```

**After:**
```php
$btn = '<a href="#" class="btn btn-primary">' . __('common.edit') . '</a>';
```

### DataTables Language Fix
**Before:**
```javascript
$('#sample_1').DataTable({
    processing: true,
    serverSide: true,
});
```

**After:**
```javascript
$('#sample_1').DataTable({
    language: LanguageManager.getDataTableLang(),
    processing: true,
    serverSide: true,
});
```

### Select2 Language Fix
**Before:**
```javascript
$('.select2').select2();
```

**After:**
```javascript
$('.select2').select2({
    language: '{{ app()->getLocale() }}',
    placeholder: "{{ __('common.select_option') }}"
});
```

### Datepicker Language Fix
**Before:**
```javascript
$('.date-picker').datepicker({
    format: 'yyyy-mm-dd'
});
```

**After:**
```javascript
$('.date-picker').datepicker({
    language: '{{ app()->getLocale() }}',
    format: 'yyyy-mm-dd',
    autoclose: true
});
```

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

When checking plugin i18n, verify the layout file (`resources/views/layouts/app.blade.php`) includes:

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

## LanguageManager Detailed Initialization

When checking JavaScript i18n, verify LanguageManager is properly initialized with PHP translations:

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

When checking module translations, verify the Service Provider registers translations correctly:

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