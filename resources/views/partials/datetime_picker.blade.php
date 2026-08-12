{{--
    日期 + 时间组合控件，替代 <input type="datetime-local">。

    原生 datetime-local 的显示格式跟着浏览器界面语言走，英文界面上是
    「08/12/2026, 07:48 PM」。这里拆成两个已本地化的控件：
      日期 → .js-date（bootstrap-datepicker 中文语言包）
      时间 → .js-time（clockface，24 小时表盘，无文案）
    两者拼进同名 hidden，提交值仍是 `Y-m-d H:i`，后端不用改。

    用法：
        @include('partials.datetime_picker', ['name' => 'performed_at', 'required' => true])

    参数：
        name      必填，提交用的字段名（hidden 的 name）
        required  选填，true 时给日期和时间加 required

    ⚠️ 提交前必须调用 DateTimePicker.sync(form) 把两个可见框合并进 hidden。
       clockface 选完时间不会派发 change 事件，靠事件监听同步不可靠。
       样式与脚本在 layouts/app.blade.php 统一引入，这里只出标记。
--}}
<div class="datetime-picker" data-datetime-picker="{{ $name }}">
    <input type="text"
           class="form-control js-date datetime-picker__date"
           data-datetime-date
           autocomplete="off"
           placeholder="{{ __('common.date_placeholder') }}"
           @if(!empty($required)) required @endif>
    <input type="text"
           class="form-control js-time datetime-picker__time"
           data-datetime-time
           autocomplete="off"
           placeholder="{{ __('common.time_placeholder') }}"
           @if(!empty($required)) required @endif>
    <input type="hidden" name="{{ $name }}" data-datetime-value>
</div>
