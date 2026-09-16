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

    // 页面上真实存在的分行段落，按出现顺序。
    //
    // **不要在这里写死**：这份清单服务端也有一份（MedicalCaseItem::SECTIONS），
    // 两处各写一遍必然漂移 —— 加「治疗计划」段时只改了服务端，这里没跟上，
    // 结果行渲染得出来、提交时却收集不到，整段静默丢失（端到端实测撞到）。
    var ALL_SECTIONS = (function () {
        var found = [];
        $('.case-items-section[data-section]').each(function () {
            var s = $(this).data('section');
            if (s && found.indexOf(s) === -1) found.push(s);
        });
        return found.length ? found
            : ['examination', 'auxiliary_examination', 'diagnosis', 'treatment_plan', 'treatment'];
    })();

    // 提交时要收集的段。诊断不在其中 —— 它走 diagnoses 表（带 ICD 编码），
    // 由 collectDiagnoses() 单独收集。
    var SECTIONS = ALL_SECTIONS.filter(function (s) { return s !== 'diagnosis'; });

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

    /** 把 '16,17' 这种拆成数组 */
    function splitTeeth(value) {
        return String(value || '').split(/[,，\s]+/).map(function (x) { return x.trim(); })
            .filter(function (x) { return x !== ''; });
    }

    /**
     * 牙位十字图；无牙位时显示虚线占位。
     *
     * 一行可以带多颗牙，**同象限的合并在同一格里**：16、17 都在右上区，
     * 写成一格「76」，不用分两行各写一遍。这就是部位记录法的写法 ——
     * 十字分区，同区的牙位序号并排写。
     */
    function toothCrossHtml(tooth) {
        var teeth = splitTeeth(tooth);
        if (!teeth.length) {
            return '<span class="tooth-empty">' + t('medical_cases.pick_tooth', '选牙位') + '</span>';
        }

        var title = ' title="' + escapeHtml(teeth.join(', ')) + '"';

        // 按象限归拢；同象限内按牙位序号排序（从中线往外，与牙弓顺序一致）
        var byQuad = { tl: [], tr: [], bl: [], br: [] };
        var unknown = [];
        teeth.forEach(function (tth) {
            var q = toothQuadrant(tth);
            if (q) { byQuad[q].push(tth); } else { unknown.push(tth); }
        });

        if (unknown.length && !teeth.some(toothQuadrant)) {
            // 全是认不出的编号：不装作知道在哪个区，居中显示
            return '<span class="tooth-cross tooth-cross-plain"' + title + '>' +
                   escapeHtml(unknown.join(',')) + '</span>';
        }

        var cells = ['tl', 'tr', 'bl', 'br'].map(function (cell) {
            var list = byQuad[cell].slice().sort(function (a, b) {
                // 左侧两格靠中线在右，序号大的写在左边；右侧两格反之
                var d = parseInt(a.charAt(1), 10) - parseInt(b.charAt(1), 10);
                return (cell === 'tl' || cell === 'bl') ? -d : d;
            });
            return '<span class="tq tq-' + cell + '">' +
                   escapeHtml(list.map(toothSymbol).join('')) + '</span>';
        }).join('');

        return '<span class="tooth-cross"' + title + '>' + cells + '</span>';
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

        var $dx = $('#diagnosis_rows_input');
        if (!$dx.length) {
            $dx = $('<input type="hidden" name="diagnosis_rows" id="diagnosis_rows_input">')
                .appendTo('#medical-record-form');
        }
        $dx.val(JSON.stringify(collectDiagnoses()));

        syncAllDerived();
        syncDiagnosisDerived();
    }

    // ─── 牙位 ────────────────────────────────────────────────────

    function setRowTooth($row, tooth) {
        var value = Array.isArray(tooth) ? tooth.join(',') : (tooth || '');
        $row.find('.case-item-tooth-value').val(value);
        $row.find('.js-pick-tooth').toggleClass('has-tooth', !!value)
            .html(toothCrossHtml(value));

        // 模板插进来的 __ 占位符换成这一行的牙位。旧实现是拿整段的牙位串去替换，
        // 一行一个牙位之后这里才是对的粒度。
        var $content = $row.find('.case-item-content');
        if (value && $content.val() && $content.val().indexOf('__') !== -1) {
            $content.val($content.val().split('__').join(value));
        }

        // 诊断行走自己的派生（格式带 ICD），普通行走 syncDerived
        if ($row.hasClass('diagnosis-row')) {
            syncDiagnosisDerived();
        } else {
            syncDerived($row.data('section'));
        }
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
     * 在当前行里加/减一颗牙（侧栏牙位图点击用）。
     *
     * 一行可以带多颗：16、17 都在右上区，合并写在同一格里，不用分两行。
     * 已经在这一行里就取消，实现「再点一次去掉」。
     */
    function toggleToothOnRow($row, tooth, fallbackSection) {
        if (!$row || !$row.length) {
            var $new = addRow(fallbackSection || 'examination', tooth, '', true);
            if ($new) focusedRow = $new;
            return $new;
        }

        var teeth = splitTeeth($row.find('.case-item-tooth-value').val());
        var i = teeth.indexOf(tooth);
        if (i === -1) { teeth.push(tooth); } else { teeth.splice(i, 1); }

        setRowTooth($row, teeth);
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

        // {} 是光标位（短语库约定）；先处理 __ 再剥 {}，避免牙位替换挪动光标位
        var caretAt = String(text).indexOf('{}');
        var insertText = caretAt >= 0 ? String(text).replace('{}', '') : String(text);

        $ta.val(val.substring(0, pos) + insertText + val.substring(pos));
        if (el && el.setSelectionRange) {
            var next = pos + (caretAt >= 0 ? caretAt : insertText.length);
            el.setSelectionRange(next, next);
        }
        $ta.focus();
        syncDerived($row.data('section'));
    }

    /** 该段最后一行；没有行就建一行 —— SOAP 模板批量填充用 */
    function lastRowOf(section) {
        var $rows = $('#rows-' + section).find('.case-item-row');
        return $rows.length ? $rows.last() : addRow(section, '', '', false);
    }

    /** 侧栏牙位图高亮跟着走 */
    function updateAllCharts() {
        if (typeof updateMiniChartHighlights === 'function') {
            updateMiniChartHighlights();
        }
    }

    function getFocusedRow() {
        return (focusedRow && focusedRow.closest('body').length) ? focusedRow : null;
    }

    /** 当前所有已选牙位（侧栏牙位图高亮用） */
    function selectedTeeth() {
        var teeth = [];
        $('.case-item-row .case-item-tooth-value').each(function () {
            splitTeeth($(this).val()).forEach(function (v) {
                if (teeth.indexOf(v) === -1) teeth.push(v);
            });
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

        /**
         * 复制上一段的牙位。
         *
         * 检查写了 45、36，诊断和治疗多半也是这两颗 —— 不用再去牙位图上点一遍。
         * 只带牙位不带文字：诊断的内容和检查的内容本来就不一样，带过来反而要删。
         * 已有内容的行不动，只补齐缺的牙位、按需补行。
         */
        $(document).on('click', '.js-copy-teeth', function () {
            var section = $(this).data('section');

            // 往前找**最近一个有牙位的段**，而不是数组里紧挨着的上一段：
            // 诊断的前一段是「辅助检查」，那一段常常是空的，医生要的是「检查」里那几颗。
            var teeth = [];
            // 走 ALL_SECTIONS 而不是 SECTIONS：诊断同样是有牙位的一段，
            // 「治疗计划」往前找时不该把它跳过去。
            for (var i = ALL_SECTIONS.indexOf(section) - 1; i >= 0 && !teeth.length; i--) {
                $('#rows-' + ALL_SECTIONS[i]).find('.case-item-tooth-value').each(function () {
                    var v = ($(this).val() || '').trim();
                    if (v && teeth.indexOf(v) === -1) teeth.push(v);
                });
            }
            if (!teeth.length) return;

            // 一行可以带多颗，整段的牙位合并到一行；已有牙位的行不覆盖
            var $rows = $('#rows-' + section).find('.case-item-row');
            var $target = $rows.filter(function () {
                return !($(this).find('.case-item-tooth-value').val() || '').trim();
            }).first();

            if ($target.length) {
                setRowTooth($target, teeth);
            } else if (section === 'diagnosis') {
                addDiagnosisRow(teeth.join(','), '', '', '', false);
            } else {
                addRow(section, teeth.join(','), '', false);
            }
            updateAllCharts();
        });

        // 段落折叠：病历一长，来回滚很费劲
        // 折叠：段落头上的三角。原来是工具条里一个带「折叠/展开」文字的按钮，
        // 现在并进了段落头（见 case_items_section.blade.php），只剩图标。
    /**
     * 折叠 / 展开一个段落。
     *
     * 折叠态把内容藏起来但保留段落头 —— 医生一眼还能看到「这一段有没有写」，
     * 而不是整段消失。图标同时翻向，作为折叠状态的唯一指示（文字标签已去掉）。
     */
    function setCollapsed(section, collapse) {
        if (!section) return;

        var $rows = $('#rows-' + section);
        var $sec  = $('.case-items-section[data-section="' + section + '"]');

        $rows.toggle(!collapse);
        // 影像资料、治疗项目这些挂在段落下面的附属块跟着一起收
        $sec.nextUntil('.soap-section:not(.soap-section-attached)', '.soap-section-attached').toggle(!collapse);
        $sec.toggleClass('is-collapsed', collapse);
        $sec.find('.js-toggle-section i')
            .toggleClass('fa-chevron-down', !collapse)
            .toggleClass('fa-chevron-right', collapse);
    }

        $(document).on('click', '.js-toggle-section', function () {
            setCollapsed($(this).data('section'), $('#rows-' + $(this).data('section')).is(':visible'));
        });

        // 全部展开 / 全部折叠。参考产品的全局工具条上就有这两个 ——
        // 复诊翻旧病历时，一键折叠才看得过来。
        $(document).on('click', '.js-toggle-all-sections', function () {
            var collapse = $(this).data('collapse') === true || $(this).data('collapse') === 'true';
            $('.case-items-section').each(function () {
                setCollapsed($(this).data('section'), collapse);
            });
        });

        // 行内的牙位按钮：打开牙位选择器，目标是这一行
        $(document).on('click', '.js-pick-tooth', function () {
            focusedRow = $(this).closest('.case-item-row');
            if (typeof openToothSelector === 'function') {
                openToothSelector(focusedRow.data('section'));
            }
        });
    }

    // ─── 诊断行 ──────────────────────────────────────────────────
    //
    // 诊断不走 medical_case_items，直接写 diagnoses 表 —— 那张表带 ICD 编码、
    // 严重程度、转归状态，是另一张表存不了的（ICD 是医保与病案质控要的）。
    // 所以诊断行比普通行多一个 ICD 选择框，提交时走 diagnosis_rows 而不是 case_items。

    function diagnosisRowHtml(tooth, content, icd, icdText) {
        return '' +
            '<div class="case-item-row diagnosis-row">' +
              '<button type="button" class="case-item-tooth js-pick-tooth' + (tooth ? ' has-tooth' : '') + '"' +
                      ' title="' + t('medical_cases.pick_tooth', '选牙位') + '">' +
                toothCrossHtml(tooth) +
              '</button>' +
              '<input type="hidden" class="case-item-tooth-value" value="' + escapeHtml(tooth || '') + '">' +
              '<div class="diagnosis-fields">' +
                '<textarea class="case-item-content phrase-enabled template-enabled" rows="2"' +
                         ' data-template-type="diagnosis"' +
                         ' placeholder="' + t('medical_cases.diagnosis_placeholder', '填写诊断结论…') + '">' +
                  escapeHtml(content || '') +
                '</textarea>' +
                '<select class="form-control input-sm js-icd-select">' +
                  (icd ? '<option value="' + escapeHtml(icd) + '" selected>' +
                          escapeHtml(icdText || icd) + '</option>' : '') +
                '</select>' +
              '</div>' +
              '<button type="button" class="case-item-remove js-remove-diagnosis"' +
                      ' title="' + t('common.delete', '删除') + '">&times;</button>' +
            '</div>';
    }

    function addDiagnosisRow(tooth, content, icd, icdText, focus) {
        var $rows = $('#rows-diagnosis');
        if (!$rows.length) return null;

        var $row = $(diagnosisRowHtml(tooth, content, icd, icdText));
        $rows.append($row);
        initIcdSelect($row.find('.js-icd-select'));
        syncDiagnosisDerived();

        if (focus) $row.find('.case-item-content').focus();
        return $row;
    }

    /** ICD 编码选择器 —— 走既有的 /medical-cases/icd10-search 接口，不另起一套码表 */
    function initIcdSelect($el) {
        if (!$el.length || typeof $el.select2 !== 'function') return;
        $el.select2({
            placeholder: t('medical_cases.icd_placeholder', 'ICD 编码（选填）'),
            allowClear: true,
            width: '100%',
            minimumInputLength: 1,
            ajax: {
                url: '/api/icd10-codes',
                dataType: 'json',
                delay: 250,
                data: function (params) { return { q: params.term }; },
                processResults: function (data) { return { results: data || [] }; },
                cache: true
            }
        });
    }

    function collectDiagnoses() {
        var out = [];
        $('#rows-diagnosis').find('.diagnosis-row').each(function () {
            var content = ($(this).find('.case-item-content').val() || '').trim();
            if (!content) return;   // 只选牙位没写诊断名的行没有意义
            out.push({
                tooth_no: ($(this).find('.case-item-tooth-value').val() || '').trim(),
                content:  content,
                icd_code: $(this).find('.js-icd-select').val() || ''
            });
        });
        return out;
    }

    /** 与服务端 normalizeDiagnoses 的派生格式保持一致：「牙位 诊断名（ICD）」逐行 */
    function syncDiagnosisDerived() {
        var lines = [];
        $('#rows-diagnosis').find('.diagnosis-row').each(function () {
            var tooth = ($(this).find('.case-item-tooth-value').val() || '').trim();
            var content = ($(this).find('.case-item-content').val() || '').trim();
            if (!content) return;
            var icd = $(this).find('.js-icd-select').val() || '';
            var line = tooth ? (tooth + ' ' + content) : content;
            lines.push(icd ? line + '（' + icd + '）' : line);
        });
        $('#diagnosis').val(lines.join('\n'));
    }

    function renderDiagnosisSeed() {
        var $seed = $('.js-diagnosis-seed');
        if (!$seed.length) return;

        var rows;
        try { rows = JSON.parse($seed.text() || '[]'); } catch (e) { rows = []; }
        rows.forEach(function (r) {
            addDiagnosisRow(r.tooth_no, r.content, r.icd_code, r.icd_text, false);
        });
        if (!rows.length) addDiagnosisRow('', '', '', '', false);
    }

    function bindDiagnosis() {
        $(document).on('click', '.js-add-diagnosis', function () {
            addDiagnosisRow('', '', '', '', true);
        });

        $(document).on('click', '.js-remove-diagnosis', function () {
            var $row = $(this).closest('.diagnosis-row');
            if (focusedRow && focusedRow.is($row)) focusedRow = null;
            $row.remove();
            if (!$('#rows-diagnosis').find('.diagnosis-row').length) {
                addDiagnosisRow('', '', '', '', false);
            }
            syncDiagnosisDerived();
        });

        $(document).on('input', '#rows-diagnosis .case-item-content', syncDiagnosisDerived);
        $(document).on('change', '.js-icd-select', syncDiagnosisDerived);
    }

    function init() {
        if (!$('.case-items-section').length) return;
        bind();
        bindDiagnosis();
        renderSeed();
        renderDiagnosisSeed();
        syncAllDerived();
        syncDiagnosisDerived();
    }

    return {
        init: init,
        collect: collect,
        writeToForm: writeToForm,
        applyTooth: applyTooth,
        toggleToothOnRow: toggleToothOnRow,
        splitTeeth: splitTeeth,
        setRowTooth: setRowTooth,
        getFocusedRow: getFocusedRow,
        selectedTeeth: selectedTeeth,
        addRow: addRow,
        insertIntoRow: insertIntoRow,
        lastRowOf: lastRowOf,
        syncDerived: syncDerived,
        addDiagnosisRow: addDiagnosisRow,
        collectDiagnoses: collectDiagnoses,
        syncDiagnosisDerived: syncDiagnosisDerived,
        SECTIONS: SECTIONS
    };
})();
