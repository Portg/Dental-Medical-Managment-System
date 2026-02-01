# Convert / Generate Form Modal Command

将已有的表单弹窗转换为 `layouts.form-modal` 基类模板，或基于基类模板生成新表单弹窗。
**核心原则：不影响任何已有事件和业务逻辑。**

## 输入格式

```
/gen-form-modal                                                → 交互式：询问文件路径
/gen-form-modal resources/views/holidays/create.blade.php      → 转换已有弹窗
/gen-form-modal new patient_tags                               → 生成新弹窗
```

若 `$ARGUMENTS` 为空，进入交互式引导，询问：
1. 操作类型（convert 转换 / new 新建）
2. 文件路径或资源名
3. 确认转换范围

---

## 转换规则（convert 模式）

### 第一步：读取分析

1. **读取目标文件**：完整读取现有 blade 文件
2. **读取基类模板**：读取 `resources/views/layouts/form-modal.blade.php` 了解可用 section
3. **分析现有结构**：
   - 识别 modal id（`<div class="modal fade" id="xxx-modal">`）
   - 识别 modal 标题（`<h4 class="modal-title">`）
   - 识别 form id（`<form ... id="xxx-form">`）
   - 提取 hidden fields（`<input type="hidden">`）
   - 提取所有表单字段（完整的 `<div class="form-group">` 块）
   - 提取 footer 按钮
   - 提取 modal 尺寸（是否有 `modal-lg` 等）
   - 提取表单内或 modal 后的 `<script>` 代码

### 第二步：映射到 section

| 原始内容 | 目标 section |
|---------|-------------|
| `<div id="xxx-modal">` 的 id | `@section('modal_id', 'xxx-modal')` |
| `<h4 class="modal-title">` 中的文字 | `@section('modal_title', __('xxx.form_title'))` |
| `<form id="xxx-form">` 的 id | `@section('form_id', 'xxx-form')` |
| `modal-lg` / `modal-sm` 类名 | `@section('modal_size', 'modal-form-lg')` |
| `<input type="hidden">` 字段 | `@section('hidden_fields')` |
| 所有 `<div class="form-group">` | `@section('form_content')` |
| footer 按钮（与基类不同时） | `@section('footer_buttons')` |
| `<script>` 内容 | `@section('form_js')` |

### 第三步：内容迁移

#### 必须迁移到 section 中

```blade
{{-- modal_id --}}
@section('modal_id', 'holidays-modal')

{{-- modal_title --}}
@section('modal_title', __('holidays.holidays_form'))

{{-- form_id --}}
@section('form_id', 'holidays-form')

{{-- hidden_fields（id 字段） --}}
@section('hidden_fields')
    <input type="hidden" id="id" name="id">
@endsection

{{-- form_content（所有表单字段，保持原样） --}}
@section('form_content')
    <div class="form-group">
        <label class="text-primary">{{ __('holidays.holiday') }}</label>
        <input type="text" name="name" ...>
    </div>
    ...
@endsection
```

#### 禁止修改（保持原样）

- **表单字段**：所有 `<div class="form-group">` 的内部结构不改
- **字段属性**：`name`、`id`、`class`、`placeholder` 不改
- **Select2 初始化**：`$('#xxx').select2({...})` 不改
- **Datepicker 初始化**：`$('.datepicker').datepicker({...})` 不改
- **onclick 事件**：footer 按钮的 `onclick="save_data()"` 等不改
- **@foreach/@if 等 Blade 指令**：保持原样
- **服务端渲染数据**：`@foreach ($items as $item)` 等不改
- **条件显示逻辑**：`v-if`、jQuery show/hide 等不改

#### 按需调整

| 情况 | 处理方式 |
|------|---------|
| footer 按钮与基类默认一致（Cancel + Save） | 不需要 `@section('footer_buttons')` |
| footer 按钮有差异（不同文字/多个按钮/不同 onclick） | 使用 `@section('footer_buttons')` 覆盖 |
| modal 后有 `<script>` 标签 | 移入 `@section('form_js')` |
| 表单使用 `form-horizontal` class | 基类已包含，无需额外处理 |

### 第四步：移除冗余

转换后，以下内容由基类提供，子页面中应**删除**：

| 删除内容 | 原因 |
|---------|------|
| `<div class="modal fade" id="xxx-modal" role="dialog" aria-hidden="true">` | 基类已包含 |
| `<div class="modal-dialog">` | 基类已包含 |
| `<div class="modal-content">` | 基类已包含 |
| `<div class="modal-header">` + `<button class="close">` + `<h4>` | 基类已包含 |
| `<div class="modal-body">` | 基类已包含 |
| `<div class="alert alert-danger">...</div>` | 基类已包含 |
| `<form action="#" id="xxx-form" autocomplete="off">` | 基类已包含 |
| `@csrf` | 基类已包含 |
| `</form>` | 基类已包含 |
| `<div class="modal-footer">` | 基类已包含 |
| `@foreach ($errors->all() as $error)` 错误循环 | 基类使用 JS 方式展示 |

### 第五步：处理 footer 按钮

基类默认 footer：
```blade
<button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.cancel') }}</button>
<button type="button" id="btn-save" class="btn btn-primary" onclick="saveForm()">{{ __('common.save') }}</button>
```

**对比旧模式的常见 footer**：
```blade
<button type="button" class="btn btn-primary" id="btn-save" onclick="save_data()">{{ __('common.save_changes') }}</button>
<button type="button" class="btn dark btn-outline" data-dismiss="modal">{{ __('common.close') }}</button>
```

差异点：
1. 基类 onclick 是 `saveForm()` vs 旧模式 `save_data()` — **必须覆盖**
2. 基类按钮文字是 `common.save` vs 旧模式 `common.save_changes` — 按实际需要选择
3. 按钮顺序不同（基类 Cancel 在前，旧模式 Save 在前）

**处理规则**：若旧模式 footer 的 `onclick` 函数名不是 `saveForm()`，必须使用 `@section('footer_buttons')` 覆盖，保留原有的 onclick 绑定。

---

## 新建模式（new）

### 输入
```
/gen-form-modal new {resource_name}
```

### 生成文件

#### `resources/views/{resource}/create.blade.php`

```blade
@extends('layouts.form-modal')

@section('modal_id', '{{resource}}-modal')

@section('modal_title', __('{{resource}}.form_title'))

@section('form_id', '{{resource}}-form')

@section('hidden_fields')
    <input type="hidden" id="id" name="id">
@endsection

@section('form_content')
    <div class="form-group">
        <label class="text-primary">{{ __('{{resource}}.name') }}</label>
        <input type="text" name="name" placeholder="{{ __('{{resource}}.enter_name') }}" class="form-control">
    </div>
@endsection

@section('footer_buttons')
    <button type="button" class="btn btn-primary" id="btn-save" onclick="save_data()">{{ __('common.save_changes') }}</button>
    <button type="button" class="btn dark btn-outline" data-dismiss="modal">{{ __('common.close') }}</button>
@endsection
```

---

## 验证清单

转换完成后，逐项检查：

- [ ] `@extends('layouts.form-modal')` — 不是内联 HTML modal 结构
- [ ] `@section('modal_id')` 与父页面 JS 中 `$('#xxx-modal').modal('show')` 一致
- [ ] `@section('form_id')` 与父页面 JS 中 `$('#xxx-form').serialize()` / `$('#xxx-form')[0].reset()` 一致
- [ ] `@section('hidden_fields')` 包含所有原有的 hidden input（如 `id` 字段）
- [ ] `@section('form_content')` 包含所有原有的表单字段，结构不变
- [ ] 删除了基类已提供的冗余 HTML（modal 容器、header、alert-danger、form 标签、csrf、footer）
- [ ] `@foreach ($errors->all())` 已删除（基类使用 JS 方式展示验证错误）
- [ ] footer 按钮的 `onclick` 函数名与父页面 JS 一致（通常是 `save_data()`）
- [ ] 如果有表单相关的 `<script>`，已移入 `@section('form_js')`
- [ ] modal 大小（如有 `modal-lg`）通过 `@section('modal_size')` 设置

---

## 示例：转换前 vs 转换后

### 转换前（旧模式）
```blade
<div class="modal fade" id="holidays-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                <h4 class="modal-title"> {{ __('holidays.holidays_form') }} </h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <form action="#" id="holidays-form" autocomplete="off">
                    @csrf
                    <input type="hidden" id="id" name="id">
                    <div class="form-group">
                        <label class="text-primary">{{ __('holidays.holiday') }}</label>
                        <input type="text" name="name" placeholder="{{ __('holidays.enter_holiday_name') }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('holidays.date_of_the_year') }}</label>
                        <input type="text" name="holiday_date" placeholder="{{ __('holidays.enter_date_of_the_year') }}" class="form-control" id="datepicker">
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('holidays.is_this_the_same_date_every_year') }}</label><br>
                        <input type="radio" name="repeat_date" value="Yes"> {{ __('common.yes') }}
                        <input type="radio" name="repeat_date" value="No"> {{ __('common.no') }}
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="btn-save" onclick="save_data()">{{ __('common.save_changes') }}</button>
                <button type="button" class="btn dark btn-outline" data-dismiss="modal">{{ __('common.close') }}</button>
            </div>
        </div>
    </div>
</div>
```

### 转换后（新模式）
```blade
@extends('layouts.form-modal')

@section('modal_id', 'holidays-modal')

@section('modal_title', __('holidays.holidays_form'))

@section('form_id', 'holidays-form')

@section('hidden_fields')
    <input type="hidden" id="id" name="id">
@endsection

@section('form_content')
    <div class="form-group">
        <label class="text-primary">{{ __('holidays.holiday') }}</label>
        <input type="text" name="name" placeholder="{{ __('holidays.enter_holiday_name') }}" class="form-control">
    </div>
    <div class="form-group">
        <label class="text-primary">{{ __('holidays.date_of_the_year') }}</label>
        <input type="text" name="holiday_date" placeholder="{{ __('holidays.enter_date_of_the_year') }}" class="form-control" id="datepicker">
    </div>
    <div class="form-group">
        <label class="text-primary">{{ __('holidays.is_this_the_same_date_every_year') }}</label><br>
        <input type="radio" name="repeat_date" value="Yes"> {{ __('common.yes') }}
        <input type="radio" name="repeat_date" value="No"> {{ __('common.no') }}
    </div>
@endsection

@section('footer_buttons')
    <button type="button" class="btn btn-primary" id="btn-save" onclick="save_data()">{{ __('common.save_changes') }}</button>
    <button type="button" class="btn dark btn-outline" data-dismiss="modal">{{ __('common.close') }}</button>
@endsection
```
