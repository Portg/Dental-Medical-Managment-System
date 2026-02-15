---
description: Check Laravel files for internationalization issues
---

# Laravel Internationalization Check Command

扫描代码中的国际化问题，包括硬编码文本和缺少 language 配置的前端插件。

## Task

对代码库进行全面的 i18n 审计，报告需要国际化的硬编码文本。

## 检查 Pattern

### 1. Controller 消息响应
- `messageResponse("Hardcoded text"` → 应使用 `messageResponse(__('key'))`
- `response()->json(['message' => 'Hardcoded'` → 应使用 `__('key')`

### 2. DataTables 按钮/状态文本
- `>Edit</a>` / `>Delete</a>` → 应使用 `__('common.edit')` / `__('common.delete')`
- `>Active</span>` / `>Inactive</span>` → 应使用 `__('common.active')` / `__('common.inactive')`

### 3. JavaScript 对话框文本
- `swal("Alert!"` → 应使用 `{{ __('common.alert') }}`
- `confirm("Are you sure` → 应使用翻译文本

### 4. SMS/Email 模板
- `$message = 'Hello,` → 应使用 `__('sms.key')`
- 邮件主题和正文硬编码

### 5. Validator 自定义消息
- `'field.required' => 'The field is required'` → 去掉自定义消息让 Laravel 自动翻译，或使用 `__('validation.custom.field.required')`

### 6. 前端插件缺少 language 配置
- DataTables 缺少 `language: LanguageManager.getDataTableLang()`
- Select2 缺少 `language: '{{ app()->getLocale() }}'`
- Datepicker 缺少 `language: '{{ app()->getLocale() }}'`
- FullCalendar 缺少 `locale: '{{ app()->getLocale() }}'`

### 7. Blade 表头硬编码
- `<th>Name</th>` → 应使用 `<th>{{ __('common.name') }}</th>`

## 搜索目录

- `app/Http/Controllers/`
- `App/Http/Controllers/`
- `Modules/*/Http/Controllers/`
- `resources/views/**/*.blade.php`
- `Modules/*/Resources/views/**/*.blade.php`

## 搜索命令参考

```bash
# DataTables 缺少 language
grep -r "\.DataTable\s*(" --include="*.blade.php" | grep -v "language"

# Select2 缺少 language
grep -r "\.select2\s*(" --include="*.blade.php" | grep -v "language"

# Datepicker 缺少 language
grep -r "\.datepicker\s*(" --include="*.blade.php" | grep -v "language"

# 硬编码表头
grep -r "<th>[A-Z]" --include="*.blade.php" | grep -v "__("

# 硬编码 JSON 消息
grep -r "'message' => '" --include="*.php" | grep -v "__("
```

## 输出格式

对每个发现的问题，报告：
1. 文件路径和行号
2. 发现的硬编码文本或缺失配置
3. 使用翻译函数的修复建议

## 后续操作

发现问题后，可使用：
- `/i18n-fix <文件路径>` — 修复指定文件
- `/i18n-add-key` — 添加缺失的翻译 key

完整 i18n 规范见 `/laravel-Internationalization`。
