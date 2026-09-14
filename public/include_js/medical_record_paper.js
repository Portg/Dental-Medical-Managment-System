/**
 * 病历纸的两件事：文本域跟着内容长高，以及打印。
 *
 * 为什么必须自动长高：纸是所见即所得的，而 textarea 只画出可见的那几行，
 * 超出的部分靠滚动。不处理的话，医生写了八行的检查、打印出来只有两行 ——
 * 病历上缺内容是医疗文书事故，不是显示问题。
 *
 * 高度必须是行高（26px，见 medical-record-paper.css）的整数倍，否则横格线
 * 会和字错位。先把 height 置回 auto 再读 scrollHeight，不然只会越长越高、
 * 删掉内容也收不回来。
 */
(function () {
    'use strict';

    var PAPER = '.mr-paper';

    function autoGrow(el) {
        if (!el || el.tagName !== 'TEXTAREA') return;
        el.style.height = 'auto';
        el.style.height = el.scrollHeight + 'px';
    }

    function growAll() {
        $(PAPER).find('textarea').each(function () {
            autoGrow(this);
        });
    }

    $(document)
        .on('input', PAPER + ' textarea', function () {
            autoGrow(this);
        })
        // 快捷短语 / 模板插入是用 .val() 写进去的，不触发 input 事件。
        // 那些代码插完都会 .trigger('change')，接住它补一次测量。
        .on('change', PAPER + ' textarea', function () {
            autoGrow(this);
        });

    /**
     * 打印前标记那些「屏幕上要、纸上不要」的东西。
     *
     * 为什么不能纯用 CSS：判断依据是 textarea 的 value 和 select 的选中值，
     * 这些都不反映在 DOM 属性上，选择器看不见。:has() 能解决一部分，但这套
     * 系统要跑在 Win7 的老浏览器上（见部署脚本那一串提交），不能指望它。
     */
    function markForPrint() {
        var $paper = $(PAPER);

        // 空行不印。医生只填了检查、其余三段留空时，纸上不该印出三个空框 ——
        // 纸质病历里没写的段落就是空白，不会预先画好格子。
        $paper.find('.case-item-row').each(function () {
            var tooth   = $.trim($(this).find('.case-item-tooth-value').val() || '');
            var content = $.trim($(this).find('.case-item-content').val() || '');
            $(this).toggleClass('mr-print-hide', tooth === '' && content === '');
        });

        // 没选 ICD 的诊断行不印那个下拉框
        $paper.find('.js-icd-select').each(function () {
            var $container = $(this).next('.select2-container');
            var empty = !$(this).val();
            $(this).toggleClass('mr-print-hide', empty);
            $container.toggleClass('mr-print-hide', empty);
        });

        // 就诊类型只印选中的那个
        $paper.find('.visit-type-radio').each(function () {
            $(this).toggleClass('mr-print-hide', !$(this).find('input[type="radio"]').prop('checked'));
        });

        // 「附加影像资料」「治疗项目」：没有内容时整块不印，免得纸上留一个
        // 孤零零的标签（按钮本身已经由 CSS 收掉了）。
        $paper.find('.soap-section-attached').each(function () {
            var hasImages   = $(this).find('#auxiliary-image-preview').children().length > 0;
            var hasServices = $(this).find('.service-tag').length > 0;
            $(this).toggleClass('mr-print-hide', !hasImages && !hasServices);
        });
    }

    /**
     * 打印。
     *
     * 打之前先把所有文本域重新量一遍：分行明细的行是 CaseItems 动态插进来的，
     * 插入时若还没测量过，高度停在默认的两行上，打出来就是截断的。
     */
    window.printMedicalRecord = function () {
        growAll();
        markForPrint();
        window.print();
    };

    $(function () {
        growAll();

        // 分行明细的行由 medical_case_items.js 动态插入（添加一行、带入上次病历、
        // 初始 seed 渲染），那边没有回调可挂，盯 DOM 变化最省事也最不易漏。
        if (window.MutationObserver) {
            var $rows = $(PAPER).find('.case-items-rows');
            if ($rows.length) {
                var observer = new MutationObserver(function () {
                    growAll();
                });
                $rows.each(function () {
                    observer.observe(this, { childList: true, subtree: true });
                });
            }
        }

        // 浏览器自带的打印（Ctrl+P）走的是同一张纸，不经过上面那个按钮，
        // 同样得先量、先标记。
        function prepare() {
            growAll();
            markForPrint();
        }

        if (window.matchMedia) {
            var mql = window.matchMedia('print');
            if (mql.addEventListener) {
                mql.addEventListener('change', function (e) {
                    if (e.matches) prepare();
                });
            } else if (mql.addListener) {
                mql.addListener(function (e) {
                    if (e.matches) prepare();
                });
            }
        }
        window.addEventListener('beforeprint', prepare);
    });
})();
