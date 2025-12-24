# 国际化(i18n)实现文档 / Internationalization Implementation Guide

## 概述 / Overview

本文档说明了 Dental Medical Management System 的国际化实现，支持英文(en)和中文(zh)两种语言。

This document explains the internationalization implementation for the Dental Medical Management System, supporting English (en) and Chinese (zh) languages.

---

## 已完成的工作 / Completed Work

### 1. 语言文件结构 / Language File Structure

已创建以下语言文件:
The following language files have been created:

```
resources/lang/
├── en/
│   ├── auth.php                 # 认证相关 / Authentication
│   ├── auth_extended.php        # 扩展认证 / Extended auth
│   ├── passwords.php            # 密码重置 / Password reset
│   ├── pagination.php           # 分页 / Pagination
│   ├── validation.php           # 表单验证 / Form validation
│   ├── common.php               # 通用字符串 / Common strings
│   ├── appointments.php         # 预约模块 / Appointments
│   ├── patients.php             # 患者模块 / Patients
│   ├── medical.php              # 医疗治疗 / Medical treatment
│   ├── invoices.php             # 发票模块 / Invoices
│   ├── expenses.php             # 费用模块 / Expenses
│   ├── users.php                # 用户管理 / Users
│   ├── hr.php                   # 人力资源 / Human resources
│   ├── reports.php              # 报告模块 / Reports
│   └── settings.php             # 设置模块 / Settings
└── zh/
    └── (相同的文件结构 / Same file structure)
```

### 2. 中间件 / Middleware

创建了 `SetLocale` 中间件来自动设置应用语言:
Created `SetLocale` middleware to automatically set application language:

- 文件位置 / File location: `app/Http/Middleware/SetLocale.php`
- 已添加到web中间件组 / Added to web middleware group

### 3. 语言切换控制器 / Language Controller

创建了 `LanguageController` 来处理语言切换:
Created `LanguageController` to handle language switching:

- 文件位置 / File location: `app/Http/Controllers/LanguageController.php`
- 路由 / Route: `GET /language/{locale}`

### 4. 示例视图文件修改 / Sample View File Modifications

已完成以下视图文件的国际化:
Completed internationalization for the following view files:

- `resources/views/auth/login.blade.php` - 登录页面 / Login page

---

## 使用方法 / Usage

### 在视图中使用国际化 / Using i18n in Views

```php
<!-- 简单翻译 / Simple translation -->
{{ __('common.buttons.save') }}

<!-- 带参数的翻译 / Translation with parameters -->
{{ __('auth.throttle', ['seconds' => 60]) }}

<!-- 多个选择(单复数) / Pluralization -->
{{ trans_choice('messages.apples', 10) }}
```

### 在控制器中使用 / Using in Controllers

```php
// 简单消息 / Simple message
return redirect()->back()->with('success', __('common.messages.record_saved'));

// 验证消息 / Validation messages
$request->validate([
    'email' => 'required|email',
], [
    'email.required' => __('validation.required', ['attribute' => __('common.fields.email')]),
    'email.email' => __('validation.email', ['attribute' => __('common.fields.email')]),
]);
```

### 语言切换 / Language Switching

用户可以通过访问以下URL切换语言:
Users can switch language by visiting:

- 切换到英文 / Switch to English: `/language/en`
- 切换到中文 / Switch to Chinese: `/language/zh`

---

## 待完成的工作 / Remaining Work

### 1. 批量转换视图文件 / Batch Convert View Files

需要修改约 **130+ 个视图文件**，将硬编码的文本替换为国际化函数调用。
Need to modify approximately **130+ view files**, replacing hardcoded text with i18n function calls.

#### 转换示例 / Conversion Examples:

**之前 / Before:**
```html
<button>Add New</button>
<div class="desc">Today's Appointments</div>
<input placeholder="Email address" name="email"/>
```

**之后 / After:**
```html
<button>{{ __('common.buttons.add_new') }}</button>
<div class="desc">{{ __('appointments.dashboard.todays_appointments') }}</div>
<input placeholder="{{ __('auth_extended.login.email_placeholder') }}" name="email"/>
```

#### 需要修改的主要视图文件 / Main View Files to Modify:

1. **布局文件 / Layout Files**
   - `resources/views/layouts/app.blade.php` - 主布局和导航菜单 / Main layout and navigation

2. **首页 / Home**
   - `resources/views/home.blade.php` - 仪表板 / Dashboard

3. **预约模块 / Appointments Module**
   - `resources/views/appointments/*.blade.php`

4. **患者模块 / Patients Module**
   - `resources/views/patients/*.blade.php`

5. **医疗治疗模块 / Medical Treatment Module**
   - `resources/views/medical_treatment/**/*.blade.php`
   - `resources/views/medical_history/**/*.blade.php`

6. **发票模块 / Invoicing Module**
   - `resources/views/invoices/**/*.blade.php`
   - `resources/views/quotations/**/*.blade.php`

7. **其他模块 / Other Modules**
   - 费用管理 / Expenses: `resources/views/expenses/**/*.blade.php`
   - 用户管理 / Users: `resources/views/users/*.blade.php`
   - 人力资源 / HR: `resources/views/payslips/**/*.blade.php`, `resources/views/leave_*/**/*.blade.php`
   - 报告 / Reports: `resources/views/reports/**/*.blade.php`
   - 设置 / Settings: `resources/views/*/index.blade.php`, `resources/views/*/create.blade.php`

### 2. 控制器国际化 / Controller Internationalization

需要为所有控制器添加国际化支持:
Need to add i18n support for all controllers:

#### 验证消息 / Validation Messages

**示例 / Example:**
```php
// PatientController.php
public function store(Request $request)
{
    $request->validate([
        'surname' => 'required|string|max:255',
        'other_name' => 'required|string|max:255',
        'email' => 'required|email|unique:patients',
        'phone' => 'required|string',
    ], [
        'surname.required' => __('validation.required', ['attribute' => __('patients.form.surname')]),
        'email.required' => __('validation.required', ['attribute' => __('patients.form.email')]),
        'email.email' => __('validation.email', ['attribute' => __('patients.form.email')]),
        'email.unique' => __('validation.unique', ['attribute' => __('patients.form.email')]),
    ]);

    // ... rest of code

    return redirect()->back()->with('success', __('common.messages.record_saved'));
}
```

#### Flash 消息 / Flash Messages

**示例 / Example:**
```php
// Success message
return redirect()->route('appointments.index')
    ->with('success', __('common.messages.record_saved'));

// Error message
return redirect()->back()
    ->with('error', __('common.messages.operation_failed'));
```

### 3. JavaScript 国际化 / JavaScript i18n

对于包含JavaScript字符串的页面，需要传递翻译到前端:
For pages containing JavaScript strings, need to pass translations to frontend:

```php
<script>
    const translations = {
        confirmDelete: "{{ __('common.alerts.are_you_sure') }}",
        yesDelete: "{{ __('common.alerts.yes_delete') }}",
        loading: "{{ __('common.status.loading') }}"
    };
</script>
```

### 4. 添加语言切换UI / Add Language Switcher UI

在主布局文件中添加语言切换器:
Add language switcher in main layout file:

```html
<!-- resources/views/layouts/app.blade.php -->
<div class="language-switcher">
    <a href="{{ route('language.switch', 'en') }}" class="{{ app()->getLocale() == 'en' ? 'active' : '' }}">
        English
    </a>
    |
    <a href="{{ route('language.switch', 'zh') }}" class="{{ app()->getLocale() == 'zh' ? 'active' : '' }}">
        中文
    </a>
</div>
```

---

## 批量转换工具 / Batch Conversion Tool

为了加快视图文件的转换，可以创建一个Artisan命令:
To speed up view file conversion, you can create an Artisan command:

```bash
php artisan make:command TranslateViews
```

该命令可以自动扫描视图文件并提示需要翻译的文本。
This command can automatically scan view files and prompt for text that needs translation.

---

## 测试 / Testing

### 1. 语言切换测试 / Language Switching Test

1. 访问 `/language/en` - 应显示英文界面
2. 访问 `/language/zh` - 应显示中文界面
3. 刷新页面 - 语言应保持不变(存储在session中)

### 2. 表单验证测试 / Form Validation Test

1. 提交空表单 - 验证错误消息应以当前语言显示
2. 提交无效数据 - 错误消息应以当前语言显示

### 3. Flash 消息测试 / Flash Message Test

1. 创建/更新/删除记录 - 成功/错误消息应以当前语言显示

---

## 最佳实践 / Best Practices

1. **一致的键名** / Consistent Key Names
   - 使用描述性的键名，如 `appointments.table.appointment_date` 而不是 `app.t1.d1`

2. **模块化组织** / Modular Organization
   - 每个模块有自己的语言文件，便于维护

3. **通用字符串复用** / Reuse Common Strings
   - 按钮、状态等通用字符串放在 `common.php` 中

4. **验证属性翻译** / Validation Attribute Translation
   - 在 `validation.php` 的 `attributes` 数组中定义字段名翻译

5. **参数化消息** / Parameterized Messages
   - 使用参数而不是拼接字符串: `__('messages.welcome', ['name' => $user->name])`

---

## 扩展语言支持 / Adding More Languages

要添加新语言（如法语 fr）:
To add a new language (e.g., French fr):

1. 创建语言目录 / Create language directory:
   ```bash
   mkdir resources/lang/fr
   ```

2. 复制英文语言文件 / Copy English language files:
   ```bash
   cp resources/lang/en/*.php resources/lang/fr/
   ```

3. 翻译 `fr` 目录中的所有文件 / Translate all files in `fr` directory

4. 更新中间件和控制器 / Update middleware and controller:
   ```php
   // SetLocale.php
   $locale = $request->getPreferredLanguage(['en', 'zh', 'fr']) ?? config('app.locale');

   // LanguageController.php
   if (!in_array($locale, ['en', 'zh', 'fr'])) {
       abort(400);
   }
   ```

---

## 支持 / Support

如有问题，请参考Laravel官方文档:
For questions, refer to Laravel official documentation:
https://laravel.com/docs/8.x/localization

---

**最后更新 / Last Updated:** 2025-12-24
