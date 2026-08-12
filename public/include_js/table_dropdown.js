/*
 * 表格「操作」下拉菜单的定位。配套 public/css/table-actions.css。
 *
 * 背景：DataTables 默认把表格包进 <div class="table-scrollable">，该容器是
 * overflow: auto hidden，下拉菜单展开会被纵向裁掉。Metronic 原来的做法是把整个
 * .btn-group 绝对定位来绕开裁剪，副作用是按钮脱离文档流、单元格塌成一行文字高，
 * 按钮戳出行边框（详见 table-actions.css 的注释）。
 *
 * 这里换成：按钮留在文档流里（行高正常），菜单展开的瞬间切成 position: fixed
 * 并按触发按钮的屏幕坐标摆放。fixed 元素不受祖先 overflow 裁剪，前提是祖先链上
 * 没有 transform / filter / will-change —— .table-scrollable 上没有。
 *
 * 不搬 DOM（不 appendTo(body)）：菜单里的 <a onclick="..."> 依赖的全局函数与
 * Bootstrap 自己的「点外面关闭」逻辑都建立在原有的 DOM 关系上，搬走容易出隐蔽的
 * 事件问题。只改 style 是最小侵入。
 */
(function ($) {
    'use strict';

    var GAP = 8;
    var PINNED = 'is-table-action-pinned';

    function pin(event) {
        var $group = $(event.currentTarget);
        if (!$group.closest('.table-scrollable').length) {
            return;
        }

        var $menu = $group.children('.dropdown-menu');
        if (!$menu.length) {
            return;
        }

        // 此刻菜单已经显示（shown 事件），量得到真实尺寸
        var rect = $group[0].getBoundingClientRect();
        var width = $menu.outerWidth();
        var height = $menu.outerHeight();

        // 操作列基本都贴着表格右边，菜单默认左对齐会顶出视口，放不下就改成右对齐
        var left = rect.left;
        if (left + width > window.innerWidth - GAP) {
            left = rect.right - width;
        }
        left = Math.max(GAP, left);

        // 下方空间不够且上方够，就往上翻
        var top = rect.bottom;
        if (top + height > window.innerHeight - GAP && rect.top - height > GAP) {
            top = rect.top - height;
        }

        $menu.addClass(PINNED).css({ top: top + 'px', left: left + 'px', right: 'auto' });
    }

    function unpin(event) {
        $(event.currentTarget).children('.dropdown-menu.' + PINNED)
            .removeClass(PINNED)
            .css({ top: '', left: '', right: '' });
    }

    $(document)
        .on('shown.bs.dropdown', '.table-scrollable .btn-group', pin)
        .on('hidden.bs.dropdown', '.table-scrollable .btn-group', unpin);

    /*
     * 菜单钉在视口坐标上，页面一滚就会和按钮脱节，所以滚动时直接关掉。
     * 用捕获阶段监听 document：滚动事件不冒泡，只有捕获才能同时收到
     * window 滚动和 .table-scrollable 自身的横向滚动。
     */
    function closeAll() {
        $('.table-scrollable .btn-group.open').removeClass('open').each(function () {
            // 手动补发 hidden 事件，让上面的 unpin 把内联样式清干净
            $(this).trigger('hidden.bs.dropdown');
        });
    }

    document.addEventListener('scroll', closeAll, true);
    window.addEventListener('resize', closeAll);
})(jQuery);
