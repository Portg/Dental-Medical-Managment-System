# Generate List Page Command

根据 UI 规范生成标准的 Laravel + Blade 列表页面。

## 使用方式
```
/gen-list-page <resource_name>
/gen-list-page patients
/gen-list-page appointments
```

## 生成规则

### 1. 文件结构

生成以下文件：
- `resources/views/{resource}/index.blade.php` - 列表页主文件
- `resources/views/{resource}/create.blade.php` - 创建/编辑模态框

### 2. 列表页模板 (index.blade.php)

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
                    <span class="caption-subject">{{ __('{{resource}}.management') }} / {{ __('{{resource}}.list') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                {{-- 工具栏 --}}
                <div class="table-toolbar" style="margin-bottom: 15px;">
                    <a class="btn btn-theme" href="#" onclick="createRecord()">
                        <i class="fa fa-plus"></i> {{ __('{{resource}}.add_new') }}
                    </a>
                </div>

                {{-- 筛选区域 --}}
                <div class="filter-area" style="background: #f9f9f9; border: 1px solid #eee; border-radius: 4px; padding: 15px; margin-bottom: 20px;">
                    <div class="row">
                        <div class="col-md-3">
                            <input type="text" id="quickSearch" class="form-control" placeholder="{{ __('{{resource}}.search') }}">
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
                                <option value="This week">{{ __('datetime.time_periods.this_week') }}</option>
                                <option value="This Month">{{ __('datetime.time_periods.this_month') }}</option>
                            </select>
                        </div>
                        <div class="col-md-3 text-right">
                            <button type="button" class="btn btn-default" onclick="clearFilters()">
                                <i class="fa fa-refresh"></i> {{ __('common.clear') }}
                            </button>
                        </div>
                    </div>
                    {{-- 高级筛选 --}}
                    <div id="advancedFilters" style="display: none; margin-top: 15px; padding-top: 15px; border-top: 1px solid #e5e5e5;">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="control-label" style="font-size: 12px; color: #666;">{{ __('datetime.date_range.title') }}</label>
                                <div class="row">
                                    <div class="col-xs-5">
                                        <input type="text" class="form-control start_date" placeholder="{{ __('datetime.date_range.start_date') }}">
                                    </div>
                                    <div class="col-xs-2 text-center" style="padding-top: 7px;">{{ __('datetime.date_range.to') }}</div>
                                    <div class="col-xs-5">
                                        <input type="text" class="form-control end_date" placeholder="{{ __('datetime.date_range.end_date') }}">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-md-offset-3">
                                <label class="control-label">&nbsp;</label>
                                <button type="button" id="customFilterBtn" class="btn btn-theme btn-block">
                                    <i class="fa fa-search"></i> {{ __('common.search') }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="text-center" style="margin-top: 12px;">
                        <a href="javascript:void(0)" id="toggleAdvancedFilters" style="font-size: 12px; color: #8E44AD;">
                            <i class="fa fa-chevron-down"></i> {{ __('common.advanced_filter') }}
                        </a>
                    </div>
                </div>

                {{-- 数据表格 --}}
                <table class="table table-striped table-bordered table-hover table-checkable order-column" id="{{resource}}-table">
                    <thead>
                    <tr>
                        <th>{{ __('{{resource}}.id') }}</th>
                        <th>{{ __('{{resource}}.name') }}</th>
                        <th>{{ __('{{resource}}.status') }}</th>
                        <th>{{ __('{{resource}}.created_at') }}</th>
                        <th>{{ __('{{resource}}.action') }}</th>
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="loading">
    <i class="fa fa-refresh fa-spin fa-2x fa-fw"></i><br/>
    <span>{{ __('common.loading') }}</span>
</div>
@include('{{resource}}.create')
@endsection

@section('js')
<script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
<script src="{{ asset('include_js/DatesHelper.js') }}" type="text/javascript"></script>

<script type="text/javascript">
    // 加载页面特定翻译
    LanguageManager.loadAllFromPHP({
        '{{resource}}': @json(__('{{resource}}'))
    });

    // 防抖函数
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // 高级筛选折叠
    $('#toggleAdvancedFilters').on('click', function() {
        var $advFilters = $('#advancedFilters');
        var $icon = $(this).find('i');
        if ($advFilters.is(':visible')) {
            $advFilters.slideUp();
            $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
        } else {
            $advFilters.slideDown();
            $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
        }
    });

    $(function () {
        // 初始化 DataTable
        var table = $('#{{resource}}-table').DataTable({
            destroy: true,
            processing: true,
            serverSide: true,
            language: LanguageManager.getDataTableLang(),
            ajax: {
                url: "{{ url('/{{resource}}/') }}",
                data: function (d) {
                    d.start_date = $('.start_date').val();
                    d.end_date = $('.end_date').val();
                    d.status = $('#filter_status').val();
                    d.quick_search = $('#quickSearch').val();
                }
            },
            dom: 'rtip',
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex'},
                {data: 'name', name: 'name'},
                {data: 'status', name: 'status'},
                {data: 'created_at', name: 'created_at'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });

        // 快速搜索 (300ms 防抖)
        var debouncedSearch = debounce(function() {
            table.draw(true);
        }, 300);
        $('#quickSearch').on('keyup', debouncedSearch);

        // 状态筛选自动应用
        $('#filter_status').on('change', function() {
            table.draw(true);
        });
    });

    // 初始化 Select2
    $('#filter_status').select2({
        language: '{{ app()->getLocale() }}',
        placeholder: "{{ __('common.select_status') }}",
        allowClear: true
    });

    // 自定义筛选按钮
    $('#customFilterBtn').click(function () {
        $('#{{resource}}-table').DataTable().draw(true);
    });

    // 清除筛选
    function clearFilters() {
        $('#quickSearch').val('');
        $('.start_date').val('');
        $('.end_date').val('');
        $('#filter_status').val(null).trigger('change');
        $('#{{resource}}-table').DataTable().draw(true);
    }

    // 新建记录
    function createRecord() {
        $("#{{resource}}-form")[0].reset();
        $('#id').val('');
        $('#btnSave').attr('disabled', false);
        $('#{{resource}}-modal').modal('show');
    }

    // 编辑记录
    function editRecord(id) {
        $("#{{resource}}-form")[0].reset();
        $('#id').val('');
        $.LoadingOverlay("show");
        $.ajax({
            type: 'get',
            url: "{{resource}}/" + id + "/edit",
            success: function (data) {
                $('#id').val(id);
                // 填充表单字段
                // $('[name="field_name"]').val(data.record.field_name);
                $.LoadingOverlay("hide");
                $('#{{resource}}-modal').modal('show');
            },
            error: function () {
                $.LoadingOverlay("hide");
            }
        });
    }

    // 保存数据
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
        $('#btnSave').attr('disabled', true);
        $('#btnSave').html('<i class="fa fa-spinner fa-spin"></i> {{ __("common.saving") }}');
        $.ajax({
            type: 'POST',
            data: $('#{{resource}}-form').serialize(),
            url: "/{{resource}}",
            success: function (data) {
                $.LoadingOverlay("hide");
                $('#btnSave').attr('disabled', false);
                $('#btnSave').html('<i class="fa fa-check"></i> {{ __("common.save") }}');
                $('#{{resource}}-modal').modal('hide');
                if (data.status) {
                    alert_dialog(data.message, "success");
                } else {
                    alert_dialog(data.message, "danger");
                }
            },
            error: function (request) {
                $.LoadingOverlay("hide");
                $('#btnSave').attr('disabled', false);
                $('#btnSave').html('<i class="fa fa-check"></i> {{ __("common.save") }}');
                var json = $.parseJSON(request.responseText);
                $.each(json.errors, function (key, value) {
                    $('.alert-danger').show();
                    $('.alert-danger').append('<p>' + value + '</p>');
                });
            }
        });
    }

    function update_record() {
        $.LoadingOverlay("show");
        $('#btnSave').attr('disabled', true);
        $('#btnSave').text("{{ __('common.updating') }}");
        $.ajax({
            type: 'PUT',
            data: $('#{{resource}}-form').serialize(),
            url: "/{{resource}}/" + $('#id').val(),
            success: function (data) {
                $.LoadingOverlay("hide");
                $('#{{resource}}-modal').modal('hide');
                if (data.status) {
                    alert_dialog(data.message, "success");
                } else {
                    alert_dialog(data.message, "danger");
                }
            },
            error: function (request) {
                $.LoadingOverlay("hide");
                $('#btnSave').attr('disabled', false);
                $('#btnSave').text("{{ __('common.update_record') }}");
                var json = $.parseJSON(request.responseText);
                $.each(json.errors, function (key, value) {
                    $('.alert-danger').show();
                    $('.alert-danger').append('<p>' + value + '</p>');
                });
            }
        });
    }

    // 删除记录
    function deleteRecord(id) {
        var sweetAlertLang = LanguageManager.getSweetAlertLang();
        swal({
            title: "{{ __('common.are_you_sure') }}",
            text: "{{ __('{{resource}}.delete_warning') }}",
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: "{{ __('common.yes_delete_it') }}",
            cancelButtonText: sweetAlertLang.cancel,
            closeOnConfirm: false
        }, function () {
            $.LoadingOverlay("show");
            $.ajax({
                type: 'delete',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                url: "{{resource}}/" + id,
                success: function (data) {
                    $.LoadingOverlay("hide");
                    if (data.status) {
                        alert_dialog(data.message, "success");
                    } else {
                        alert_dialog(data.message, "danger");
                    }
                },
                error: function () {
                    $.LoadingOverlay("hide");
                }
            });
        });
    }

    function alert_dialog(message, status) {
        swal("{{ __('common.alert') }}", message, status);
        if (status === "success") {
            $('#{{resource}}-table').DataTable().draw(false);
        }
    }
</script>
@endsection
```

### 3. 模态框表单模板 (create.blade.php)

```blade
<div class="modal fade" id="{{resource}}-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">{{ __('{{resource}}.add_new') }}</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display: none;">
                    <ul></ul>
                </div>
                <form id="{{resource}}-form">
                    @csrf
                    <input type="hidden" id="id" name="id">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">{{ __('{{resource}}.name') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" placeholder="{{ __('{{resource}}.enter_name') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">{{ __('{{resource}}.status') }}</label>
                                <select class="form-control select2" name="status" style="width: 100%;">
                                    <option value="active">{{ __('common.active') }}</option>
                                    <option value="inactive">{{ __('common.inactive') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="control-label">{{ __('{{resource}}.description') }}</label>
                                <textarea class="form-control" name="description" rows="3" placeholder="{{ __('{{resource}}.enter_description') }}"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    {{ __('common.cancel') }}
                </button>
                <button type="button" class="btn btn-theme" id="btnSave" onclick="save_data()">
                    <i class="fa fa-check"></i> {{ __('common.save') }}
                </button>
            </div>
        </div>
    </div>
</div>
```

### 4. 必要的语言文件 Key

生成后需要在 `resources/lang/{locale}/{{resource}}.php` 中添加：

```php
<?php
return [
    'management' => '{{Resource}} Management',
    'list' => '{{Resource}} List',
    'add_new' => 'Add New {{Resource}}',
    'search' => 'Search {{resource}}...',
    'id' => 'ID',
    'name' => 'Name',
    'status' => 'Status',
    'description' => 'Description',
    'created_at' => 'Created At',
    'action' => 'Action',
    'enter_name' => 'Enter name',
    'enter_description' => 'Enter description',
    'delete_warning' => 'This {{resource}} will be permanently deleted!',
];
```

## 执行步骤

1. 解析 `$ARGUMENTS` 获取资源名称
2. 将模板中的 `{{resource}}` 替换为实际资源名
3. 生成 `index.blade.php` 文件
4. 生成 `create.blade.php` 文件
5. 生成语言文件模板（中英文）
6. 输出生成的文件列表和后续配置提示

## 后续配置提示

生成文件后，提醒用户：

1. 在 `routes/web.php` 添加资源路由
2. 创建对应的 Controller 并实现 DataTables 接口
3. 添加语言文件翻译
4. 根据实际字段调整表单和表格列
