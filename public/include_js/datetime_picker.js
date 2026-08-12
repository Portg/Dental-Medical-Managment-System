/*
 * partials/datetime_picker.blade.php 的行为层。
 *
 * 控件由两个可见输入框（日期 .js-date / 时间 .js-time）加一个同名 hidden 组成。
 * 日期框由 layout 里的全局 bootstrap-datepicker 初始化接管，这里只管：
 *   1. 给时间框挂 clockface（24 小时表盘，HH:mm）
 *   2. 把两个框拼进 hidden
 *   3. 提供回填接口给「编辑」场景
 *
 * 为什么不监听 change 自动同步：clockface 选完时间是直接 $element.val(...)，
 * **不派发 change 事件**（见 clockface.js:314/331）。所以调用方必须在提交前
 * 显式调 DateTimePicker.sync(form)。日期框这边顺带也监听着，纯属兜底。
 */
window.DateTimePicker = (function ($) {
    'use strict';

    var DATE_TIME = /^(\d{4}-\d{2}-\d{2})[T ](\d{2}:\d{2})/;

    function wrappers(root) {
        return $(root || document).find('[data-datetime-picker]');
    }

    function parts($wrapper) {
        return {
            date: $wrapper.find('[data-datetime-date]'),
            time: $wrapper.find('[data-datetime-time]'),
            value: $wrapper.find('[data-datetime-value]')
        };
    }

    function init(root) {
        wrappers(root).each(function () {
            var p = parts($(this));
            if (p.time.length && !p.time.data('clockface-ready') && $.fn.clockface) {
                p.time.clockface({ format: 'HH:mm' }).data('clockface-ready', true);
            }
        });
    }

    /** 把可见的日期 + 时间合并进 hidden，返回本次同步的字段数 */
    function sync(root) {
        var count = 0;
        wrappers(root).each(function () {
            var p = parts($(this));
            var date = $.trim(p.date.val() || '');
            var time = $.trim(p.time.val() || '');

            // 只填了日期没填时间时补 00:00，否则后端的 date 校验会挂在半截字符串上
            p.value.val(date ? date + ' ' + (time || '00:00') : '');
            count += 1;
        });
        return count;
    }

    /**
     * 回填。接受 `Y-m-d H:i`、`Y-m-d H:i:s` 和 `Y-m-dTH:i` 三种写法
     * （分别来自 DataTables 行数据、接口原值和 window._serverNow）。
     */
    function set(root, name, value) {
        var $wrapper = $(root || document).find('[data-datetime-picker="' + name + '"]');
        if (!$wrapper.length) {
            return;
        }

        var p = parts($wrapper);
        var matched = DATE_TIME.exec(String(value || ''));

        p.date.val(matched ? matched[1] : '');
        p.time.val(matched ? matched[2] : '');
        p.value.val(matched ? matched[1] + ' ' + matched[2] : '');

        // 让 datepicker 内部日期跟着走，否则下次展开还停在上一条记录的月份
        if (p.date.data('datepicker')) {
            p.date.datepicker('update', matched ? matched[1] : '');
        }
    }

    function clear(root) {
        wrappers(root).each(function () {
            var p = parts($(this));
            p.date.val('');
            p.time.val('');
            p.value.val('');
            if (p.date.data('datepicker')) {
                p.date.datepicker('update', '');
            }
        });
    }

    $(function () {
        init();
        $(document).on('change', '[data-datetime-picker] input', function () {
            sync($(this).closest('[data-datetime-picker]').parent());
        });
    });

    return { init: init, sync: sync, set: set, clear: clear };
})(jQuery);
