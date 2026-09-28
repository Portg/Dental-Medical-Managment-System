/**
 * 椅旁工作台 —— 牙位图与治疗史联动。
 *
 * 口腔科的所有记录都挂在牙位上，所以牙位图不是一个可看可不看的图，是索引：
 * 点一颗牙，右侧就只剩这颗牙历次做过什么；取消选择回到全口历次就诊。
 *
 * 这条联动正是原来两个兄弟页签（牙齿图表 / 牙科记录）做不到的事。
 */
(function ($) {
    'use strict';

    var $root, url, currentTooth = '';

    function t(key, fallback) {
        return LanguageManager.trans('medical_treatment.' + key, fallback);
    }

    function esc(s) {
        if (!s) return '';
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function renderVisits(rows) {
        if (!rows.length) {
            return '<div class="cs-empty">' + t('cs_no_history', '暂无记录') + '</div>';
        }
        return rows.map(function (r) {
            return '<div class="cs-visit">'
                 + '<span class="cs-date">' + esc(r.date) + '</span>'
                 + '<span class="cs-what">' + esc(r.summary || t('cs_no_history', '暂无记录')) + '</span>'
                 + '<span class="cs-who">' + esc(r.doctor_name) + '</span>'
                 + '</div>';
        }).join('');
    }

    function renderToothRows(rows) {
        if (!rows.length) {
            return '<div class="cs-empty">' + t('cs_tooth_no_history', '这颗牙还没有记录') + '</div>';
        }
        return rows.map(function (r) {
            // 诊断行弱化：时间线的主干是「做了什么」
            var dx = r.section === 'diagnosis';
            return '<div class="cs-visit' + (dx ? ' is-dx' : '') + '">'
                 + '<span class="cs-date">' + esc(r.date) + '</span>'
                 + '<span class="cs-what">'
                 + (dx ? esc(t('cs_dx_prefix', '诊断：')) : '')
                 + esc(r.content) + '</span>'
                 + '<span class="cs-who">' + esc(r.doctor_name) + '</span>'
                 + '</div>';
        }).join('');
    }

    function load(tooth) {
        if (!url) return;
        currentTooth = tooth || '';

        $('#csHistBody').html('<div class="cs-empty">'
            + LanguageManager.trans('common.loading', '加载中…') + '</div>');

        $.getJSON(url, { tooth: currentTooth })
            .done(function (res) {
                var rows = (res && res.data) || [];
                $('#csHistBody').html(currentTooth ? renderToothRows(rows) : renderVisits(rows));
                $('#csHistTitle').text(currentTooth
                    ? currentTooth + ' ' + t('cs_tooth_history_suffix', '做过什么')
                    : t('cs_visit_history', '历次就诊'));
                $('#csHistAll').toggle(!!currentTooth);
            })
            .fail(function () {
                // 有 error 分支：静默失败会让医生以为这颗牙没记录
                $('#csHistBody').html('<div class="cs-empty">'
                    + LanguageManager.trans('common.error_message', '加载失败') + '</div>');
            });
    }

    $(function () {
        $root = $('#csHistory');
        if (!$root.length) return;
        url = $root.data('url');

        $('#csHistAll').on('click', function () { load(''); });

        // 牙位图是既有组件（dental_chart_editor.js），它不对外发任何事件。
        // 不去改它的内部实现——它同时还承担「涂色录入」，动它等于把两件事搅在一起；
        // 在外层监听同一个点击就够：它管涂色，我只管读牙位号。
        $(document).on('click.cshist', '.dce-tooth', function () {
            var tooth = String($(this).data('tooth') || '');
            // 再点同一颗＝取消聚焦，回到全口
            load(tooth === currentTooth ? '' : tooth);
        });
    });
})(jQuery);
