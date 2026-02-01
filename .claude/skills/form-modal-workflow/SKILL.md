---
name: form-modal-workflow
description: 表单弹窗开发工作流指南，涵盖 form-modal 基类模板的转换规范和使用方法，提供 /gen-form-modal 命令导航
---

## Usage

### How to Invoke This Skill

```
/form-modal-workflow
```

### What This Skill Does

当调用此技能时，提供：
1. **form-modal 基类模板结构说明** — 所有可用 section 和基类提供的功能
2. **转换规范** — 将旧模式弹窗转换为基类模板的完整规则
3. **命令导航** — 快速访问 `/gen-form-modal`

### Common Use Cases

| 场景 | 操作 |
|------|------|
| 转换已有表单弹窗 | `/gen-form-modal resources/views/xxx/create.blade.php` |
| 新建表单弹窗 | `/gen-form-modal new {resource}` |
| 查看转换规范 | `/form-modal-workflow` |

### Quick Start

```
/gen-form-modal resources/views/holidays/create.blade.php
```

---

## 基类模板结构

**文件**：`resources/views/layouts/form-modal.blade.php`

### 可用 Section

| Section | 类型 | 说明 |
|---------|------|------|
| `modal_id` | 必填 | Modal 的 HTML id，如 `holidays-modal` |
| `modal_title` | 必填 | Modal 标题文字，如 `__('holidays.holidays_form')` |
| `form_id` | 必填 | Form 的 HTML id，如 `holidays-form` |
| `form_content` | 必填 | 表单字段内容（所有 `<div class="form-group">` 块） |
| `modal_size` | 可选 | Modal 大小 class（默认空，可选 `modal-form-sm`、`modal-form-lg`） |
| `hidden_fields` | 可选 | 隐藏字段（如 `<input type="hidden" id="id" name="id">`） |
| `footer_buttons` | 可选 | 自定义 footer 按钮（默认：Cancel + Save） |
| `form_js` | 可选 | 表单相关的 JavaScript |

### 基类提供的 HTML 结构

基类自动包含以下内容，子页面**不需要重复定义**：

- `<div class="modal fade modal-form">` — modal 容器
- `<div class="modal-dialog">` + `<div class="modal-content">` — modal 结构
- `<div class="modal-header">` — 含关闭按钮和标题
- `<div class="modal-body">` — 内容区域
- `<div class="alert alert-danger">` — 验证错误展示区
- `<form action="#" class="form-horizontal" autocomplete="off">` — 表单标签
- `@csrf` — CSRF token
- `<div class="modal-footer">` — 默认 footer 按钮

### 基类提供的 JS 工具函数

| 函数 | 说明 | 用法 |
|------|------|------|
| `toggleSection(sectionId)` | 切换表单分区折叠状态 | 用于可折叠表单区域 |
| `expandSection(sectionId)` | 展开表单分区 | 编程控制展开 |
| `collapseSection(sectionId)` | 折叠表单分区 | 编程控制折叠 |
| `showValidationErrors(errors, formId)` | 显示验证错误 | AJAX error 回调中使用 |
| `hideValidationErrors(formId)` | 隐藏验证错误 | 表单重置时使用 |
| `resetForm(formId)` | 重置表单（含 Select2） | 新建记录前调用 |
| `setButtonLoading(buttonId, loading, loadingText, normalText)` | 设置按钮加载状态 | AJAX 提交时使用 |
| `calculateAge(birthday)` | 根据生日计算年龄 | 患者信息表单 |
| `isValidEmail(email)` | 验证邮箱格式 | 表单前端验证 |
| `isValidPhone(phone)` | 验证手机号格式（中国手机） | 表单前端验证 |
| `parseChineseIdCard(idCard)` | 解析身份证提取生日和性别 | 患者信息表单 |

### 可用 CSS 类（来自 form-modal.css）

| CSS 类 | 说明 |
|--------|------|
| `.form-section` | 表单分区容器 |
| `.form-section-header` | 分区标题 |
| `.form-section-body` | 分区内容 |
| `.form-row` | 表单行 |
| `.required-asterisk` | 必填标记 |
| `.field-hint` | 字段提示 |
| `.validation-message` | 验证消息 |
| `.warning-box` | 警告框 |
| `.info-box` | 信息框 |
| `.conditional-fields` | 条件字段（配合 `.show`） |

### 可用组件

| 组件 | 用法 |
|------|------|
| `@include('components.form.section')` | 可折叠表单分区 |
| `@include('components.form.field')` | 表单字段包装器 |

---

## 转换规范

### 识别旧模式

以下特征表明弹窗使用旧模式，需要转换：

```blade
{{-- 旧模式标志 --}}
<div class="modal fade" id="xxx-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                <h4 class="modal-title"> 标题 </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <form action="#" id="xxx-form" autocomplete="off">
                    @csrf
                    ...
```

### 转换步骤

#### 1. 替换为 @extends

```blade
{{-- 旧：完整内联 HTML --}}
<div class="modal fade" id="xxx-modal" ...>...</div>

{{-- 新：继承基类 --}}
@extends('layouts.form-modal')
```

#### 2. 提取 section

```blade
@section('modal_id', 'xxx-modal')
@section('modal_title', __('xxx.form_title'))
@section('form_id', 'xxx-form')

@section('hidden_fields')
    <input type="hidden" id="id" name="id">
@endsection

@section('form_content')
    {{-- 所有 form-group 保持原样 --}}
@endsection
```

#### 3. 处理 footer 按钮

**基类默认** footer 的 onclick 是 `saveForm()`。如果旧模式使用其他函数名（如 `save_data()`），**必须**覆盖 footer：

```blade
@section('footer_buttons')
    <button type="button" class="btn btn-primary" id="btn-save" onclick="save_data()">{{ __('common.save_changes') }}</button>
    <button type="button" class="btn dark btn-outline" data-dismiss="modal">{{ __('common.close') }}</button>
@endsection
```

#### 4. 删除冗余

| 删除内容 | 原因 |
|---------|------|
| `<div class="modal fade">` 外层结构 | 基类已包含 |
| `<div class="modal-header">` 含关闭按钮和标题 | 基类已包含 |
| `<div class="modal-body">` | 基类已包含 |
| `<div class="alert alert-danger">` 含 `@foreach` 错误循环 | 基类已包含（JS 方式） |
| `<form action="#" id="xxx" autocomplete="off">` | 基类已包含 |
| `@csrf` | 基类已包含 |
| `</form>` 闭合标签 | 基类已包含 |
| `<div class="modal-footer">` | 基类已包含 |

---

## 核心原则

### 不影响任何事件

转换过程中，以下内容**绝不修改**：

1. **表单字段**：不改 `name`、`id`、`class`、`placeholder`
2. **字段结构**：不改 `<div class="form-group">` 内部结构
3. **onclick 绑定**：不改 footer 按钮的 `onclick` 函数
4. **Select2 配置**：不改选项、placeholder、数据源
5. **Datepicker 配置**：不改日期选择器初始化
6. **Blade 指令**：不改 `@foreach`、`@if`、`@isset` 等
7. **服务端数据**：不改 `$items`、`$categories` 等变量渲染
8. **表单脚本**：不改 `<script>` 中的任何逻辑，只是移入 `@section('form_js')`

### 唯一允许的结构变化

只移除基类已提供的外层 HTML 壳，内部表单内容完全保持原样。

---

## 特殊情况

### 带 Select2 的表单

Select2 的初始化代码通常在父页面（index.blade.php）的 JS 中，不在 create.blade.php 中。转换时只需保留 `<select>` 元素即可。

### 带 Datepicker 的表单

Datepicker 初始化同样在父页面 JS 中。保留 `<input>` 元素和其 `id`/`class`。

### 大尺寸 Modal

如旧模式使用 `<div class="modal-dialog modal-lg">`，添加：
```blade
@section('modal_size', 'modal-form-lg')
```

### 带表单脚本的弹窗

部分弹窗在 `</div>` 后有 `<script>` 标签：
```blade
@section('form_js')
<script>
    // 表单相关的 JS 逻辑
    $(function() {
        $('#datepicker').datepicker({...});
    });
</script>
@endsection
```

### 多个隐藏字段

```blade
@section('hidden_fields')
    <input type="hidden" id="id" name="id">
    <input type="hidden" id="patient_id" name="patient_id">
@endsection
```

---

## 已转换的页面参考

暂无已转换页面。使用 `/gen-form-modal` 命令转换后，可在此列表中添加。

---

## 关联命令

| 命令 | 说明 |
|------|------|
| `/gen-form-modal` | 转换或新建表单弹窗 |
| `/gen-list-page` | 转换或新建列表页面 |
| `/form-review` | UI 审查 |
| `/i18n-check` | 国际化检查 |
