/**
 * 锚定短语面板。
 *
 * 点进病历的任何一个字段，装着该字段短语的面板就贴在这个字段下方弹出来，
 * 按语义槽位分组，点一下追加一段文字 —— 医生点着点着一句话就成了。
 *
 * 为什么不是右侧栏：侧栏离光标远，而且一份短语表要同时服务九个字段，只能按
 * 「检查/诊断/治疗」这种大类平铺。锚定之后每个字段拿到的是**自己那一套槽位**：
 * 现病史是「时间 → 部位 → 症状 → 治疗情况 → 症状变化」，那是一句现病史的句子结构，
 * 顺着点下来自然成句；检查换成临床检查的知识结构（龋坏/非龋性/旧充填/牙周/…）。
 *
 * 两条约定与短语库一致（见 ClinicalPhraseLibrarySeeder）：
 *   标点在短语里 —— 这里不补任何分隔符，直接追加。
 *   {} 是光标位 —— 插入后光标落在该处，医生只补数值（「PD={}mm，」）。
 *
 * 侧栏那份保留作总览，与本面板共用同一批数据。
 */
(function () {
    'use strict';

    var CARET = '{}';

    var $panel   = null;
    var $target  = null;    // 当前要插入的 textarea
    var pinned   = false;   // 点了面板里的东西之后不要因为失焦就关掉

    function data() {
        return (window.MedicalRecordConfig && window.MedicalRecordConfig.phrasePanel) || {};
    }

    /** 这个输入框属于病历的哪一段 */
    function fieldOf($el) {
        var explicit = $el.data('phraseField');
        if (explicit) return explicit;

        // 分行明细的 textarea 没有 id，段落写在行所属的 .case-items-section 上
        var $sec = $el.closest('[data-section]');
        if ($sec.length) return $sec.data('section');

        return $el.attr('id') || '';
    }

    function ensurePanel() {
        if ($panel) return $panel;

        $panel = $('<div class="phrase-panel" style="display:none"></div>')
            .appendTo('body');

        // mousedown 而不是 click：click 之前浏览器已经把焦点从 textarea 挪走了，
        // 用 mousedown 才来得及在失焦前把插入位置记下来。
        $panel.on('mousedown', '.phrase-panel-item', function (e) {
            e.preventDefault();          // 别让 textarea 失焦
            insert($(this).data('phrase'));
        });

        $panel.on('mousedown', function () { pinned = true; });
        $panel.on('mouseup mouseleave', function () { pinned = false; });

        $panel.on('mousedown', '.phrase-panel-close', function (e) {
            e.preventDefault();
            hide();
        });

        return $panel;
    }

    function render(field) {
        var slots = data()[field];
        if (!slots) return false;

        var names = Object.keys(slots);
        if (!names.length) return false;

        var html = '<div class="phrase-panel-head">' +
                   '<span class="phrase-panel-title">' + escapeHtml(labelFor(field)) + '</span>' +
                   '<span class="phrase-panel-close">&times;</span></div>' +
                   '<div class="phrase-panel-body">';

        names.forEach(function (slot) {
            html += '<div class="phrase-slot">' +
                    '<div class="phrase-slot-name">' + escapeHtml(slot) + '</div>' +
                    '<div class="phrase-slot-items">';
            slots[slot].forEach(function (p) {
                html += '<span class="phrase-panel-item' + (p.indexOf(CARET) >= 0 ? ' has-caret' : '') +
                        '" data-phrase="' + escapeHtml(p) + '">' +
                        escapeHtml(p.replace(CARET, '__')) + '</span>';
            });
            html += '</div></div>';
        });

        ensurePanel().html(html + '</div>');
        return true;
    }

    /**
     * 段落名。
     *
     * 走服务端传下来的 phraseLabels，不走 LanguageManager —— 病历页只往
     * LanguageManager 里灌了 templates 一组，medical_cases.* 在前端拿不到，
     * 直接 trans 会把键名原样印出来（「present_illness」而不是「现病史」）。
     */
    function labelFor(field) {
        var labels = (window.MedicalRecordConfig && window.MedicalRecordConfig.phraseLabels) || {};
        return labels[field] || field;
    }

    /**
     * 贴到字段下方。
     *
     * 优先贴下方并按剩余空间压缩高度，而不是「放不下就整个翻上去」——
     * 翻上去会盖住正在参考的上文（写现病史时主诉就在上面），而医生正是照着
     * 上面那句往下写的。只有下方实在太窄（不足 160px）才翻上去。
     */
    function position($el) {
        var r   = $el[0].getBoundingClientRect();
        var pad = 6;
        var MIN = 160;

        var spaceBelow = window.innerHeight - r.bottom - pad - 8;
        var spaceAbove = r.top - pad - 8;
        var putBelow   = spaceBelow >= MIN || spaceBelow >= spaceAbove;

        // 先解开上一次设的高度，量出内容真实高度，再按可用空间收
        $panel.css('max-height', '');
        var natural = $panel.outerHeight();
        var room    = Math.max(MIN, Math.floor(putBelow ? spaceBelow : spaceAbove));
        var height  = Math.min(natural, room);

        $panel.css('max-height', height + 'px');

        var pw   = $panel.outerWidth();
        var left = Math.max(8, Math.min(r.left + window.scrollX, window.innerWidth - pw - 12));

        $panel.css({
            left: left + 'px',
            top: (putBelow
                    ? r.bottom + window.scrollY + pad
                    : r.top + window.scrollY - $panel.outerHeight() - pad) + 'px'
        });
    }

    function show($el) {
        var field = fieldOf($el);
        if (!field || !render(field)) { hide(); return; }

        $target = $el;
        $panel.show();
        position($el);
    }

    function hide() {
        if ($panel) $panel.hide();
        $target = null;
    }

    /**
     * 追加短语。
     *
     * 不补分隔符 —— 标点已经在短语里（「本院治疗，」「未治疗。」），
     * 再补一个会变成「本院治疗，，」。
     */
    function insert(phrase) {
        if (!$target || !phrase) return;

        var el   = $target[0];
        var pos  = typeof el.selectionStart === 'number' ? el.selectionStart : el.value.length;
        var head = el.value.substring(0, pos);
        var tail = el.value.substring(pos);

        var caretAt = phrase.indexOf(CARET);
        var text    = caretAt >= 0 ? phrase.replace(CARET, '') : phrase;

        el.value = head + text + tail;

        // 带 {} 的半成品把光标停在那个位置，医生只补数值；其余落到插入内容之后
        var next = pos + (caretAt >= 0 ? caretAt : text.length);
        el.selectionStart = el.selectionEnd = next;
        el.focus();

        // .val()/直接赋值不触发 input，不显式派发的话分行明细的派生文本还是旧的，
        // 保存下去等于这条短语没插（既有逻辑同样依赖这一步）。
        $target.trigger('input').trigger('change');

        position($target);
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    $(function () {
        var sel = '#medical-record-form textarea, #medical-record-form input[data-phrase-field]';

        $(document).on('focus', sel, function () { show($(this)); });

        $(document).on('blur', sel, function () {
            // 点面板里的短语也会让 textarea 失焦，pinned 期间不关
            setTimeout(function () { if (!pinned) hide(); }, 120);
        });

        $(window).on('resize scroll', function () {
            if ($panel && $panel.is(':visible') && $target) position($target);
        });

        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') hide();
        });
    });
})();
