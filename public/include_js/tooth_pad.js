/**
 * 牙位软键盘。
 *
 * 点行里的牙位格，网格贴着这一行弹出来；点牙即写进这一行，点空白处收起。
 * 和短语面板同一个思路 —— 要用的东西出现在光标旁边，而不是让人跑去别处。
 *
 * 替掉了原来的两条路：
 *
 *   选牙位模态框  盖住正在写的那一行，选完还要点「确认」再关掉。医生一行里
 *                 要改两次牙位就得开关两次弹窗。
 *   右侧栏牙位图  它其实是同一件事的远距离版本：一样的网格，但离光标很远，
 *                 而且要先让某一行获得焦点才知道往哪写。软键盘出现在行上，
 *                 「往哪写」不言自明，侧栏那份就没有存在理由了。
 *
 * 显示用部位记录法（恒牙 1-8、乳牙 Ⅰ-Ⅴ，象限靠位置表示），存的仍是 FDI 全码
 * —— 与行内十字图 toothSymbol() 同一套规则。
 */
(function () {
    'use strict';

    var $pad   = null;
    var $row   = null;     // 正在编辑牙位的那一行
    var positioning = false;   // 见 position()：自身滚动会触发 scroll 事件，防自激

    function ensurePad() {
        if ($pad) return $pad;

        var $src = $('#tooth-pad-template');
        if (!$src.length) return null;

        // 搬走而不是复制：模板里有 #tooth-pad-grid 这类 id，留着的话页面上同一个
        // id 会出现两次。虽然模板是 display:none 看不见，但重复 id 下
        // document.getElementById 只认得到第一个 —— 这套代码为重复 id 栽过一次
        // （46287dc）。用完即拆，不给下一个人留这个坑。
        $pad = $('<div class="tooth-pad" style="display:none"></div>')
            .append($src.children())
            .appendTo('body');
        $src.remove();

        // 点牙：加/减这一颗
        $pad.on('mousedown', '.tg-t', function (e) {
            e.preventDefault();          // 不要让当前行失焦
            toggle([String($(this).data('tooth'))]);
        });

        // 「全」：整象限一键选 / 再点取消
        $pad.on('mousedown', '.tg-all', function (e) {
            e.preventDefault();
            toggle(String($(this).data('teeth')).split(','));
        });

        // 牙位标记 △ 残根 / ✕ 已拔除 / — 缺失：落在行上，再点同一个取消
        $pad.on('mousedown', '.tooth-pad-mark', function (e) {
            e.preventDefault();
            if (!$row || !$row.length) return;
            CaseItems.setRowMark($row, $(this).data('mark'));
            paintMarks();
        });

        $pad.on('mousedown', '.tooth-pad-done', function (e) {
            e.preventDefault();
            hide();
        });

        return $pad;
    }

    function current() {
        if (!$row || !$row.length) return [];
        return CaseItems.splitTeeth($row.find('.case-item-tooth-value').val());
    }

    /**
     * 整组加或整组减。
     *
     * 组里**全都已选**才算「取消」，否则算「补齐」—— 点「全」时右上区已经选了两颗，
     * 医生要的是把整区选满，不是把那两颗取消掉。
     */
    function toggle(teeth) {
        if (!$row || !$row.length) return;

        var cur   = current();
        var allOn = teeth.every(function (t) { return cur.indexOf(t) !== -1; });

        var next = allOn
            ? cur.filter(function (t) { return teeth.indexOf(t) === -1; })
            : cur.concat(teeth.filter(function (t) { return cur.indexOf(t) === -1; }));

        // 一次写回，避免逐颗 toggle 触发多次重渲染
        CaseItems.setRowTooth($row, next);
        paint();
    }

    /** 已选的牙在网格上高亮 */
    function paint() {
        var cur = current();
        $pad.find('.tg-t').each(function () {
            $(this).toggleClass('selected', cur.indexOf(String($(this).data('tooth'))) !== -1);
        });
        paintMarks();
    }

    /** 这一行当前的标记按钮压下去 */
    function paintMarks() {
        var mark = ($row && $row.length) ? CaseItems.rowMark($row) : '';
        $pad.find('.tooth-pad-mark').each(function () {
            $(this).toggleClass('active', $(this).data('mark') === mark);
        });
    }

    /**
     * 贴着这一行的下方弹出。
     *
     * 下方放不下时**把页面滚一点**让它放得下，而不是翻到行的上方 ——
     * 翻上去会盖住正在参考的上文（写检查时主诉、现病史就在上面）。真实的软键盘
     * 也是这么做的：顶起内容，而不是盖住内容。
     * 只有滚到底了仍然放不下（面板比整个视口还高，正常不会）才退回翻上方。
     */
    function position($anchor) {
        // position() 自己会滚页面，而 scroll 事件又调 position() —— 不挡一下会自激。
        if (positioning) return;
        positioning = true;
        try { place($anchor); } finally {
            // 让本次滚动产生的 scroll 事件先走完再解锁
            setTimeout(function () { positioning = false; }, 0);
        }
    }

    function place($anchor) {
        var gap = 6;
        var ph  = $pad.outerHeight();
        var pw  = $pad.outerWidth();

        var r    = $anchor[0].getBoundingClientRect();
        var over = (r.bottom + ph + gap) - window.innerHeight;

        if (over > 0) {
            var before = window.scrollY;
            window.scrollBy(0, over + 8);
            // 滚动量可能不够（已经到底了），按实际滚了多少重新量
            if (window.scrollY !== before) {
                r = $anchor[0].getBoundingClientRect();
            }
        }

        var fitsBelow = (r.bottom + ph + gap) <= window.innerHeight;
        var left = Math.max(8, Math.min(r.left + window.scrollX, window.innerWidth - pw - 12));

        $pad.css({
            left: left + 'px',
            top: (fitsBelow ? r.bottom + window.scrollY + gap
                            : r.top + window.scrollY - ph - gap) + 'px'
        });
    }

    function show($toothBtn) {
        if (!ensurePad()) return;

        $row = $toothBtn.closest('.case-item-row');
        if (!$row.length) return;

        // 先收短语面板。两个浮层都贴着同一行弹出、z-index 都是 1060，同时开着
        // 必然互相遮挡（实测短语面板会压住半个牙位网格，那片区域的牙点不动）。
        //
        // 它不会自己关：本函数的调用方在 mousedown 里 preventDefault 了
        // （「不要让当前行失焦」），而短语面板恰恰是靠 textarea 失焦才关的。
        //
        // 只需要这一个方向 —— 反过来（面板开着时点文本框）现有逻辑会正确收起
        // 软键盘：那次点击落在面板外，走 document 的 mousedown 收起分支。
        if (window.PhrasePanel && window.PhrasePanel.hide) {
            window.PhrasePanel.hide();
        }

        $pad.show();
        paint();
        position($toothBtn);
    }

    function hide() {
        if ($pad) $pad.hide();
        $row = null;

        // 把 show() 里收起的短语面板还回去。焦点这一路都没离开过文本框，
        // 不主动还原的话它永远不会再出现（focus 事件不会二次触发）。
        if (window.PhrasePanel && window.PhrasePanel.restore) {
            window.PhrasePanel.restore();
        }
    }

    $(function () {
        // 行里的牙位格 —— 接管原来「打开模态框」的那个入口
        $(document).on('mousedown', '.js-pick-tooth', function (e) {
            e.preventDefault();
            e.stopPropagation();

            // 已经开在这一行上就收起来，再点一次是「关掉」
            if ($pad && $pad.is(':visible') && $row && $row.is($(this).closest('.case-item-row'))) {
                hide();
                return;
            }
            show($(this));
        });

        // 点别处收起。
        //
        // 用事件的 target 判断「点在不在面板里」，不用 mouseenter/mouseleave 记状态 ——
        // 触摸设备上手指点下去之前没有 mouseenter，靠状态标记的话点一颗牙就把面板
        // 关掉了。target 判断跟指针历史无关。
        $(document).on('mousedown', function (e) {
            if (!$pad || !$pad.is(':visible')) return;
            if ($pad[0] === e.target || $pad[0].contains(e.target)) return;
            if ($(e.target).closest('.js-pick-tooth').length) return;   // 由上面那个处理器管
            hide();
        });

        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') hide();
        });

        $(window).on('resize scroll', function () {
            if ($pad && $pad.is(':visible') && $row) {
                position($row.find('.js-pick-tooth'));
            }
        });
    });
})();
