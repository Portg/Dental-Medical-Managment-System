---
name: laravel-Internationalization
description: This document captures the comprehensive internationalization (i18n) skills and techniques implemented in the Dental Medical Management System, a modular Laravel application supporting multiple languages.
---

## Usage

### How to Invoke This Skill

Use the `/laravel-Internationalization` command to access internationalization capabilities for this Laravel project.

```
/laravel-Internationalization
```

### What This Skill Does

When invoked, this skill provides:
1. **i18n Knowledge Base** - Reference for Laravel translation patterns and best practices
2. **Access to Related Commands** - Quick access to `/i18n-check`, `/i18n-fix`, and `/i18n-add-key`
3. **Code Examples** - Ready-to-use code snippets for common i18n scenarios

### Common Use Cases

| Task | How to Use |
|------|------------|
| Check for hardcoded text | Run `/i18n-check` |
| Fix i18n issues in a file | Run `/i18n-fix` and specify the file path |
| Add a new translation key | Run `/i18n-add-key` with key and translations |
| Learn i18n patterns | Invoke `/laravel-Internationalization` and ask questions |

### Quick Start Examples

**Example 1: Check entire codebase for i18n issues**
```
User: /i18n-check
```

**Example 2: Fix i18n issues in a specific controller**
```
User: /i18n-fix app/Http/Controllers/PatientController.php
```

**Example 3: Add a new translation key**
```
User: /i18n-add-key
messages.patient_created_successfully
EN: Patient created successfully
ZH: 患者创建成功
```

**Example 4: Ask about i18n implementation**
```
User: /laravel-Internationalization
User: How do I internationalize DataTables?
```

---

## Prerequisites & Environment

### Supported Languages
- English (`en`) - Default/Fallback
- Chinese Simplified (`zh-CN`)

### Required Files
```
resources/lang/
├── en/                  # English translations
└── zh-CN/               # Chinese translations

public/js/i18n/
├── language-manager.js  # Frontend translation manager
├── lang-en.js           # English JS translations
└── lang-zh-CN.js        # Chinese JS translations

public/backend/assets/global/plugins/
├── select2/js/i18n/{locale}.js
├── bootstrap-datepicker/js/locales/bootstrap-datepicker.{locale}.js
└── fullcalendar/lang/{locale}.js
```

### Configuration (config/app.php)
```php
'locale' => 'en',              // Default locale
'fallback_locale' => 'en',     // Fallback locale
'faker_locale' => 'zh_CN',     // Faker locale for testing
'available_locales' => [
    'en' => 'English',
    'zh-CN' => '简体中文',
],
```

---

## Related Commands

This skill works with the following Claude commands:

| Command | Description |
|---------|-------------|
| `/i18n-check` | Check for internationalization issues in the codebase |
| `/i18n-fix` | Fix internationalization issues in specific files |
| `/i18n-add-key` | Add new translation keys to language files |

---

## Search Directories

When checking or fixing i18n issues, search these directories:

- `app/Http/Controllers/`
- `App/Http/Controllers/` (case-sensitive on some systems)
- `Modules/*/Http/Controllers/`
- `resources/views/**/*.blade.php`
- `Modules/*/Resources/views/**/*.blade.php`
- `public/js/**/*.js` (for frontend plugin configurations)

---

## Search Patterns for Detecting Issues

Use these grep commands to find i18n issues:

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

## Required Plugin Locale Files

Ensure these files exist for Chinese (zh-CN) support:

| Plugin | Locale File Path |
|--------|------------------|
| DataTables | `public/js/i18n/lang-zh-CN.js` |
| Select2 | `public/backend/assets/global/plugins/select2/js/i18n/zh-CN.js` |
| Datepicker | `public/backend/assets/global/plugins/bootstrap-datepicker/js/locales/bootstrap-datepicker.zh-CN.js` |
| FullCalendar | `public/backend/assets/global/plugins/fullcalendar/lang/zh-cn.js` |

---

## 1. Laravel Internationalization Fundamentals

### Language Package Structure

```php
// resources/lang/zh-CN/example.php
<?php
return [
    'key' => '翻译值',
    'nested' => [
        'key' => '嵌套翻译'
    ],
    'parameterized' => '带参数的翻译 :parameter'
];
```

### Translation Functions in Views

```blade
<!-- Basic translation -->
{{ __('example.key') }}

<!-- Nested key translation -->
{{ __('example.nested.key') }}

<!-- Parameterized translation -->
{{ __('example.parameterized', ['parameter' => $value]) }}

<!-- With default value -->
{{ __('example.missing_key', [], 'en') }}
```

### Language File Directory Structure

```
resources/lang/
├── en/
│   ├── auth.php              # Authentication messages
│   ├── validation.php        # Validation rules & attributes
│   ├── common.php            # Common UI elements (edit, delete, save, etc.)
│   ├── messages.php          # Controller response messages
│   ├── patient.php           # Patient module translations
│   ├── appointment.php       # Appointment translations
│   ├── invoices.php          # Invoice/billing translations
│   ├── medical_treatment.php # Treatment translations
│   ├── doctor_claims.php     # Doctor claims translations
│   ├── sms.php               # SMS message templates
│   ├── emails.php            # Email templates
│   ├── holidays.php          # Holiday management
│   ├── expenses.php          # Expense management
│   ├── leaves.php            # Leave requests
│   ├── roles.php             # Role management
│   ├── permissions.php       # Permission management
│   ├── report.php            # Reports
│   ├── medical.php           # Medical records
│   └── ...
└── zh-CN/
    └── ... (mirrors en/ structure)
```

### Core Application Files Reference

| File                | Description                                      |
|---------------------|--------------------------------------------------|
| `common`            | Shared UI elements (buttons, labels, statuses)   |
| `messages`          | Controller response messages                     |
| `validation`        | Validation rules and custom messages             |
| `auth`              | Authentication messages                          |
| `invoices`          | Invoice/billing related                          |
| `patient`           | Patient management                               |
| `appointment`       | Appointment scheduling                           |
| `medical_treatment` | Treatment related                                |
| `doctor_claims`     | Doctor claims                                    |
| `sms`               | SMS message templates                            |
| `emails`            | Email templates                                  |
| `holidays`          | Holiday management                               |
| `expenses`          | Expense management                               |
| `leaves`            | Leave requests                                   |
| `roles`             | Role management                                  |
| `permissions`       | Permission management                            |
| `report`            | Reports                                          |
| `medical`           | Medical records                                  |

Path pattern: `resources/lang/{locale}/{file}.php`

------

## 2. Controller Internationalization Patterns

### JSON Response Messages (messageResponse Pattern)

```php
// Using FunctionsHelper::messageResponse()
public function store(Request $request)
{
    $status = Model::create([...]);

    if ($status) {
        return FunctionsHelper::messageResponse(
            __('messages.record_created_successfully'),
            $status
        );
    }
    return FunctionsHelper::messageResponse(
        __('messages.error_try_again'),
        false
    );
}
```

### Direct JSON Response

```php
public function update(Request $request, $id)
{
    $status = Model::where('id', $id)->update([...]);

    if ($status) {
        return response()->json([
            'message' => __('permissions.permission_updated_successfully'),
            'status' => true
        ]);
    }
    return response()->json([
        'message' => __('messages.error_occurred'),
        'status' => false
    ]);
}
```

### Parameterized Messages

```php
// Message with dynamic parameter
return response()->json([
    'message' => __('messages.appointment_status_updated', ['status' => $request->status]),
    'status' => true
]);

// In language file:
// 'appointment_status_updated' => 'Appointment has been saved as :status',
```

### SMS Message Templates

```php
// SMS with multiple parameters
$message = __('sms.appointment_scheduled', [
    'name' => $patient->name,
    'company' => config('app.name'),
    'date' => $appointment->date,
    'time' => $appointment->time
]);

// In language file:
// 'appointment_scheduled' => 'Hello, :name Your appointment at :company has been scheduled for :date at :time',
```

------

## 3. DataTables Internationalization

### Button Text in Column Callbacks

```php
return Datatables::of($data)
    ->addColumn('editBtn', function ($row) {
        return '<a href="#" onclick="editRecord(' . $row->id . ')" class="btn btn-primary">'
            . __('common.edit') . '</a>';
    })
    ->addColumn('deleteBtn', function ($row) {
        return '<a href="#" onclick="deleteRecord(' . $row->id . ')" class="btn btn-danger">'
            . __('common.delete') . '</a>';
    })
    ->rawColumns(['editBtn', 'deleteBtn'])
    ->make(true);
```

### Status Text Display

```php
->addColumn('status', function ($row) {
    if ($row->status == 1) {
        return '<span class="text-primary">' . __('common.active') . '</span>';
    }
    return '<span class="text-danger">' . __('common.inactive') . '</span>';
})
```

### Action Dropdown Menus

```php
->addColumn('action', function ($row) {
    $btn = '<div class="btn-group">
        <button class="btn dropdown-toggle" data-toggle="dropdown">'
            . __('common.action') . '</button>
        <ul class="dropdown-menu">
            <li><a href="#" onclick="editRecord(' . $row->id . ')">'
                . __('common.edit') . '</a></li>
            <li><a href="#" onclick="deleteRecord(' . $row->id . ')">'
                . __('common.delete') . '</a></li>
        </ul>
    </div>';
    return $btn;
})
```

### DataTables JavaScript Initialization (REQUIRED)

```javascript
// In Blade template - MUST include language configuration
$('#example').DataTable({
    language: LanguageManager.getDataTableLang(),
    processing: true,
    serverSide: true,
    // other DataTable options...
});
```

### Blade Table Headers

Hardcoded table headers should use translation functions:

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

------

## 4. Validator Internationalization

### Basic Validation (Uses Default Messages)

```php
// Laravel automatically uses validation.php translations
Validator::make($request->all(), [
    'payment_date' => 'required',
    'amount' => 'required',
    'payment_method' => 'required'
])->validate();
// Laravel will auto-translate using resources/lang/{locale}/validation.php
```

**Note:** If no custom messages are needed, simply omit the third parameter. Laravel will automatically use translations from `validation.php` based on the current locale.

### Custom Validation Messages

```php
Validator::make($request->all(), [
    'name' => 'required|string|max:255',
    'email' => 'required|email',
], [
    'name.required' => __('validation.custom.name.required'),
    'email.required' => __('validation.custom.email.required'),
])->validate();
```

### Validation Attributes Translation

```php
// resources/lang/zh-CN/validation.php
'attributes' => [
    'employee' => '员工',
    'contract_type' => '合同类型',
    'payment_date' => '支付日期',
    'amount' => '金额',
    'payment_method' => '支付方式',
    'leaves' => [
        'leave_type' => '假期类型',
        'start_date' => '开始日期',
        'duration' => '持续时间',
    ],
],

'custom' => [
    'name' => [
        'required' => 'The name field is required.',
    ],
    'body_reaction' => [
        'required' => 'Please describe the body reaction.',
    ],
    'patient_id' => [
        'required' => 'Please select a patient.',
    ],
],
```

------

## 5. JavaScript & Blade Internationalization

### LanguageManager Initialization (REQUIRED in every view using JS translations)

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

### SweetAlert Dialogs

```blade
<script>
function alert_dialog(message, status) {
    if (status) {
        swal("{{ __('common.alert') }}", message, status);
    } else {
        swal("{{ __('common.warning') }}", message);
    }
}
</script>
```

### Confirm Dialogs

```blade
<script>
function confirmDelete(id) {
    swal({
        title: "{{ __('common.confirm_delete_title') }}",
        text: "{{ __('common.delete_confirm_message') }}",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "{{ __('common.yes_delete') }}",
        cancelButtonText: "{{ __('common.cancel') }}"
    }).then(function() {
        deleteRecord(id);
    });
}
</script>
```

### Using LanguageManager.trans() in JavaScript

```javascript
// Basic usage
var msg = LanguageManager.trans('common.save');

// With parameters
var msg = LanguageManager.trans('messages.welcome', { name: userName });

// With default value
var msg = LanguageManager.trans('missing.key', 'Default Text');
```

------

## 6. Modular Architecture Internationalization

### Service Provider Translation Registration

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

### Module Translation File Structure

```
Modules/
├── Doctor/
│   └── Resources/
│       └── lang/
│           ├── en/
│           │   ├── doctor.php
│           │   ├── appointments.php
│           │   └── invoices.php
│           └── zh-CN/
│               ├── doctor.php
│               ├── appointments.php
│               └── invoices.php
├── Nurse/
├── Receptionist/
└── SuperAdmin/
```

### Module Translation Usage

```blade
<!-- Accessing module translations -->
{{ __('doctor::appointments.title') }}
{{ __('nurse::tasks.pending') }}
{{ __('receptionist::dashboard.welcome') }}
```

------

## 7. Common Translation Keys Structure

### common.php - Shared UI Elements

```php
return [
    // Actions
    'edit' => 'Edit',
    'delete' => 'Delete',
    'save' => 'Save',
    'save_changes' => 'Save Changes',
    'cancel' => 'Cancel',
    'close' => 'Close',
    'action' => 'Action',
    'view' => 'View',
    'print' => 'Print',
    'search' => 'Search',
    'filter' => 'Filter',
    'clear' => 'Clear',
    'submit' => 'Submit',
    'reset' => 'Reset',

    // Status
    'active' => 'Active',
    'inactive' => 'Inactive',
    'pending' => 'Pending',
    'completed' => 'Completed',
    'approved' => 'Approved',
    'rejected' => 'Rejected',

    // Dialogs
    'alert' => 'Alert!',
    'warning' => 'Warning!',
    'are_you_sure' => 'Are you sure?',
    'confirm_delete_title' => 'Are you sure?',
    'yes_delete_it' => 'Yes, delete it!',
    'processing' => 'Processing...',
    'loading' => 'Loading...',

    // Table
    'actions' => 'Actions',
    'name' => 'Name',
    'status' => 'Status',
    'date' => 'Date',
    'created_at' => 'Created At',
];
```

### messages.php - Controller Responses

```php
return [
    // Success Messages
    'record_created_successfully' => 'Record created successfully!',
    'record_updated_successfully' => 'Record updated successfully!',
    'record_deleted_successfully' => 'Record deleted successfully!',

    // Error Messages
    'error_occurred' => 'An error occurred, please try again.',
    'error_try_again' => 'Oops, an error has occurred. Please try again.',
    'error_occurred_later' => 'An error occurred, please try again later.',

    // Status Messages
    'no_invoice_yet' => 'No Invoice Yet',
    'invoice_already_generated' => 'Invoice Already Generated',
    'data_deleted' => 'Data Deleted',

    // Parameterized Messages
    'appointment_status_updated' => 'Appointment has been saved as :status',
];
```

------

## 8. Email Template Internationalization

### Email View

```blade
<!-- resources/views/emails/invoice.blade.php -->
@component('mail::message')
# {{ __('emails.invoice_subject') }}

{{ __('emails.dear_patient', ['surname' => $patient->surname, 'othername' => $patient->othername]) }}

{{ __('emails.thank_you_message', ['company_name' => config('app.name')]) }}
@endcomponent
```

### Email Language File

```php
// resources/lang/en/emails.php
return [
    'invoice_subject' => 'Your Invoice',
    'dear_patient' => 'Dear :surname :othername,',
    'thank_you_message' => 'Thank you for choosing :company_name.',
    'invoice_attached' => 'invoice',
    'quotation_attached' => 'quotation',
    'db_backup_done_subject' => 'DB Auto-backup Done',
    'new_database_backup_file' => 'You have a new database backup file.',
];
```

------

## 9. Third-Party Component Internationalization

### FullCalendar Localization

```blade
{{-- Load locale-specific calendar JS --}}
<script src="{{ asset('backend/assets/global/plugins/fullcalendar/lang/' . strtolower(app()->getLocale()) . '.js') }}"></script>

<script>
$('#calendar').fullCalendar({
    locale: '{{ app()->getLocale() }}',
    // other options...
});
</script>
```

### Select2 with Localization

```blade
{{-- Load Select2 locale file --}}
<script src="{{ asset('backend/assets/global/plugins/select2/js/i18n/' . app()->getLocale() . '.js') }}"></script>

<script>
$('.select2').select2({
    language: '{{ app()->getLocale() }}',
    placeholder: "{{ __('common.select_option') }}"
});
</script>
```

### Bootstrap Datepicker

```blade
{{-- Load Datepicker locale file --}}
<script src="{{ asset('backend/assets/global/plugins/bootstrap-datepicker/js/locales/bootstrap-datepicker.' . app()->getLocale() . '.js') }}"></script>

<script>
$('.datepicker').datepicker({
    language: '{{ app()->getLocale() }}',
    format: 'yyyy-mm-dd',
    autoclose: true
});
</script>
```

------

## 10. Language Switching

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

------

## 11. Date, Time & Number Formatting

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

------

## 12. Implementation Checklist

### Controller Internationalization
- [ ] Replace all `messageResponse("Hardcoded message")` with `messageResponse(__('key'))`
- [ ] Replace all `response()->json(['message' => 'Text'])` with translated keys
- [ ] Internationalize DataTables button text (Edit, Delete, View, etc.)
- [ ] Internationalize status labels (Active/Inactive, Paid/Unpaid, etc.)
- [ ] Internationalize dropdown menu items
- [ ] Internationalize SMS message templates
- [ ] Internationalize email subjects and bodies

### Blade/JavaScript Internationalization
- [ ] Replace hardcoded swal/alert dialog titles
- [ ] Replace confirm dialog messages
- [ ] Use `{{ __('key') }}` for all user-facing text
- [ ] Initialize LanguageManager in every view using JS translations
- [ ] Configure DataTables with `language: LanguageManager.getDataTableLang()`
- [ ] Configure Select2 with `language: '{{ app()->getLocale() }}'`
- [ ] Configure Datepicker with `language: '{{ app()->getLocale() }}'`

### Validator Internationalization
- [ ] Define custom validation messages using `__()` function
- [ ] Configure validation attributes in validation.php
- [ ] Add custom field-specific messages in validation.php custom array

### Verification
- [ ] Run `php -l` on all modified PHP files
- [ ] Test both language versions (en and zh-CN)
- [ ] Verify all translation keys exist in language files
- [ ] Run `/i18n-check` to find remaining issues

------

## 13. Troubleshooting

### Common Issues

| Issue | Cause | Solution |
|-------|-------|----------|
| Translation key displayed as-is | Missing key in language file | Add key using `/i18n-add-key` command |
| DataTables shows English text | Missing LanguageManager initialization | Add `LanguageManager.init()` before DataTable |
| Select2/Datepicker in English | Missing locale JS file or language option | Load locale file and add `language` option |
| LanguageManager is undefined | JS files loaded in wrong order | Load `language-manager.js` after jQuery |
| Translations not updating | Browser cache | Clear browser cache or hard refresh |

### Debugging

```javascript
// Check current language
console.log('Current:', LanguageManager.getCurrentLanguage());
console.log('Available:', LanguageManager.getSupportedLanguages());
console.log('Loaded modules:', LanguageManager.getLoadedModules());

// Test translation
console.log(LanguageManager.trans('common.edit'));
```

```php
// Check current locale in PHP
dd(app()->getLocale());
dd(__('common.edit'));
dd(session('locale'));
```

------

## 14. Adding Translation Keys (/i18n-add-key)

### Input Formats

The `/i18n-add-key` command supports multiple input formats:

**Format 1: Structured Input**
```
file: messages
key: record_saved_successfully
EN: Record saved successfully
ZH: 记录保存成功
```

**Format 2: Dot Notation**
```
messages.record_saved_successfully
EN: Record saved successfully
ZH: 记录保存成功
```

**Format 3: Quick Format**
```
messages.record_saved_successfully = Record saved successfully / 记录保存成功
```

**Format 4: Nested Keys**
```
invoices.status.paid
EN: Paid
ZH: 已支付
```

**Format 5: Module Translation (with namespace)**
```
doctor::appointments.title
EN: Doctor Appointments
ZH: 医生预约
```

### Batch Add (Multiple Keys)

You can add multiple keys at once:

```
file: invoices

keys:
- invoice_void: Void Invoice / 作废发票
- invoice_voided_successfully: Invoice voided successfully / 发票作废成功
- confirm_void_invoice: Are you sure you want to void this invoice? / 确定要作废此发票吗？
```

### Pluralization (Laravel Style)

```php
// English
'items_count' => '{0} No items|{1} One item|[2,*] :count items'

// Chinese
'items_count' => '{0} 没有项目|{1} 1 个项目|[2,*] :count 个项目'
```

Usage:
```php
trans_choice('messages.items_count', $count);
```

### Error Handling

| Scenario | Behavior |
|----------|----------|
| **Key Already Exists** | Shows current value, asks for confirmation to update |
| **Invalid File** | Asks if user wants to create a new language file |
| **Syntax Error After Edit** | Shows error, reverts changes, reports issue |

------

## 15. Quick Reference

### Adding New Translation Key

1. Add to English file: `resources/lang/en/{module}.php`
2. Add to Chinese file: `resources/lang/zh-CN/{module}.php`
3. Use in code: `__('module.key_name')`

### Translation Key Naming Convention

| Type | Pattern | Example |
|------|---------|---------|
| Success message | `module.action_successfully` | `invoices.invoice_created_successfully` |
| Error message | `messages.error_description` | `messages.error_try_again` |
| Button text | `common.action` | `common.edit`, `common.delete` |
| Status label | `common.status` | `common.active`, `common.inactive` |
| Dialog title | `common.dialog_type` | `common.alert`, `common.warning` |
| Validation | `validation.custom.field.rule` | `validation.custom.name.required` |

### File Locations

| Category | English | Chinese |
|----------|---------|---------|
| Common UI | `resources/lang/en/common.php` | `resources/lang/zh-CN/common.php` |
| Messages | `resources/lang/en/messages.php` | `resources/lang/zh-CN/messages.php` |
| Validation | `resources/lang/en/validation.php` | `resources/lang/zh-CN/validation.php` |
| Module-specific | `resources/lang/en/{module}.php` | `resources/lang/zh-CN/{module}.php` |