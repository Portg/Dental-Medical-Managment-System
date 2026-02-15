---
description: Fix internationalization issues in Laravel files
---

# Laravel Internationalization Fix Command

修复指定文件中的国际化问题，包括硬编码文本和前端插件配置。

## Task

分析并修复给定文件中所有需要国际化的硬编码文本。

## 输入

用户可提供：
- 具体文件路径：`/i18n-fix app/Http/Controllers/PatientController.php`
- glob 模式：`/i18n-fix app/Http/Controllers/*Controller.php`
- 模块名：`/i18n-fix Doctor`
- 插件类型：`/i18n-fix DataTables`

## 修复流程

### Step 1: 分析文件
读取文件，识别所有硬编码文本和缺失的插件 language 配置。

### Step 2: 确定翻译 Key
- 优先使用已有的语言文件中的 key
- 新 key 遵循命名约定：`module.feature.message_type`
- 通用 UI 元素用 `common.*`

### Step 3: 执行修复

**Controller 消息：**
```php
// Before
return response()->json(['message' => 'Record saved successfully', 'status' => true]);
// After
return response()->json(['message' => __('messages.record_saved_successfully'), 'status' => true]);
```

**DataTables 按钮：**
```php
// Before
$btn = '<a href="#" class="btn btn-primary">Edit</a>';
// After
$btn = '<a href="#" class="btn btn-primary">' . __('common.edit') . '</a>';
```

**Validator 自定义消息（简化优先）：**
```php
// Before
Validator::make($request->all(), ['name' => 'required'], ['name.required' => 'The name field is required']);
// After（去掉自定义消息，让 Laravel 自动翻译）
Validator::make($request->all(), ['name' => 'required']);
```

**插件 language 配置：**
```javascript
// DataTables 加 language
$('#table').DataTable({ language: LanguageManager.getDataTableLang(), ... });

// Select2 加 language
$('.select2').select2({ language: '{{ app()->getLocale() }}', placeholder: "{{ __('common.select_option') }}" });

// Datepicker 加 language
$('.datepicker').datepicker({ language: '{{ app()->getLocale() }}', ... });

// FullCalendar 加 locale
$('#calendar').fullCalendar({ locale: '{{ app()->getLocale() }}', ... });
```

### Step 4: 更新语言文件
新增的翻译 key 同时添加到：
- `resources/lang/en/*.php`
- `resources/lang/zh-CN/*.php`

### Step 5: 验证
对所有修改的文件运行 `php -l` 确认无语法错误。

## 输出

报告所有改动：
1. 修改的文件列表
2. 每处改动的 before/after 对比
3. 新增的翻译 key
4. 语法检查结果

## 翻译 Key 命名约定

| 类型 | 模式 | 示例 |
|------|------|------|
| 成功消息 | `module.action_successfully` | `invoices.invoice_created_successfully` |
| 错误消息 | `messages.error_description` | `messages.error_try_again` |
| 按钮文本 | `common.action` | `common.edit`、`common.delete` |
| 状态标签 | `common.status` | `common.active`、`common.inactive` |
| 对话框标题 | `common.dialog_type` | `common.alert`、`common.warning` |

完整 i18n 规范见 `/laravel-Internationalization`。
