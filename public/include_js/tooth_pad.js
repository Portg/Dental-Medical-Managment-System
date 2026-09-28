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
    var activeMark  = '';      // armed 的标记工具，见下面 .tooth-pad-mark 的注释

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

        // 点牙：armed 了标记就给这颗牙盖上（再点同一颗取消），否则加/减这一颗
        $pad.on('mousedown', '.tg-t', function (e) {
            e.preventDefault();          // 不要让当前行失焦
            var tooth = String($(this).data('tooth'));
            if (activeMark) {
                CaseItems.setToothMark($row, tooth, activeMark);
                paint();
            } else {
                toggle([tooth]);
            }
        });

        // 「全」始终只有一个含义：**它管的那些牙**。没拿笔是把它们选上，
        // 拿了笔是把符号盖到它们身上。
        //
        // 曾经让它在拿笔时改成「放一个不指名的象限码」—— 那是错的：医生拿着 ✕
        // 点「全」，预期是「这些牙全标成缺失」（整区缺失是真实的临床陈述），
        // 而那样做只会产生一条不指名的记录。预期若干颗被标记、实际 1 条，
        // 是会出错的数据，不是语义美不美的问题。
        $pad.on('mousedown', '.tg-all', function (e) {
            e.preventDefault();
            var teeth = String($(this).data('teeth')).split(',').filter(Boolean);
            if (activeMark) {
                CaseItems.setTeethMark($row, teeth, activeMark);
            } else {
                toggle(teeth);
            }
            paint();
        });

        // 牙位标记 △ 残根 / ✕ 已拔除 / — 缺失。
        //
        // 按下去是「拿起这支笔」，不是立刻涂到整行上 —— 标记现在是每个牙位
        // 各自一个，得让医生指明涂在哪。拿起笔后点牙位=给那颗牙盖章，点「全」=
        // 给那个区放一个光秃秃的符号。再点同一个按钮放下笔。
        $pad.on('mousedown', '.tooth-pad-mark', function (e) {
            e.preventDefault();
            if (!$row || !$row.length) return;
            var mark = String($(this).data('mark'));
            activeMark = (activeMark === mark) ? '' : mark;
            paintMarks();
        });

        $pad.on('mousedown', '.tooth-pad-done', function (e) {
            e.preventDefault();
            hide();
        });

        return $pad;
    }

    /**
     * 十字格 → 一位数字的象限码（见 MedicalCaseItem::isQuadrantCode）。
     * 恒牙 1-4 与 FDI 首位一致；乳牙 5-8 落在同一个格子里（toothQuadrant 只看首位），
     * 所以一个光秃秃的符号用恒牙码表示就够了。
     */
    function cellQuadrantCode(cls) {
        if (/\btq-tl\b/.test(cls)) return '1';
        if (/\btq-tr\b/.test(cls)) return '2';
        if (/\btq-br\b/.test(cls)) return '3';
        if (/\btq-bl\b/.test(cls)) return '4';
        return '';
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

    /** armed 的那支笔压下去；没 armed 时退回「整行同一个标记」的旧显示 */
    function paintMarks() {
        var shown = activeMark || (($row && $row.length) ? CaseItems.rowMark($row) : '');
        $pad.find('.tooth-pad-mark').each(function () {
            $(this).toggleClass('active', String($(this).data('mark')) === shown);
        });
        $pad.toggleClass('tooth-pad-armed', !!activeMark);

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

    /**
     * 收起软键盘。
     *
     * restorePhrase 传 false 表示「别把短语面板还回来」——按 Esc 时用，
     * Esc 的语义是把浮层全关掉，还回来一个等于没关。
     */
    function hide(restorePhrase) {
        // 本来就没开就什么都不做。下面那个 Esc 处理器是全局的、不带可见性判断，
        // 少了这道闸，医生在主诉里按一下 Esc 也会走到还原分支，把短语面板重新
        // 弹出来 —— 看起来就是 Esc 关不掉面板。
        var wasOpen = !!($pad && $pad.is(':visible'));

        activeMark = '';          // 收起时放下笔，下次打开是干净的
        if ($pad) $pad.hide();
        $row = null;

        if (!wasOpen || restorePhrase === false) {
            return;
        }

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

            var openHere = $pad && $pad.is(':visible') && $row && $row.is($(this).closest('.case-item-row'));

            // 手上拿着符号、软键盘又正开在这一行 —— 那这个十字就是**填写区**：
            // 点哪一格，符号就写进哪一格，不指名是哪颗牙（部位记录法里「符号直接
            // 取代数字」的写法）。软键盘管选具体牙位，十字管按区写符号，两条路并存。
            //
            // 没拿笔时仍然是开关软键盘，与原来一致。
            if (openHere && activeMark) {
                var code = cellQuadrantCode(String($(e.target).closest('.tq').attr('class') || ''));
                if (code) {
                    CaseItems.setToothMark($row, code, activeMark);
                    paint();
                    return;
                }
            }

            if (openHere) {
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
            if (e.key === 'Escape') hide(false);
        });

        $(window).on('resize scroll', function () {
            if ($pad && $pad.is(':visible') && $row) {
                position($row.find('.js-pick-tooth'));
            }
        });
    });
})();
