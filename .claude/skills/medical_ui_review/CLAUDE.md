# Project: 口腔诊所管理系统

## 项目概述
这是一个口腔诊所管理系统，使用 Laravel 框架构建，采用模块化架构支持多角色（医生、护士、前台、超级管理员）。

## 技术栈
- **后端**：Laravel (PHP 7.4+)
- **模板**：Blade 模板引擎
- **CSS 框架**：Bootstrap 3
- **JS 库**：jQuery
- **UI 组件**：
  - DataTables（服务端分页表格）
  - Select2（增强下拉选择）
  - SweetAlert（确认弹窗）
  - FullCalendar（日历视图）
  - Bootstrap Datepicker（日期选择）
  - Toastr（消息提示）
  - LoadingOverlay（加载遮罩）
  - Chart.js（图表）

## UI 审查规范

本项目遵循医疗系统 UI 规范，在进行页面开发或审查时，请参考以下原则：

### 页面结构规范
1. **布局容器**：使用 `portlet light bordered` 作为页面主容器
2. **页面标题**：使用 `portlet-title` + `caption-subject` 结构
3. **工具栏**：使用 `table-toolbar` 放置主操作按钮，按钮使用 `btn-theme` 类
4. **筛选区域**：使用 `filter-area` 容器，支持高级筛选折叠

### 列表页设计原则
1. **页面头部**：标题使用 `caption-subject`，主操作按钮唯一且在工具栏左侧
2. **筛选区域**：最多两行，高频筛选靠前，支持折叠的高级筛选
3. **数据表格**：使用 DataTables，列数 ≤ 9，操作列右对齐
4. **空状态**：DataTables 自动处理，需配置正确的国际化
5. **搜索**：使用 300ms 防抖的 `quickSearch` 输入框

### 表单规范
1. **模态框表单**：使用 Bootstrap Modal，ID 命名为 `{resource}-modal`
2. **表单验证**：后端验证 + 前端显示错误（`.alert-danger` 容器）
3. **保存按钮**：支持"保存"和"保存并继续"两种模式
4. **加载状态**：使用 `$.LoadingOverlay("show/hide")`

### 组件使用规范
```blade
{{-- 下拉选择 --}}
$('#field').select2({
    language: '{{ app()->getLocale() }}',
    placeholder: "{{ __('key') }}",
    allowClear: true
});

{{-- 日期选择 --}}
$('.datepicker').datepicker({
    language: '{{ app()->getLocale() }}',
    autoclose: true,
    todayHighlight: true
});

{{-- 确认删除 --}}
swal({
    title: "{{ __('common.are_you_sure') }}",
    type: "warning",
    showCancelButton: true,
    confirmButtonClass: "btn-danger"
});
```

### 禁止模式
- ❌ 多个主按钮（只能有一个 `btn-theme` 主操作）
- ❌ 筛选与新增操作混排
- ❌ 删除无二次确认（必须使用 SweetAlert）
- ❌ 超过 9 列的表格
- ❌ 硬编码文本（必须使用 `__()` 国际化）
- ❌ 内联样式超过 3 个属性（应使用 CSS 类）

## 自定义命令

- `/form-review` - 审查当前页面的 UI 规范符合度
- `/gen-list-page` - 根据审查结果生成修复代码

## 文件命名约定
- 列表页面：`resources/views/{resource}/index.blade.php`
- 创建表单：`resources/views/{resource}/create.blade.php`
- 编辑页面：复用 create.blade.php（通过 hidden id 字段判断）
- 模块页面：`Modules/{Module}/Resources/views/{resource}/index.blade.php`

## 国际化要求
- 所有用户可见文本必须使用 `{{ __('file.key') }}` 或 `@lang('file.key')`
- JavaScript 文本使用 `LanguageManager.trans('key')`
- DataTables 语言配置使用 `LanguageManager.getDataTableLang()`
- Select2 必须设置 `language: '{{ app()->getLocale() }}'`
