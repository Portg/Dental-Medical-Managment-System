# Convert / Generate List Page Command

将已有的列表页面转换为 `layouts.list-page` 基类模板，或基于基类模板生成新列表页。
**核心原则：不影响任何已有事件和业务逻辑。**

## 输入格式

```
/gen-list-page                                                → 交互式：询问文件路径
/gen-list-page resources/views/leave_requests/index.blade.php → 转换已有页面
/gen-list-page new appointments                               → 生成新页面
```

若 `$ARGUMENTS` 为空，进入交互式引导，询问：
1. 操作类型（convert 转换 / new 新建）
2. 文件路径或资源名
3. 确认转换范围

---

## 转换规则（convert 模式）

### 第一步：读取分析

1. **读取目标文件**：完整读取现有 blade 文件
2. **读取基类模板**：读取 `resources/views/layouts/list-page.blade.php` 了解可用 section
3. **分析现有结构**：
   - 识别当前 `@extends` 目标
   - 提取页面标题
   - 提取 table id
   - 提取表头 `<th>` 列表
   - 提取 header 操作按钮
   - 提取筛选区域
   - 提取 modal 对话框（含 `@include` 的 modal partial）
   - 提取所有 JS 函数及其完整实现
   - 提取 DataTable 配置（columns、ajax url 等）

### 第二步：映射到 section

| 原始内容 | 目标 section |
|---------|-------------|
| 页面标题文字 | `@section('page_title', __('xxx.title'))` |
| table 的 id 属性 | `@section('table_id', 'xxx-table')` |
| 新建/导出等按钮 | `@section('header_actions')` |
| 筛选表单（日期、状态、搜索框等） | `@section('filter_area')` 或 `@section('filter_primary')` |
| `<th>` 列表 | `@section('table_headers')` |
| modal 对话框 | `@section('modals')` |
| 所有 `<script>` 内容 | `@section('page_js')` |
| 额外 CSS | `@section('page_css')` |

### 第三步：JS 标准化

在 `@section('page_js')` 中，按以下规则调整：

#### 必须修改

| 原始代码 | 修改为 | 原因 |
|---------|--------|------|
| `var table = ...DataTable({` | `dataTable = ...DataTable({` | 使用基类全局变量 |
| `table.ajax.reload()` / `table.draw()` | `dataTable.ajax.reload()` / `dataTable.draw()` | 同上 |
| `oTable.fnDraw(false)` | `dataTable.draw(false)` | 同上 |
| 无 `LanguageManager.loadAllFromPHP()` | 添加到 `$(function(){ ... })` 开头 | 基类依赖 |
| 无 `setupEmptyStateHandler()` | 在 DataTable 初始化之后调用 | 空状态展示 |
| `destroy: true` | 删除 | 基类不需要 |
| `dom: 'Bfrtip'` + 空 `buttons` | 删除 | 基类不需要 |
| 相对 URL `"leave-requests/"` | `"{{ url('/leave-requests') }}/"` | URL 规范化 |

#### 禁止修改（保持原样）

- 所有 `function` 声明和实现（`createRecord`、`editRecord`、`deleteRecord`、`save_data`、`save_new_record`、`update_record` 等）
- AJAX 请求的 `success`/`error` 回调逻辑
- 表单序列化方式（`$('#form').serialize()`）
- modal 的 show/hide 逻辑
- `swal()` 确认对话框的完整配置
- `$.LoadingOverlay()` 调用
- `select2` 初始化
- 自定义业务函数（如 `loadLeaveTypes`、`loadPendingCount` 等）
- `toastr` 通知调用

#### 按需调整

| 情况 | 处理方式 |
|------|---------|
| 页面自定义 `alert_dialog()` 与基类一致 | 删除（基类已提供） |
| 页面自定义 `alert_dialog()` 有额外逻辑 | 保留并覆盖 |
| `deleteRecord` 缺少 `cancelButtonText` | 添加 `cancelButtonText: "{{ __('common.cancel') }}"` |
| 硬编码 Blade 翻译 `'{{ __('xxx') }}'` 在 JS 中 | 可改为 `LanguageManager.trans('xxx')`（非强制） |

### 第四步：移除冗余

转换后，以下内容由基类提供，子页面中应**删除**：

- `@extends(\App\Http\Helper\FunctionsHelper::navigation())` → `@extends('layouts.list-page')`
- `@section('css') @include('layouts.page_loader') @endsection` → 基类已包含
- `<script src="page_loader.js">` → 基类已包含
- `<script src="DatesHelper.js">` → 基类已包含
- `<div class="loading">...</div>` → 基类已包含
- `<div class="portlet light bordered">` 外层结构 → 基类已包含
- `<div class="table-toolbar">` 工具栏 → 使用 `header_actions` section
- `session()->has('success')` 提示 → 用 `alert_dialog()` 替代
- 自定义 `debounce()` 函数 → 基类已提供
- 自定义 `clearFilters()` 函数（如果逻辑与基类一致）→ 基类已提供
- 自定义 `doSearch()` 函数 → 基类已提供

---

## 新建模式（new）

### 输入
```
/gen-list-page new {resource_name}
```

### 生成文件

#### `resources/views/{resource}/index.blade.php`

```blade
@extends('layouts.list-page')

@section('page_title', __('{{resource}}.title'))

@section('table_id', '{{resource}}-table')

@section('header_actions')
    <button type="button" class="btn btn-primary" onclick="createRecord()">
        {{ __('common.add_new') }}
    </button>
@endsection

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('{{resource}}.name') }}</th>
    <th>{{ __('common.status') }}</th>
    <th>{{ __('common.action') }}</th>
@endsection

@section('modals')
@include('{{resource}}.create')
@endsection

@section('page_js')
<script type="text/javascript">
    $(function () {
        LanguageManager.loadAllFromPHP({
            '{{resource}}': @json(__('{{resource}}')),
            'common': @json(__('common'))
        });

        dataTable = $('#{{resource}}-table').DataTable({
            processing: true,
            serverSide: true,
            language: LanguageManager.getDataTableLang(),
            ajax: "{{ url('{{resource}}') }}",
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'name', name: 'name'},
                {data: 'status', name: 'status'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });

        setupEmptyStateHandler();
    });

    function createRecord() {
        $("#{{resource}}-form")[0].reset();
        $('#id').val('');
        $('#btn-save').attr('disabled', false);
        $('#btn-save').text('{{ __("common.save_record") }}');
        $('#{{resource}}-modal').modal('show');
    }

    function save_data() {
        var id = $('#id').val();
        if (id === "") {
            save_new_record();
        } else {
            update_record();
        }
    }

    function save_new_record() {
        $.LoadingOverlay("show");
        $('#btn-save').attr('disabled', true);
        $('#btn-save').text('{{ __("common.processing") }}');
        $('.alert-danger').hide().empty();

        $.ajax({
            type: 'POST',
            data: $('#{{resource}}-form').serialize(),
            url: "{{ url('/{{resource}}') }}",
            success: function (data) {
                $('#{{resource}}-modal').modal('hide');
                $.LoadingOverlay("hide");
                if (data.status) {
                    alert_dialog(data.message, "success");
                } else {
                    alert_dialog(data.message, "danger");
                }
            },
            error: function (request, status, error) {
                $.LoadingOverlay("hide");
                $('#btn-save').attr('disabled', false);
                $('#btn-save').text('{{ __("common.save_record") }}');
                json = $.parseJSON(request.responseText);
                $.each(json.errors, function (key, value) {
                    $('.alert-danger').show();
                    $('.alert-danger').append('<p>' + value + '</p>');
                });
            }
        });
    }

    function editRecord(id) {
        $.LoadingOverlay("show");
        $("#{{resource}}-form")[0].reset();
        $('#id').val('');
        $('#btn-save').attr('disabled', false);

        $.ajax({
            type: 'get',
            url: "{{ url('/{{resource}}') }}/" + id + "/edit",
            success: function (data) {
                $('#id').val(id);
                // TODO: 填充表单字段
                $.LoadingOverlay("hide");
                $('#btn-save').text('{{ __("common.update_record") }}');
                $('#{{resource}}-modal').modal('show');
            },
            error: function (request, status, error) {
                $.LoadingOverlay("hide");
            }
        });
    }

    function update_record() {
        $.LoadingOverlay("show");
        $('#btn-save').attr('disabled', true);
        $('#btn-save').text('{{ __("common.updating") }}');
        $('.alert-danger').hide().empty();

        $.ajax({
            type: 'PUT',
            data: $('#{{resource}}-form').serialize(),
            url: "{{ url('/{{resource}}') }}/" + $('#id').val(),
            success: function (data) {
                $('#{{resource}}-modal').modal('hide');
                $.LoadingOverlay("hide");
                if (data.status) {
                    alert_dialog(data.message, "success");
                } else {
                    alert_dialog(data.message, "danger");
                }
            },
            error: function (request, status, error) {
                $.LoadingOverlay("hide");
                $('#btn-save').attr('disabled', false);
                $('#btn-save').text('{{ __("common.update_record") }}');
                json = $.parseJSON(request.responseText);
                $.each(json.errors, function (key, value) {
                    $('.alert-danger').show();
                    $('.alert-danger').append('<p>' + value + '</p>');
                });
            }
        });
    }

    function deleteRecord(id) {
        swal({
            title: "{{ __('common.are_you_sure') }}",
            text: "{{ __('{{resource}}.confirm_delete') }}",
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: "{{ __('common.yes_delete_it') }}",
            cancelButtonText: "{{ __('common.cancel') }}",
            closeOnConfirm: false
        }, function () {
            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
            $.LoadingOverlay("show");
            $.ajax({
                type: 'delete',
                data: {_token: CSRF_TOKEN},
                url: "{{ url('/{{resource}}') }}/" + id,
                success: function (data) {
                    if (data.status) {
                        alert_dialog(data.message, "success");
                    } else {
                        alert_dialog(data.message, "danger");
                    }
                    $.LoadingOverlay("hide");
                },
                error: function (request, status, error) {
                    $.LoadingOverlay("hide");
                }
            });
        });
    }
</script>
@endsection
```

---

## Action Column 标准化

所有列表页的操作列统一使用 `ActionColumnHelper`，生成分裂按钮组（主按钮 + 下拉菜单）。

### Controller 端

```php
use App\Http\Helper\ActionColumnHelper;

// 简单场景：edit 为主按钮，delete 在下拉
->addColumn('action', function ($row) {
    return ActionColumnHelper::make($row->id)
        ->primary('edit')
        ->add('delete')
        ->render();
})
->rawColumns(['action'])

// 复杂场景：自定义 label、onclick 函数
->addColumn('action', function ($row) {
    return ActionColumnHelper::make($row->id)
        ->primary('preview', __('templates.preview'), '#', 'previewTemplate')
        ->add('edit', __('common.edit'), '#', 'editTemplate')
        ->add('delete', __('common.delete'), '#', 'deleteTemplate')
        ->render();
})
->rawColumns(['action'])

// 条件操作
->addColumn('action', function ($row) {
    return ActionColumnHelper::make($row->id)
        ->primaryIf($row->deleted_at == null, 'edit')
        ->addIf($row->status == 'Pending', 'approve', __('common.approve'), '#', 'approveRecord')
        ->add('delete')
        ->render();
})
->rawColumns(['action'])

// 插入已有的原始 HTML
->addColumn('action', function ($row) {
    return ActionColumnHelper::make($row->id)
        ->primary('edit')
        ->addRaw($existingHtml)
        ->add('delete')
        ->render();
})
->rawColumns(['action'])
```

### Blade 端

操作列统一为单个 `action` 列：

```blade
@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('resource.name') }}</th>
    <th>{{ __('common.action') }}</th>
@endsection
```

JS columns 配置：
```javascript
{data: 'action', name: 'action', orderable: false, searchable: false}
```

### 迁移旧模式

**模式 A**（分开的 editBtn + deleteBtn）：
1. Controller：合并 `addColumn('editBtn')` + `addColumn('deleteBtn')` → `addColumn('action')` 使用 `ActionColumnHelper`
2. Controller：`rawColumns(['editBtn', 'deleteBtn'])` → `rawColumns(['action'])`
3. Blade：两个 `<th>` 合并为 `<th>{{ __('common.action') }}</th>`
4. JS：两个 column 配置合并为 `{data: 'action', name: 'action', orderable: false, searchable: false}`

**模式 B**（手写 action 列 HTML）：
1. Controller：将手写 HTML 替换为 `ActionColumnHelper` 链式调用
2. Blade 和 JS 无需修改（已是单列 `action`）

### 预设操作

| 名称 | 默认 label | 默认 onclick |
|------|-----------|-------------|
| `edit` | `__('common.edit')` | `editRecord(id)` |
| `delete` | `__('common.delete')` | `deleteRecord(id)` |
| `view` | `__('common.view')` | `viewRecord(id)` |

自定义操作使用 `->add('name', label, href, onclick)` 或 `->addRaw(html)`。

---

## 验证清单

转换完成后，逐项检查：

- [ ] `@extends('layouts.list-page')` — 不是 `layouts.app` 或 `FunctionsHelper::navigation()`
- [ ] `@section('table_id')` 与 JS 中 `$('#xxx').DataTable()` 选择器一致
- [ ] `@section('table_headers')` 的 `<th>` 数量与 JS `columns` 数组长度一致
- [ ] DataTable 赋值给 `dataTable`（基类全局变量），不是局部 `var table`
- [ ] `LanguageManager.loadAllFromPHP()` 在 `$(function(){ })` 内首先调用
- [ ] `setupEmptyStateHandler()` 在 DataTable 初始化之后调用
- [ ] 所有 AJAX URL 使用 `{{ url('/...') }}`，不是相对路径
- [ ] 所有原有事件函数保持不变（函数名、参数、内部逻辑）
- [ ] modal partial 通过 `@section('modals')` 中 `@include` 引入
- [ ] 删除了基类已提供的冗余代码（page_loader、loading div、debounce 等）
- [ ] `swal` 删除确认包含 `cancelButtonText`

---

## 示例：转换前 vs 转换后

### 转换前（旧模式）
```blade
@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
@section('css')
    @include('layouts.page_loader')
@endsection
<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <span class="caption-subject">标题</span>
                </div>
            </div>
            <div class="portlet-body">
                <div class="table-toolbar">...</div>
                <table id="my-table">
                    <thead><tr><th>ID</th><th>名称</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="loading">...</div>
@include('resource.create')
@endsection
@section('js')
<script src="page_loader.js"></script>
<script>
    var table = $('#my-table').DataTable({
        destroy: true,
        processing: true,
        serverSide: true,
        language: LanguageManager.getDataTableLang(),
        ajax: "resource/",
        columns: [...]
    });
    function alert_dialog(message, status) {
        swal("Alert", message, status);
        if (status) { table.draw(false); }
    }
</script>
@endsection
```

### 转换后（新模式）
```blade
@extends('layouts.list-page')

@section('page_title', __('resource.title'))
@section('table_id', 'my-table')

@section('header_actions')
    <button class="btn btn-primary" onclick="createRecord()">{{ __('common.add_new') }}</button>
@endsection

@section('table_headers')
    <th>{{ __('common.id') }}</th>
    <th>{{ __('resource.name') }}</th>
@endsection

@section('modals')
@include('resource.create')
@endsection

@section('page_js')
<script type="text/javascript">
    $(function () {
        LanguageManager.loadAllFromPHP({
            'resource': @json(__('resource')),
            'common': @json(__('common'))
        });

        dataTable = $('#my-table').DataTable({
            processing: true,
            serverSide: true,
            language: LanguageManager.getDataTableLang(),
            ajax: "{{ url('resource') }}",
            columns: [...]
        });

        setupEmptyStateHandler();
    });
    // 原有事件函数保持不变...
</script>
@endsection
```
