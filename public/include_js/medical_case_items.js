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

    // 段落 → 病历模板类型。与分行之前挂在整段 textarea 上的映射保持一致，
    // 否则同一个段落按 / 弹出来的模板会换一批。
    // 辅助检查只给快捷短语不给模板（检查结果因人而异，模板没意义）——原来也是这样。
    var TEMPLATE_TYPES = {
        examination: 'progress_note',
        diagnosis:   'diagnosis',
        treatment:   'treatment_plan'
    };

    // 当前聚焦的行（侧栏牙位图和模板插入要知道往哪儿写）
    var focusedRow = null;

    /**
     * FDI 牙位 → 十字图的象限（部位记录法 / Palmer 记号）。
     *
     * 十字是「面对患者」画的，所以患者的右侧落在图的左边：
     *   FDI 1x 右上 → 图的左上格      FDI 2x 左上 → 图的右上格
     *   FDI 4x 右下 → 图的左下格      FDI 3x 左下 → 图的右下格
     *   乳牙 5x/8x 同 1x/4x，6x/7x 同 2x/3x（乳牙序号写罗马数字，见 toothSymbol）
     *
     * 参考的那套桌面软件是把数字固定画在右上格的（十字纯装饰，45 明明是右下象限
     * 也画在右上）。那样十字就没有信息量了，这里按真正的记法定位 ——
     * 医生扫一眼十字就知道是哪个区，这才是这个图存在的理由。
     */
    function toothQuadrant(tooth) {
        var q = parseInt(String(tooth || '').charAt(0), 10);
        if (q === 1 || q === 5) return 'tl';   // 患者右上 → 左上格
        if (q === 2 || q === 6) return 'tr';   // 患者左上 → 右上格
        if (q === 4 || q === 8) return 'bl';   // 患者右下 → 左下格
        if (q === 3 || q === 7) return 'br';   // 患者左下 → 右下格
        return '';                              // 认不出就不定位，居中显示
    }

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

    /**
     * FDI 编号 → 十字里该写的符号（部位记录法）。
     *
     * 十字里只写**牙位序号**，象限由它落在哪一格表示 —— 写整个 FDI 编号是重复的，
     * 那个首位数字本身就是象限。
     *   恒牙（FDI 1x-4x）用阿拉伯数字 1-8：16 → 「6」
     *   乳牙（FDI 5x-8x）用罗马数字 Ⅰ-Ⅴ：55 → 「Ⅴ」
     * 这是中文牙科的通行写法，恒牙乳牙靠数字形式区分，不靠另加标记。
     */
    var ROMAN = ['', 'Ⅰ', 'Ⅱ', 'Ⅲ', 'Ⅳ', 'Ⅴ'];

    function toothSymbol(tooth) {
        var str = String(tooth || '');
        var quad = parseInt(str.charAt(0), 10);
        var pos  = parseInt(str.charAt(1), 10);

        if (!quad || !pos) return str;                    // 非 FDI 编号，原样显示
        if (quad >= 5 && quad <= 8) return ROMAN[pos] || str;   // 乳牙
        return String(pos);                                // 恒牙
    }

    /** 牙位十字图；无牙位时显示虚线占位 */
    function toothCrossHtml(tooth) {
        if (!tooth) {
            return '<span class="tooth-empty">' + t('medical_cases.pick_tooth', '选牙位') + '</span>';
        }
        // 2×2 网格，四个格子对应四个象限；牙位落在它真正所属的那一格。
        // 用网格而不是绝对定位：格子是结构性的，数字长短不会溢出到别的象限里。
        var q = toothQuadrant(tooth);
        var sym = escapeHtml(toothSymbol(tooth));
        // 完整 FDI 编号放 title：十字里是记法符号，需要精确编号时鼠标一停就能看到
        var title = ' title="' + escapeHtml(tooth) + '"';

        var cells = ['tl', 'tr', 'bl', 'br'].map(function (cell) {
            return '<span class="tq tq-' + cell + '">' + (cell === q ? sym : '') + '</span>';
        }).join('');

        // 认不出象限的牙位（非 FDI 编号）不装作知道在哪个区，居中显示
        return q
            ? '<span class="tooth-cross"' + title + '>' + cells + '</span>'
            : '<span class="tooth-cross tooth-cross-plain"' + title + '>' + escapeHtml(tooth) + '</span>';
    }

    function rowHtml(section, tooth, content) {
        return '' +
            '<div class="case-item-row" data-section="' + section + '">' +
              '<button type="button" class="case-item-tooth js-pick-tooth' + (tooth ? ' has-tooth' : '') + '"' +
                      ' title="' + t('medical_cases.pick_tooth', '选牙位') + '">' +
                toothCrossHtml(tooth) +
              '</button>' +
              '<input type="hidden" class="case-item-tooth-value" value="' + escapeHtml(tooth || '') + '">' +
              '<textarea class="case-item-content phrase-enabled' +
                        (TEMPLATE_TYPES[section] ? ' template-enabled' : '') + '" rows="2"' +
                       (TEMPLATE_TYPES[section] ? ' data-template-type="' + TEMPLATE_TYPES[section] + '"' : '') +
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
            .html(toothCrossHtml(tooth));

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

    /**
     * 往某一行的光标处插入文字，并刷新派生文本。
     *
     * 模板与快捷短语原来是直接 $field.val(...) 写整段 textarea 的；分行之后
     * 必须走这里 —— 一是要写进正确的那一行，二是 .val() 不触发 input 事件，
     * 不显式刷新的话派生文本还是旧的，保存下去等于没插。
     */
    function insertIntoRow($row, text) {
        if (!$row || !$row.length || !text) return;

        var $ta = $row.find('.case-item-content');
        var el = $ta[0];
        var val = $ta.val() || '';
        var pos = (el && typeof el.selectionStart === 'number') ? el.selectionStart : val.length;

        // 模板里的 __ 用这一行的牙位替换（旧实现用的是整段的牙位串）
        var tooth = ($row.find('.case-item-tooth-value').val() || '').trim();
        if (tooth) text = text.split('__').join(tooth);

        $ta.val(val.substring(0, pos) + text + val.substring(pos));
        if (el && el.setSelectionRange) {
            el.setSelectionRange(pos + text.length, pos + text.length);
        }
        $ta.focus();
        syncDerived($row.data('section'));
    }

    /** 该段最后一行；没有行就建一行 —— SOAP 模板批量填充用 */
    function lastRowOf(section) {
        var $rows = $('#rows-' + section).find('.case-item-row');
        return $rows.length ? $rows.last() : addRow(section, '', '', false);
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
        insertIntoRow: insertIntoRow,
        lastRowOf: lastRowOf,
        syncDerived: syncDerived,
        SECTIONS: SECTIONS
    };
})();
