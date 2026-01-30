(function() {
    if (typeof jQuery.fn.select2 === 'undefined') {
        return;
    }

    jQuery.fn.select2.amd.define('select2/i18n/zh-CN', [], function () {     // Chinese (Simplified)
        return {
            errorLoading: function () {
                return '无法载入结果。';
            },
            inputTooLong: function (args) {
                var overChars = args.input.length - args.maximum;

                return '请删除' + overChars + '个字符';
            },
            inputTooShort: function (args) {
                var remainingChars = args.minimum - args.input.length;

                return '请再输入至少' + remainingChars + '个字符';
            },
            loadingMore: function () {
                return '载入更多结果…';
            },
            maximumSelected: function (args) {
                return '最多只能选择' + args.maximum + '个项目';
            },
            noResults: function () {
                return '未找到结果';
            },
            searching: function () {
                return '搜索中…';
            },
            removeAllItems: function () {
                return '删除所有项目';
            }
        };
    });
})();