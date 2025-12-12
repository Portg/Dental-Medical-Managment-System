# 国际化使用指南 / Internationalization Guide

## 概述 / Overview

本项目已经完成了全面的国际化改造，支持中英文双语切换。

This project has been fully internationalized and supports bilingual switching between Chinese and English.

## 已完成的工作 / Completed Work

### 1. 创建的语言文件 / Created Language Files

#### 中文语言文件 (resources/lang/zh-CN/)
- `common.php` - 通用翻译（按钮、操作、状态等）
- `auth.php` - 认证相关翻译
- `appointments.php` - 预约管理翻译
- `invoices.php` - 发票管理翻译
- `patients.php` - 患者管理翻译
- `reports.php` - 报表相关翻译
- `modules.php` - 各功能模块翻译

#### 英文语言文件 (resources/lang/en/)
- `common.php` - Common translations (buttons, actions, status, etc.)
- `auth.php` - Authentication translations
- `appointments.php` - Appointment management translations
- `invoices.php` - Invoice management translations
- `patients.php` - Patient management translations
- `reports.php` - Report translations
- `modules.php` - Module translations

### 2. 修改的视图文件 / Modified View Files

总共修改了 **120个文件**，包括：
- 认证页面（登录、密码重置等）
- 仪表盘和主页
- 预约管理
- 发票和付款
- 患者管理
- 报表和分析
- HR模块（工资单、请假申请）
- 财务模块（支出、账户）
- 沟通模块（短信、生日祝福）
- 以及更多...

Total **120 files** modified, including:
- Authentication pages (login, password reset, etc.)
- Dashboard and home page
- Appointments management
- Invoices and payments
- Patient management
- Reports and analytics
- HR modules (payslips, leave requests)
- Financial modules (expenses, accounts)
- Communication modules (SMS, birthday wishes)
- And many more...

## 如何使用 / How to Use

### 1. 切换语言 / Switch Language

在 `config/app.php` 中修改 `locale` 配置：

```php
// 使用中文 / Use Chinese
'locale' => 'zh-CN',

// 使用英文 / Use English
'locale' => 'en',
```

### 2. 在代码中使用翻译 / Use Translations in Code

#### 在 Blade 模板中 / In Blade Templates

```blade
<!-- 使用双括号语法 / Using double curly braces -->
{{ __('common.save') }}
{{ __('auth.login') }}
{{ __('appointments.appointment_date') }}

<!-- 使用 @lang 指令 / Using @lang directive -->
@lang('common.add_new')
@lang('invoices.generate_invoice')
```

#### 在 PHP 代码中 / In PHP Code

```php
// 使用 __ 函数 / Using __ function
$message = __('common.operation_success');

// 使用 trans 函数 / Using trans function
$message = trans('appointments.appointment_created');
```

### 3. 动态语言切换 / Dynamic Language Switching

如果需要让用户在运行时切换语言，可以添加以下代码：

To allow users to switch languages at runtime, add the following code:

```php
// 在控制器或中间件中 / In Controller or Middleware
App::setLocale('zh-CN'); // 切换到中文 / Switch to Chinese
App::setLocale('en');    // 切换到英文 / Switch to English

// 将语言偏好保存到 session 中 / Save language preference to session
session(['locale' => 'zh-CN']);
```

### 4. 添加新的翻译 / Add New Translations

如果需要添加新的翻译键，请在相应的语言文件中添加：

To add new translation keys, add them to the appropriate language files:

```php
// resources/lang/zh-CN/common.php
return [
    // ... 现有的翻译
    'new_key' => '新的翻译',
];

// resources/lang/en/common.php
return [
    // ... existing translations
    'new_key' => 'New Translation',
];
```

## 翻译文件结构 / Translation File Structure

- **common.php**: 通用的按钮、操作、状态、时间周期等
  - Common buttons, actions, status, time periods, etc.

- **auth.php**: 登录、注册、密码相关
  - Login, registration, password-related

- **appointments.php**: 预约管理相关
  - Appointment management

- **invoices.php**: 发票、收据、付款相关
  - Invoices, receipts, payments

- **patients.php**: 患者、病历、处方相关
  - Patients, medical records, prescriptions

- **reports.php**: 各类报表
  - Various reports

- **modules.php**: 其他功能模块（用户、保险、财务、HR等）
  - Other modules (users, insurance, finance, HR, etc.)

## 注意事项 / Notes

1. 所有的硬编码文本都已替换为翻译函数调用
   - All hardcoded text has been replaced with translation function calls

2. 某些动态生成的内容可能需要单独处理
   - Some dynamically generated content may need to be handled separately

3. JavaScript 文件中的文本暂未处理，如需要请使用 Laravel 的本地化 JavaScript 功能
   - Text in JavaScript files has not been processed yet. Use Laravel's JavaScript localization if needed

4. 如果发现遗漏的翻译，请在相应的语言文件中添加
   - If you find missing translations, please add them to the appropriate language files

## 建议的后续工作 / Recommended Follow-up Work

1. 在应用中添加语言切换器界面
   - Add a language switcher UI in the application

2. 处理 JavaScript 文件中的文本国际化
   - Handle internationalization for JavaScript files

3. 添加更多语言支持（如需要）
   - Add more language support (if needed)

4. 测试所有页面确保翻译正确显示
   - Test all pages to ensure translations display correctly

---

**提交信息 / Commit**: `Internationalize all view files with Chinese and English translations`

**修改文件数 / Files Changed**: 120

**插入行数 / Insertions**: 1015

**删除行数 / Deletions**: 504
