---
name: laravel-Internationalization
description: Laravel 国际化开发规范，涵盖翻译函数、插件本地化、模块化翻译等核心规则，提供 /i18n-check、/i18n-fix、/i18n-add-key 命令导航
---

## Usage

### How to Invoke This Skill

```
/laravel-Internationalization
```

### What This Skill Does

当调用此技能时，提供：
1. **i18n 核心规范** — 翻译函数、文件结构、命名约定
2. **命令导航** — 快速访问 `/i18n-check`、`/i18n-fix`、`/i18n-add-key`
3. **插件本地化配置** — DataTables、Select2、Datepicker、FullCalendar

### Common Use Cases

| 场景 | 操作 |
|------|------|
| 检查硬编码文本 | `/i18n-check` |
| 修复 i18n 问题 | `/i18n-fix <文件路径>` |
| 添加翻译 key | `/i18n-add-key` |
| 查看 i18n 规范 | `/laravel-Internationalization` |

---

## 支持语言

- **zh-CN**（简体中文）— 主语言
- **en**（English）— 默认/回退

## 语言文件结构

```
resources/lang/
├── en/                     # 英文
│   ├── common.php          # 共享 UI 元素
│   ├── messages.php        # Controller 响应消息
│   ├── validation.php      # 验证规则
│   └── {module}.php        # 业务模块翻译
├── zh-CN/                  # 中文（镜像 en/ 结构）
└── modules/
    ├── doctor/zh-CN/       # Doctor 模块翻译
    ├── nurse/zh-CN/
    └── ...
```

## 翻译 Key 命名约定

| 类型 | 模式 | 示例 |
|------|------|------|
| 成功消息 | `module.action_successfully` | `invoices.invoice_created_successfully` |
| 错误消息 | `messages.error_description` | `messages.error_try_again` |
| 按钮文本 | `common.action` | `common.edit`、`common.delete` |
| 状态标签 | `common.status` | `common.active`、`common.inactive` |
| 对话框标题 | `common.dialog_type` | `common.alert`、`common.warning` |
| 验证消息 | `validation.custom.field.rule` | `validation.custom.name.required` |

---

## 核心规则

### PHP / Blade

```php
// Controller 响应消息
return response()->json([
    'message' => __('messages.record_created_successfully'),
    'status' => 1,
]);

// Blade 模板
{{ __('common.edit') }}
{{ __('patient.full_name') }}
{{ __('messages.welcome', ['name' => $user->name]) }}
```

### JavaScript（LanguageManager）

```javascript
// 初始化（每个使用 JS 翻译的页面必须）
LanguageManager.init({
    availableLocales: @json(config('app.available_locales')),
    currentLocale: '{{ app()->getLocale() }}',
    defaultLocale: '{{ config('app.fallback_locale', 'en') }}'
});
LanguageManager.loadAllFromPHP({
    'common': @json(__('common')),
    'messages': @json(__('messages'))
});

// 使用
LanguageManager.trans('common.save');
LanguageManager.trans('messages.welcome', { name: userName });
```

### 插件本地化（必须配置 language 参数）

```javascript
// DataTables
$('#table').DataTable({
    language: LanguageManager.getDataTableLang(),
    // ...
});

// Select2
$('.select2').select2({
    language: '{{ app()->getLocale() }}',
    placeholder: "{{ __('common.select_option') }}"
});

// Datepicker
$('.datepicker').datepicker({
    language: '{{ app()->getLocale() }}',
    autoclose: true
});

// FullCalendar
$('#calendar').fullCalendar({
    locale: '{{ app()->getLocale() }}'
});
```

### 模块翻译

```blade
{{-- 模块翻译使用 :: 命名空间 --}}
{{ __('doctor::appointments.title') }}
{{ __('nurse::tasks.pending') }}
```

---

## 禁止模式

- **不要硬编码用户可见文本** — 必须使用 `__()`、`trans()`、`@lang()`
- **不要遗漏插件 language 参数** — DataTables、Select2、Datepicker、FullCalendar 都必须配置
- **不要只加一种语言** — `en/` 和 `zh-CN/` 必须同步添加
- **不要在 JS 中硬编码中文** — 使用 `LanguageManager.trans()` 或 Blade 内联 `{{ __() }}`

---

## 关联命令

| 命令 | 说明 |
|------|------|
| `/i18n-check` | 扫描代码中的 i18n 问题 |
| `/i18n-fix` | 修复指定文件的 i18n 问题 |
| `/i18n-add-key` | 添加翻译 key（同时更新 en + zh-CN） |

---

## 详细参考

完整的代码示例、验证器国际化、邮件模板、语言切换实现等详细内容见：
- `resources/lang/zh-CN/common.php` — 共享 UI 元素 key 列表
- `resources/lang/zh-CN/messages.php` — Controller 响应消息 key 列表
- `resources/lang/zh-CN/validation.php` — 验证规则和属性翻译
