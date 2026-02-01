---
name: list-page-workflow
description: 列表页面开发工作流指南，涵盖 list-page 基类模板的转换规范和使用方法，提供 /gen-list-page 命令导航
---

## Usage

### How to Invoke This Skill

```
/list-page-workflow
```

### What This Skill Does

当调用此技能时，提供：
1. **list-page 基类模板结构说明** — 所有可用 section 和基类提供的功能
2. **转换规范** — 将旧模式页面转换为基类模板的完整规则
3. **命令导航** — 快速访问 `/gen-list-page`

### Common Use Cases

| 场景 | 操作 |
|------|------|
| 转换已有列表页 | `/gen-list-page resources/views/xxx/index.blade.php` |
| 新建列表页 | `/gen-list-page new {resource}` |
| 查看转换规范 | `/list-page-workflow` |

### Quick Start

```
/gen-list-page resources/views/leave_requests/index.blade.php
```

---

## 基类模板结构

**文件**：`resources/views/layouts/list-page.blade.php`

**继承链**：`list-page.blade.php` → `FunctionsHelper::navigation()` → `layouts/metronic.blade.php`

### 可用 Section

| Section | 类型 | 说明 |
|---------|------|------|
| `page_title` | 必填 | 页面标题，如 `__('leaves.leave_requests')` |
| `table_id` | 必填 | DataTable 的 HTML id，如 `leave-requests_table` |
| `table_headers` | 必填 | `<th>` 列表，数量必须与 JS columns 一致 |
| `header_actions` | 可选 | 标题右侧按钮（新建、导出等） |
| `filter_area` | 可选 | 完全自定义筛选区域（与 filter_primary 二选一） |
| `filter_primary` | 可选 | 主筛选控件（自动带搜索/重置按钮） |
| `filter_advanced` | 可选 | 高级筛选（可折叠） |
| `modals` | 可选 | modal 对话框（含 `@include` 的 partial） |
| `page_js` | 可选 | 页面 JS（DataTable 初始化 + 事件函数） |
| `page_css` | 可选 | 页面额外 CSS |
| `empty_icon` | 可选 | 空状态图标 class |
| `empty_title` | 可选 | 空状态标题 |
| `empty_desc` | 可选 | 空状态描述 |
| `empty_action` | 可选 | 空状态操作按钮 |

### 基类提供的资源

#### 已包含的 CSS/JS（子页面不需要重复引入）

- `css/list-page.css` — 列表页统一样式
- `css/form-modal.css` — 表单弹窗统一样式
- `layouts/page_loader` — 加载遮罩 CSS
- `page_loader.js` — 加载遮罩 JS
- `DatesHelper.js` — 日期工具

#### 已包含的 HTML 结构

- `<div class="portlet light bordered">` — 外层容器
- `<div class="loading">` — 加载提示
- `<div id="emptyState">` — 空状态容器
- `<table>` 结构 — 含 `<thead>` 和 `<tbody>`

#### 已定义的全局 JS 变量和函数

| 变量/函数 | 说明 | 子页面操作 |
|-----------|------|-----------|
| `var dataTable` | DataTable 实例 | **必须赋值**：`dataTable = $('#xxx').DataTable({...})` |
| `setupEmptyStateHandler()` | 空状态自动切换 | **必须调用**：在 DataTable 初始化后 |
| `doSearch()` | 重新查询（调用 `dataTable.draw(true)`） | 基类已实现，可直接使用 |
| `clearFilters()` | 清空筛选条件 | 基类已实现，可自定义 `clearCustomFilters()` 扩展 |
| `debounce(func, wait)` | 防抖函数 | 基类已实现，可直接使用 |
| `alert_dialog(message, status)` | swal 弹窗 + 成功后刷新表格 | 基类已实现，可覆盖 |
| `createRecord()` | 新建记录（占位） | 子页面**覆盖实现** |
| `editRecord(id)` | 编辑记录（占位） | 子页面**覆盖实现** |
| `deleteRecord(id)` | 删除记录（占位） | 子页面**覆盖实现** |
| `exportData()` | 导出数据（占位） | 子页面按需覆盖 |

---

## 转换规范

### 识别旧模式

以下特征表明页面使用旧模式，需要转换：

```blade
{{-- 旧模式标志 --}}
@extends(\App\Http\Helper\FunctionsHelper::navigation())   {{-- 或 layouts.app --}}
@section('content')
@section('css')
    @include('layouts.page_loader')
@endsection
<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            ...
```

### 转换步骤

#### 1. 替换 @extends

```blade
{{-- 旧 --}}
@extends(\App\Http\Helper\FunctionsHelper::navigation())
@extends('layouts.app')

{{-- 新 --}}
@extends('layouts.list-page')
```

#### 2. 提取 section

```blade
@section('page_title', __('resource.title'))
@section('table_id', 'resource-table')

@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createRecord()">
        {{ __('common.add_new') }}
    </button>
@endsection

@section('table_headers')
    <th>...</th>
@endsection

@section('modals')
@include('resource.create')
@endsection
```

#### 3. JS 标准化

```javascript
// page_js section 中：

$(function () {
    // 1. 首先加载翻译
    LanguageManager.loadAllFromPHP({
        'resource': @json(__('resource')),
        'common': @json(__('common'))
    });

    // 2. 初始化 DataTable（赋值给基类全局变量）
    dataTable = $('#resource-table').DataTable({
        processing: true,
        serverSide: true,
        language: LanguageManager.getDataTableLang(),
        ajax: "{{ url('resource') }}",
        columns: [...]
    });

    // 3. 启用空状态
    setupEmptyStateHandler();
});

// 4. 覆盖基类占位函数（保持原有业务逻辑不变）
function createRecord() { ... }
function editRecord(id) { ... }
function deleteRecord(id) { ... }
```

#### 4. 删除冗余

| 删除内容 | 原因 |
|---------|------|
| `@section('css') @include('layouts.page_loader') @endsection` | 基类已包含 |
| `<script src="page_loader.js">` | 基类已包含 |
| `<script src="DatesHelper.js">` | 基类已包含 |
| `<div class="loading">...</div>` | 基类已包含 |
| `<div class="portlet light bordered">` 整层结构 | 基类已包含 |
| `<div class="table-toolbar">` | 使用 `header_actions` |
| `session()->has('success')` 提示 | 使用 `alert_dialog()` |
| `destroy: true` | 不需要 |
| `dom: 'Bfrtip'` + 空 `buttons: { buttons: [] }` | 不需要 |
| 自定义 `debounce()` | 基类已提供 |
| 与基类相同的 `alert_dialog()` | 基类已提供 |

---

## 核心原则

### 不影响任何事件

转换过程中，以下内容**绝不修改**：

1. **函数签名**：不改函数名、不改参数
2. **AJAX 逻辑**：不改 url 构造方式（仅把相对路径改为 `{{ url() }}`）、不改 data、不改 success/error 回调
3. **表单处理**：不改 `serialize()`、不改表单 reset 逻辑
4. **Modal 交互**：不改 `modal('show')`/`modal('hide')` 时机
5. **第三方插件**：不改 `select2`、`swal`、`toastr`、`$.LoadingOverlay` 调用
6. **业务函数**：不改 `loadLeaveTypes`、`loadPendingCount` 等自定义函数

### 唯一允许的变量名修改

| 旧变量 | 新变量 | 原因 |
|--------|--------|------|
| `var table` | `dataTable` | 基类全局变量名 |
| `oTable` | `dataTable` | 同上 |

修改变量名后，必须全文替换所有引用（如 `table.ajax.reload()` → `dataTable.ajax.reload()`）。

---

## 特殊页面类型

### 审批列表页（无新建按钮）

```blade
@section('header_actions')
    {{-- 审批页不需要新建按钮 --}}
    <a href="{{ url('resource') }}" class="btn btn-default">
        {{ __('resource.back_to_list') }}
    </a>
@endsection
```

### 带筛选的列表页

```blade
@section('filter_area')
    <div class="row filter-row">
        <div class="col-md-3">
            <div class="form-group">
                <label>{{ __('common.start_date') }}</label>
                <input type="text" class="form-control datepicker" id="start_date">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label>{{ __('common.status') }}</label>
                <select class="form-control" id="status_filter">
                    <option value="">{{ __('common.all') }}</option>
                </select>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                <label>&nbsp;</label>
                <button class="btn btn-primary btn-block" onclick="doSearch()">
                    {{ __('common.filter') }}
                </button>
            </div>
        </div>
    </div>
@endsection
```

在 DataTable ajax 中传递筛选参数：
```javascript
ajax: {
    url: "{{ url('resource') }}",
    data: function (d) {
        d.start_date = $('#start_date').val();
        d.status = $('#status_filter').val();
    }
}
```

### Tab 页面（非 DataTable）

如 `charts_of_accounts/index.blade.php`，不使用 DataTable 的页面**不适用**此基类。这类页面继续使用 `FunctionsHelper::navigation()`。

---

## 已转换的页面参考

| 页面 | 文件 |
|------|------|
| 临床服务 | `resources/views/clinical_services/index.blade.php` |
| 退费列表 | `resources/views/refunds/index.blade.php` |
| 待审批退费 | `resources/views/refunds/pending_approvals.blade.php` |
| 待审批折扣 | `resources/views/invoices/pending_discount_approvals.blade.php` |
| 优惠券 | `resources/views/coupons/index.blade.php` |
| 请假申请 | `resources/views/leave_requests/index.blade.php` |

遇到类似转换需求时，可参考这些已完成的页面。

---

## Action Column 标准化

所有列表页操作列统一使用 `ActionColumnHelper`（`app/Http/Helper/ActionColumnHelper.php`）。

### 基本用法

```php
use App\Http\Helper\ActionColumnHelper;

// Controller 中：
->addColumn('action', function ($row) {
    return ActionColumnHelper::make($row->id)
        ->primary('edit')          // 主按钮（直接显示）
        ->add('delete')            // 下拉菜单项
        ->render();
})
->rawColumns(['action'])
```

### API 方法

| 方法 | 说明 |
|------|------|
| `::make($id)` | 创建实例，传入记录 ID |
| `->primary($name, $label?, $href?, $onclick?)` | 设置主按钮 |
| `->primaryIf($condition, ...)` | 条件设置主按钮 |
| `->add($name, $label?, $href?, $onclick?)` | 添加下拉项 |
| `->addIf($condition, ...)` | 条件添加下拉项 |
| `->addRaw($html)` | 添加原始 HTML 下拉项 |
| `->render()` | 渲染 HTML |

### 预设操作

`edit` → `editRecord(id)`、`delete` → `deleteRecord(id)`、`view` → `viewRecord(id)`

使用预设时只需传名称：`->primary('edit')`、`->add('delete')`

自定义操作：`->add('preview', __('templates.preview'), '#', 'previewTemplate')`

### 渲染规则

| 场景 | 输出 |
|------|------|
| 仅 primary | 单按钮 |
| primary + items | 分裂按钮组（主按钮 + ▼ 下拉） |
| 仅 items（无 primary） | 纯下拉（"操作" 按钮） |

### 已迁移页面

| 页面 | 模式 | 文件 |
|------|------|------|
| 假日管理 | A→Helper | `HolidayController` + `holidays/index.blade.php` |
| 医疗模板 | B→Helper | `MedicalTemplateController` + `medical_templates/index.blade.php` |

---

## 关联命令

| 命令 | 说明 |
|------|------|
| `/gen-list-page` | 转换或新建列表页面 |
| `/form-review` | UI 审查 |
| `/i18n-check` | 国际化检查 |
