/**
 * 预约状态筛选面板。
 *
 * 一份筛选管三个视图（日历 / 医生泳道 / 诊室泳道）：三者读的都是同一个
 * calendar-events 接口，事件里都带 extendedProps.status_code，所以筛选做在
 * **客户端**、按 status_code 过滤，而不是每个视图各自往后端传一次参数。
 * 好处是切页签时筛选不丢，也不用为三个视图各写一遍取数逻辑。
 *
 * 色卡由后端 AppointmentService::statusColorMap() 供给，与事件块底色同源 ——
 * 这个面板的全部作用就是「按颜色认状态」，图例和事件对不上就白做了。
 *
 * 选择存 localStorage：前台通常长期只看「待到院 + 就诊中」这几种，
 * 每天开页面重勾一遍是纯损耗。
 */
(function ($) {
    'use strict';

    var STORAGE_KEY = 'apt_status_filter_v1';

    var statuses = [];        // [{code, color, label}]
    var selected = null;      // Set 语义的对象；null = 尚未初始化
    var onChange = null;

    function loadSaved(allCodes) {
        try {
            var raw = window.localStorage.getItem(STORAGE_KEY);
            if (!raw) return null;
            var arr = JSON.parse(raw);
            if (!Array.isArray(arr)) return null;

            // 只保留仍然存在的状态码：改过状态枚举之后，存下来的旧码
            // 会让面板筛出一个空日历，而界面上看不出是为什么
            var map = {};
            arr.forEach(function (code) {
                if (allCodes.indexOf(code) !== -1) map[code] = true;
            });
            return Object.keys(map).length ? map : null;
        } catch (e) {
            return null;
        }
    }

    function save() {
        try {
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(Object.keys(selected)));
        } catch (e) {
            // 隐私模式下 localStorage 会抛 —— 筛选照样能用，只是记不住
        }
    }

    function render($container) {
        var html = '<div class="apt-status-filter-head">'
                 + '<span class="apt-status-filter-title">'
                 + LanguageManager.trans('appointment.filter_by_status', '预约状态') + '</span>'
                 + '<a href="javascript:;" class="apt-status-all">'
                 + LanguageManager.trans('appointment.select_all', '全选') + '</a>'
                 + '<a href="javascript:;" class="apt-status-none">'
                 + LanguageManager.trans('appointment.select_none', '全不选') + '</a>'
                 + '</div><div class="apt-status-filter-list">';

        statuses.forEach(function (s) {
            html += '<label class="apt-status-chip' + (selected[s.code] ? ' is-on' : '') + '">'
                 + '<input type="checkbox" value="' + s.code + '"' + (selected[s.code] ? ' checked' : '') + '>'
                 + '<i class="apt-status-dot" style="background:' + s.color + '"></i>'
                 + '<span>' + s.label + '</span>'
                 + '</label>';
        });

        html += '</div>';
        $container.html(html);
    }

    window.AppointmentStatusFilter = {
        /**
         * @param {Object} opts {container, statuses:[{code,color,label}], onChange}
         */
        init: function (opts) {
            var $container = $(opts.container);
            if (!$container.length) return;

            statuses = opts.statuses || [];
            onChange = typeof opts.onChange === 'function' ? opts.onChange : null;

            var allCodes = statuses.map(function (s) { return s.code; });
            selected = loadSaved(allCodes);
            if (!selected) {
                // 默认全选：进来先看到全部，比进来看到一半更不容易误判
                selected = {};
                allCodes.forEach(function (c) { selected[c] = true; });
            }

            render($container);

            $container.on('change', 'input[type=checkbox]', function () {
                var code = $(this).val();
                if (this.checked) { selected[code] = true; } else { delete selected[code]; }
                $(this).closest('.apt-status-chip').toggleClass('is-on', this.checked);
                save();
                if (onChange) onChange();
            });

            $container.on('click', '.apt-status-all, .apt-status-none', function () {
                var on = $(this).hasClass('apt-status-all');
                selected = {};
                if (on) { allCodes.forEach(function (c) { selected[c] = true; }); }
                $container.find('input[type=checkbox]').prop('checked', on)
                    .closest('.apt-status-chip').toggleClass('is-on', on);
                save();
                if (onChange) onChange();
            });
        },

        /**
         * 这个事件该不该显示。
         *
         * 未知状态码一律放行：库里有旧枚举值（treatment complete 之类），
         * 它们不在筛选面板里，按「未勾选」处理会让这些预约凭空消失。
         */
        accepts: function (statusCode) {
            if (!selected) return true;
            if (!statusCode) return true;
            var known = statuses.some(function (s) { return s.code === statusCode; });
            if (!known) return true;
            return !!selected[statusCode];
        },

        /** 过滤一批事件（日历与泳道共用） */
        filterEvents: function (events) {
            var self = this;
            return (events || []).filter(function (evt) {
                var ep = evt.extendedProps || {};
                return self.accepts(ep.status_code);
            });
        }
    };
})(jQuery);
