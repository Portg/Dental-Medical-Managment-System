/**
 * 病历模板的学科分类树。
 *
 * 两个用处，共用同一份拍平逻辑：
 *   - 筛选下拉与模板表单里的分类选择（拍平成带缩进的 option）
 *   - 分类管理弹窗（增 / 改名 / 移动 / 删）
 *
 * 注意与模板的「分类」字段区分：那个是归属范围（system/department/personal），
 * 这里是学科分类树。两个词在本模块里指的是两回事，见 App\TemplateCategory。
 */
(function ($) {
    'use strict';

    var TREE_URL = '/template-categories';
    var tree = [];

    function t(key, fallback) {
        return LanguageManager.trans('templates.' + key, fallback);
    }

    function csrf() {
        return window.csrfToken || $('meta[name="csrf-token"]').attr('content');
    }

    /** 深度优先拍平，带上层级，供带缩进的 option 用 */
    function flatten(nodes, depth, out) {
        depth = depth || 0;
        out = out || [];
        (nodes || []).forEach(function (node) {
            out.push({ node: node, depth: depth });
            flatten(node.children, depth + 1, out);
        });
        return out;
    }

    function indent(depth) {
        // 用全角空格 + 连字符：select 的 option 里 CSS 缩进在多数浏览器上不生效
        return depth === 0 ? '' : new Array(depth + 1).join('　') + '└ ';
    }

    /**
     * 拉整棵树。
     *
     * 必须有 error 分支：没有的话，接口 403/500 时这里什么都不做，而调用方
     * （openCategoryManager）又把 modal('show') 放在成功回调里 —— 结果是点了
     * 按钮毫无反应，连个报错都没有。这正是上线前漏掉的那个 bug。
     */
    function load(callback, onError) {
        $.getJSON(TREE_URL, function (res) {
            tree = (res && res.data) || [];
            if (callback) callback();
        }).fail(function (req) {
            var json = (req && req.responseJSON) || {};
            var msg = json.message || LanguageManager.trans('common.error_message', '加载失败');
            if (onError) {
                onError(msg);
            } else {
                toastr.error(msg);
            }
        });
    }

    /**
     * 把树填进一个 select，保留它原有的固定选项（全部 / 未归类 / 不归类）。
     */
    function fillSelect(selector, selectedId) {
        var $sel = $(selector);
        if (!$sel.length) return;

        $sel.find('option[data-tc="1"]').remove();

        flatten(tree).forEach(function (row) {
            var label = indent(row.depth) + row.node.name;
            if (row.node.total_count) {
                label += ' (' + row.node.total_count + ')';
            }
            $sel.append(
                $('<option data-tc="1"></option>').val(row.node.id).text(label)
            );
        });

        if (selectedId) {
            $sel.val(String(selectedId));
        }
    }

    // ── 管理弹窗 ────────────────────────────────────────────────

    function renderManager() {
        var rows = flatten(tree);
        if (!rows.length) {
            $('#tc-tree').html('<div class="tc-empty text-muted">'
                + t('category_uncategorized', '还没有分类') + '</div>');
            return;
        }

        var html = '';
        rows.forEach(function (row) {
            var n = row.node;
            html += '<div class="tc-row" data-id="' + n.id + '" data-depth="' + row.depth + '">';
            html += '<span class="tc-name" style="padding-left:' + (row.depth * 18) + 'px">'
                 + escapeHtml(n.name) + '</span>';
            html += '<span class="tc-count">' + (n.total_count || 0) + '</span>';
            html += '<span class="tc-actions">';
            // 三级封顶：到了第三级就不给「新增下级」，而不是让人点完了才报错
            if (row.depth < 2) {
                html += '<a href="javascript:;" class="tc-add-child">' + t('category_add_child', '新增下级') + '</a>';
            }
            html += '<a href="javascript:;" class="tc-rename">' + t('category_rename', '重命名') + '</a>';
            html += '<a href="javascript:;" class="tc-delete text-danger">' + t('category_delete', '删除') + '</a>';
            html += '</span>';
            html += '</div>';
        });

        $('#tc-tree').html(html);
    }

    function post(url, method, data, done) {
        $.ajax({
            url: url,
            type: 'POST',
            data: $.extend({ _token: csrf(), _method: method }, data),
            success: function (res) {
                toastr.success((res && res.message) || '');
                load(function () {
                    renderManager();
                    refreshSelects();
                    if (typeof dataTable !== 'undefined' && dataTable) dataTable.ajax.reload(null, false);
                });
                if (done) done();
            },
            error: function (req) {
                var json = req.responseJSON || {};
                toastr.error(json.message || LanguageManager.trans('common.error_message'));
            }
        });
    }

    function refreshSelects() {
        fillSelect('#filter_template_category', $('#filter_template_category').val());
        fillSelect('#template_category_id', $('#template_category_id').val());
    }

    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ── 对外 ────────────────────────────────────────────────────

    window.openCategoryManager = function () {
        // 先开窗再加载：把 show 放在成功回调里，失败时就是「点了没反应」
        $('#tc-tree').html('<div class="tc-empty text-muted">'
            + LanguageManager.trans('common.loading', '加载中…') + '</div>');
        $('#category-manager-modal').modal('show');

        load(renderManager, function (msg) {
            $('#tc-tree').html('<div class="tc-empty text-danger">' + escapeHtml(msg) + '</div>');
        });
    };

    /** 模板表单打开时调用，把树填进去并选中当前值 */
    window.fillTemplateCategorySelect = function (selectedId) {
        if (!tree.length) {
            load(function () { fillSelect('#template_category_id', selectedId); });
        } else {
            fillSelect('#template_category_id', selectedId);
        }
    };

    $(function () {
        if (!$('#filter_template_category').length && !$('#tc-tree').length) {
            return;
        }

        load(refreshSelects);

        $('#filter_template_category').on('change', function () {
            if (typeof dataTable !== 'undefined' && dataTable) dataTable.ajax.reload();
        });

        $('#tc-add-root').on('click', function () {
            var name = window.prompt(t('category_add_root', '新增一级分类'));
            if (!name) return;
            post(TREE_URL, 'POST', { name: name });
        });

        $('#tc-tree').on('click', '.tc-add-child', function () {
            var id = $(this).closest('.tc-row').data('id');
            var name = window.prompt(t('category_add_child', '新增下级'));
            if (!name) return;
            post(TREE_URL, 'POST', { name: name, parent_id: id });
        });

        $('#tc-tree').on('click', '.tc-rename', function () {
            var $row = $(this).closest('.tc-row');
            var name = window.prompt(t('category_rename', '重命名'), $row.find('.tc-name').text().trim());
            if (!name) return;
            post(TREE_URL + '/' + $row.data('id'), 'PUT', { name: name });
        });

        $('#tc-tree').on('click', '.tc-delete', function () {
            if (!window.confirm(t('category_delete_confirm', '删除这个分类？'))) return;
            var id = $(this).closest('.tc-row').data('id');
            post(TREE_URL + '/' + id, 'DELETE', {});
        });
    });
})(jQuery);
