# Bootstrap + DataTables 医疗列表页最佳实践速查

## 页面结构

```blade
{{-- ✅ 正确 --}}
@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-body">
                {{-- 工具栏 --}}
                {{-- 筛选区 --}}
                {{-- 数据表格 --}}
            </div>
        </div>
    </div>
</div>
@endsection

{{-- ❌ 错误 --}}
<div class="container">
    <h1>患者管理</h1>  {{-- 缺少 portlet 容器，硬编码文本 --}}
    <table>...</table>
</div>
```

## 页面工具栏

```blade
{{-- ✅ 正确 --}}
<div class="table-toolbar" style="margin-bottom: 15px;">
    <a class="btn btn-theme" href="#" onclick="createRecord()">
        <i class="fa fa-plus"></i> {{ __('patient.add_new_patient') }}
    </a>
</div>

{{-- ❌ 错误 --}}
<div class="table-toolbar">
    <button class="btn btn-theme">新增</button>  {{-- 硬编码 --}}
    <button class="btn btn-theme">导入</button>  {{-- 多个主按钮 --}}
    <button class="btn btn-theme">导出</button>
</div>
```

## 筛选区域

```blade
{{-- ✅ 正确 --}}
<div class="filter-area" style="background: #f9f9f9; border: 1px solid #eee; border-radius: 4px; padding: 15px; margin-bottom: 20px;">
    <div class="row">
        <div class="col-md-3">
            <input type="text" id="quickSearch" class="form-control" placeholder="{{ __('patient.search_patients') }}">
        </div>
        <div class="col-md-2">
            <select id="filter_status" class="form-control select2" style="width: 100%;">
                <option value="">{{ __('common.all_status') }}</option>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-control" id="period_selector">
                <option value="">{{ __('datetime.time_periods.all') }}</option>
                <option value="Today">{{ __('datetime.time_periods.today') }}</option>
            </select>
        </div>
        <div class="col-md-3 text-right">
            <button type="button" class="btn btn-default" onclick="clearFilters()">
                <i class="fa fa-refresh"></i> {{ __('common.clear') }}
            </button>
        </div>
    </div>
    {{-- 可折叠的高级筛选 --}}
    <div id="advancedFilters" style="display: none; margin-top: 15px; padding-top: 15px; border-top: 1px solid #e5e5e5;">
        {{-- 高级筛选内容 --}}
    </div>
    <div class="text-center" style="margin-top: 12px;">
        <a href="javascript:void(0)" id="toggleAdvancedFilters" style="font-size: 12px;">
            <i class="fa fa-chevron-down"></i> {{ __('common.advanced_filter') }}
        </a>
    </div>
</div>

{{-- ❌ 错误 --}}
<input type="text" placeholder="搜索">  {{-- 硬编码，无 filter-area 容器 --}}
<select id="status"></select>
<button class="btn btn-theme">新增</button>  {{-- 新增不应在筛选区 --}}
```

## 数据表格

```blade
{{-- ✅ 正确 --}}
<table class="table table-striped table-bordered table-hover table-checkable order-column" id="patients-table">
    <thead>
    <tr>
        <th>{{ __('patient.id') }}</th>
        <th>{{ __('patient.full_name') }}</th>
        <th>{{ __('patient.gender') }}</th>
        <th>{{ __('patient.phone_no') }}</th>
        <th>{{ __('patient.action') }}</th>
    </tr>
    </thead>
    <tbody></tbody>
</table>

{{-- ❌ 错误 --}}
<table class="table" id="patients-table">
    <thead>
    <tr>
        <th>ID</th>  {{-- 硬编码 --}}
        <th>姓名</th>
        <th>性别</th>
        <th>电话</th>
        <th>保险公司</th>
        <th>添加人</th>
        <th>创建时间</th>
        <th>更新时间</th>
        <th>来源</th>
        <th>标签</th>
        <th>操作</th>  {{-- 超过9列 --}}
    </tr>
    </thead>
</table>
```

## DataTables 初始化

```javascript
// ✅ 正确
var table = $('#patients-table').DataTable({
    processing: true,
    serverSide: true,
    language: LanguageManager.getDataTableLang(),  // 国际化
    ajax: {
        url: "{{ url('/patients/') }}",
        data: function (d) {
            d.start_date = $('.start_date').val();
            d.end_date = $('.end_date').val();
            d.quick_search = $('#quickSearch').val();
        }
    },
    dom: 'rtip',  // 使用自定义搜索框
    columns: [
        {data: 'DT_RowIndex', name: 'DT_RowIndex'},
        {data: 'full_name', name: 'full_name'},
        {data: 'action', name: 'action', orderable: false, searchable: false}
    ]
});

// 300ms 防抖搜索
var debouncedSearch = debounce(function() {
    table.draw(true);
}, 300);
$('#quickSearch').on('keyup', debouncedSearch);

// ❌ 错误
$('#patients-table').DataTable({
    ajax: "{{ url('/patients/') }}"
    // 缺少 language 配置
    // 缺少 serverSide
});
```

## Select2 初始化

```javascript
// ✅ 正确
$('#filter_company').select2({
    language: '{{ app()->getLocale() }}',
    placeholder: "{{ __('patient.choose_insurance_company') }}",
    allowClear: true,
    minimumInputLength: 2,
    ajax: {
        url: '/search-insurance-company',
        dataType: 'json',
        processResults: function (data) {
            return { results: data };
        }
    }
});

// ❌ 错误
$('#filter_company').select2({
    placeholder: "选择保险公司"  // 硬编码，缺少 language
});
```

## 删除确认

```javascript
// ✅ 正确
function deleteRecord(id) {
    var sweetAlertLang = LanguageManager.getSweetAlertLang();
    swal({
        title: "{{ __('common.are_you_sure') }}",
        text: "{{ __('patient.delete_patient_warning') }}",
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn-danger",
        confirmButtonText: "{{ __('common.yes_delete_it') }}",
        cancelButtonText: sweetAlertLang.cancel,
        closeOnConfirm: false
    }, function () {
        $.ajax({
            type: 'delete',
            url: "patients/" + id,
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
            success: function (data) {
                swal("{{ __('common.alert') }}", data.message, data.status ? "success" : "danger");
                $('#patients-table').DataTable().draw(false);
            }
        });
    });
}

// ❌ 错误
function deleteRecord(id) {
    if (confirm('确定删除?')) {  // 原生 confirm，硬编码
        $.ajax({
            type: 'delete',
            url: "patients/" + id
            // 缺少 CSRF token
        });
    }
}
```

## 模态框表单

```blade
{{-- ✅ 正确 --}}
<div class="modal fade" id="patients-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">{{ __('patient.add_new_patient') }}</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display: none;">
                    <ul></ul>
                </div>
                <form id="patient-form">
                    <input type="hidden" id="id" name="id">
                    {{-- 表单字段 --}}
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    {{ __('common.cancel') }}
                </button>
                <button type="button" class="btn btn-theme" id="btnSave" onclick="save_data(false)">
                    <i class="fa fa-check"></i> {{ __('common.save') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ❌ 错误 --}}
<div class="modal" id="modal">  {{-- 命名不规范 --}}
    <form>
        {{-- 缺少 .alert-danger 错误容器 --}}
        {{-- 缺少 hidden id 字段 --}}
        <button type="submit">保存</button>  {{-- 硬编码，缺少 onclick --}}
    </form>
</div>
```

## 加载状态

```javascript
// ✅ 正确
function save_data() {
    $.LoadingOverlay("show");
    $('#btnSave').attr('disabled', true);
    $('#btnSave').html('<i class="fa fa-spinner fa-spin"></i> {{ __("common.saving") }}');

    $.ajax({
        // ...
        complete: function() {
            $.LoadingOverlay("hide");
            $('#btnSave').attr('disabled', false);
            $('#btnSave').html('<i class="fa fa-check"></i> {{ __("common.save") }}');
        }
    });
}

// ❌ 错误
function save_data() {
    $.ajax({ /* ... */ });  // 无加载状态反馈
}
```

## 日期选择器

```javascript
// ✅ 正确
$('.start_date').datepicker({
    language: '{{ app()->getLocale() }}',
    autoclose: true,
    todayHighlight: true,
    format: 'yyyy-mm-dd'
});

// ❌ 错误
$('.start_date').datepicker();  // 缺少 language 配置
```

## 检查清单

| 项目 | 要求 |
|------|------|
| 主按钮数量 | = 1 (`btn-theme`) |
| 表格列数 | ≤ 9 |
| 所有文本 | 使用 `__()` 国际化 |
| DataTables | `language: LanguageManager.getDataTableLang()` |
| Select2 | `language: '{{ app()->getLocale() }}'` |
| Datepicker | `language: '{{ app()->getLocale() }}'` |
| 删除操作 | 使用 SweetAlert 确认 |
| 模态框 ID | `{resource}-modal` 命名 |
| 错误显示 | `.alert-danger` 容器 |
| 加载状态 | `$.LoadingOverlay()` |
| 搜索输入 | 300ms 防抖 |
