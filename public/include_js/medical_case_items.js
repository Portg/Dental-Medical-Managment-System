/**
 * CaseItems —— 病历分段明细的分行编辑（牙位 + 文字）。
 *
 * 每段原来是一个 textarea + 整段共享的牙位标签列表，文字和牙位对不上号：
 * 「45 缺失」和「36 龋坏」揉在一段话里，牙位只是并列的标签集合。现在一行一个牙位。
 *
 * 与旧结构的三处关键差别：
 *   1. 牙位选择器绑到**行**，不是段落（openToothSelector 的目标从 'examination'
 *      变成具体某一行）
 *   2. 侧栏牙位图点一颗牙 = 给「当前聚焦的行」设牙位；没有聚焦行时给该段新建一行
 *   3. 旧的 syncTeethInTextFields（改牙位时对 textarea 做字符串替换）整块不需要了 ——
 *      那个 hack 存在的唯一原因就是牙位和文字分离，现在它们在同一行里
 *
 * 提交只带 case_items（隐藏字段）。每段那个 #<section> textarea 由行实时渲染但
 * 不提交（没有 name），只供既有的客户端必填校验与质量检查读取。
 */
var CaseItems = (function () {
    'use strict';

    var SECTIONS = ['examination', 'auxiliary_examination', 'diagnosis', 'treatment'];

    // 当前聚焦的行（侧栏牙位图和模板插入要知道往哪儿写）
    var focusedRow = null;

    function t(key, fallback) {
        if (typeof LanguageManager === 'undefined') return fallback;
        var v = LanguageManager.trans(key);
        return (!v || v === key) ? fallback : v;
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    // ─── 渲染 ────────────────────────────────────────────────────

    function rowHtml(section, tooth, content) {
        var toothLabel = tooth ? escapeHtml(tooth) : t('medical_cases.pick_tooth', '选牙位');
        return '' +
            '<div class="case-item-row" data-section="' + section + '">' +
              '<button type="button" class="case-item-tooth js-pick-tooth' + (tooth ? ' has-tooth' : '') + '"' +
                      ' title="' + t('medical_cases.pick_tooth', '选牙位') + '">' +
                '<span class="tooth-value">' + toothLabel + '</span>' +
              '</button>' +
              '<input type="hidden" class="case-item-tooth-value" value="' + escapeHtml(tooth || '') + '">' +
              '<textarea class="case-item-content" rows="2"' +
                       ' placeholder="' + t('medical_cases.item_content_placeholder', '描述…') + '">' +
                escapeHtml(content || '') +
              '</textarea>' +
              '<button type="button" class="case-item-remove js-remove-case-item"' +
                      ' title="' + t('common.delete', '删除') + '">&times;</button>' +
            '</div>';
    }

    function addRow(section, tooth, content, focus) {
        var $rows = $('#rows-' + section);
        if (!$rows.length) return null;

        var $row = $(rowHtml(section, tooth, content));
        $rows.append($row);
        syncDerived(section);

        if (focus) {
            $row.find('.case-item-content').focus();
        }
        return $row;
    }

    function renderSeed() {
        $('.js-case-items-seed').each(function () {
            var section = $(this).data('section');
            var rows;
            try { rows = JSON.parse($(this).text() || '[]'); } catch (e) { rows = []; }

            rows.forEach(function (r) {
                addRow(section, r.tooth_no, r.content, false);
            });

            // 空段落给一行空的，省得每次都要先点「添加」
            if (!rows.length) {
                addRow(section, '', '', false);
            }
        });
    }

    // ─── 序列化 ──────────────────────────────────────────────────

    function collect() {
        var out = [];
        SECTIONS.forEach(function (section) {
            $('#rows-' + section).find('.case-item-row').each(function () {
                var tooth = $(this).find('.case-item-tooth-value').val() || '';
                var content = $(this).find('.case-item-content').val() || '';
                if (!tooth.trim() && !content.trim()) return;   // 空行不提交
                out.push({ section: section, tooth_no: tooth.trim(), content: content.trim() });
            });
        });
        return out;
    }

    /**
     * 把行渲染进该段的隐藏 textarea。
     *
     * 格式与服务端 deriveColumnsFromItems() 必须一致（「牙位 空格 内容」逐行），
     * 否则客户端校验看到的和服务端存下来的不是同一段文字。
     */
    function syncDerived(section) {
        var lines = [];
        $('#rows-' + section).find('.case-item-row').each(function () {
            var tooth = ($(this).find('.case-item-tooth-value').val() || '').trim();
            var content = ($(this).find('.case-item-content').val() || '').trim();
            if (!tooth && !content) return;
            lines.push(tooth && content ? (tooth + ' ' + content) : (content || tooth));
        });
        $('#' + section).val(lines.join('\n'));
    }

    function syncAllDerived() {
        SECTIONS.forEach(syncDerived);
    }

    /** 提交前把行写进隐藏字段 —— doSaveMedicalRecord 会 serialize 整个表单 */
    function writeToForm() {
        var $input = $('#case_items_input');
        if (!$input.length) {
            $input = $('<input type="hidden" name="case_items" id="case_items_input">')
                .appendTo('#medical-record-form');
        }
        $input.val(JSON.stringify(collect()));
        syncAllDerived();
    }

    // ─── 牙位 ────────────────────────────────────────────────────

    function setRowTooth($row, tooth) {
        $row.find('.case-item-tooth-value').val(tooth || '');
        $row.find('.js-pick-tooth').toggleClass('has-tooth', !!tooth)
            .find('.tooth-value').text(tooth || t('medical_cases.pick_tooth', '选牙位'));

        // 模板插进来的 __ 占位符换成这一行的牙位。旧实现是拿整段的牙位串去替换，
        // 一行一个牙位之后这里才是对的粒度。
        var $content = $row.find('.case-item-content');
        if (tooth && $content.val() && $content.val().indexOf('__') !== -1) {
            $content.val($content.val().split('__').join(tooth));
        }

        syncDerived($row.data('section'));
    }

    /**
     * 侧栏牙位图 / 牙位选择器点一颗牙时的落点。
     * 有聚焦行就写进那一行；没有就在该段新建一行 —— 让「先点牙再写字」也能用。
     */
    function applyTooth(tooth, fallbackSection) {
        if (focusedRow && focusedRow.closest('body').length) {
            setRowTooth(focusedRow, tooth);
            return focusedRow;
        }
        var $row = addRow(fallbackSection || 'examination', tooth, '', true);
        if ($row) focusedRow = $row;
        return $row;
    }

    function getFocusedRow() {
        return (focusedRow && focusedRow.closest('body').length) ? focusedRow : null;
    }

    /** 当前所有已选牙位（侧栏牙位图高亮用） */
    function selectedTeeth() {
        var teeth = [];
        $('.case-item-row .case-item-tooth-value').each(function () {
            var v = ($(this).val() || '').trim();
            if (v && teeth.indexOf(v) === -1) teeth.push(v);
        });
        return teeth;
    }

    // ─── 绑定 ────────────────────────────────────────────────────

    function bind() {
        $(document).on('click', '.js-add-case-item', function () {
            addRow($(this).data('section'), '', '', true);
        });

        $(document).on('click', '.js-remove-case-item', function () {
            var $row = $(this).closest('.case-item-row');
            var section = $row.data('section');
            if (focusedRow && focusedRow.is($row)) focusedRow = null;
            $row.remove();
            // 段落删空了补一行，否则连「添加」按钮之外没有任何输入位
            if (!$('#rows-' + section).find('.case-item-row').length) {
                addRow(section, '', '', false);
            }
            syncDerived(section);
        });

        // 聚焦跟踪：侧栏牙位图、快捷短语、模板插入都要知道当前在哪一行
        $(document).on('focus', '.case-item-content', function () {
            focusedRow = $(this).closest('.case-item-row');
        });

        $(document).on('input', '.case-item-content', function () {
            syncDerived($(this).closest('.case-item-row').data('section'));
        });

        // 行内的牙位按钮：打开牙位选择器，目标是这一行
        $(document).on('click', '.js-pick-tooth', function () {
            focusedRow = $(this).closest('.case-item-row');
            if (typeof openToothSelector === 'function') {
                openToothSelector(focusedRow.data('section'));
            }
        });
    }

    function init() {
        if (!$('.case-items-section').length) return;
        bind();
        renderSeed();
        syncAllDerived();
    }

    return {
        init: init,
        collect: collect,
        writeToForm: writeToForm,
        applyTooth: applyTooth,
        setRowTooth: setRowTooth,
        getFocusedRow: getFocusedRow,
        selectedTeeth: selectedTeeth,
        addRow: addRow,
        syncDerived: syncDerived,
        SECTIONS: SECTIONS
    };
})();
