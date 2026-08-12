/*
 * partials/file_picker.blade.php 的行为：选完文件后把文件名写到旁边的 span。
 *
 * 用事件委托绑在 document 上，因为这个控件多数长在 modal 里，modal 的 DOM 可能
 * 是页面加载后才塞进去的（也可能被 AJAX 换掉），直接绑元素会漏。
 */
(function () {
    'use strict';

    function render(input) {
        var wrapper = input.closest('[data-file-picker]');
        if (!wrapper) {
            return;
        }

        var label = wrapper.querySelector('[data-file-picker-name]');
        if (!label) {
            return;
        }

        var files = input.files;
        var count = files ? files.length : 0;

        if (count === 0) {
            // 表单 reset() 之后 files 会空掉，要回到「未选择任何文件」而不是留着旧文件名
            label.textContent = label.dataset.emptyText || '';
        } else if (count === 1) {
            label.textContent = files[0].name;
        } else {
            label.textContent = (label.dataset.manyText || '').replace(':count', count);
        }

        label.classList.toggle('is-chosen', count > 0);
    }

    document.addEventListener('change', function (event) {
        if (event.target && event.target.type === 'file') {
            render(event.target);
        }
    });

    /*
     * form.reset() 会清空 input.files 但**不会**触发 change，文件名标签会留着上一次
     * 选的文件名 —— 弹窗第二次打开时看着像已经选好了，其实没有。reset 事件在清值之前
     * 派发，所以要等这一轮事件循环跑完再读 files。
     */
    document.addEventListener('reset', function (event) {
        var form = event.target;
        if (!form || form.tagName !== 'FORM') {
            return;
        }

        setTimeout(function () {
            var inputs = form.querySelectorAll('[data-file-picker] input[type="file"]');
            Array.prototype.forEach.call(inputs, render);
        }, 0);
    });
})();
