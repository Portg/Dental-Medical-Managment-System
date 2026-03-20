var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
var treeData = [];
var flatItems = {};
var currentItemId = null;
var isCreateMode = false;

$(document).ready(function() {
    loadTree();

    // Title key → live preview
    $('#title_key').on('input', function() {
        var key = $(this).val();
        if (key) {
            var parts = key.split('.');
            var resolved = resolveTranslation(parts);
            $('#titlePreview').text(resolved || key);
        } else {
            $('#titlePreview').text('—');
        }
    });
});

// ── Tree Loading ──

function loadTree() {
    $.ajax({
        url: '/menu-items/tree',
        type: 'GET',
        dataType: 'json',
        success: function(resp) {
            if (resp.status) {
                treeData = resp.data;
                flatItems = {};
                flattenTree(treeData);
                renderTree();
                populateParentSelect();
            }
        }
    });
}

function flattenTree(items) {
    items.forEach(function(item) {
        flatItems[item.id] = item;
        if (item.children && item.children.length) {
            flattenTree(item.children);
        }
    });
}

// ── Tree Rendering ──

function renderTree() {
    var html = buildTreeHtml(treeData, 0);
    $('#menuTree').html('<ul>' + html + '</ul>');
}

function buildTreeHtml(items, level) {
    var html = '';
    items.forEach(function(item) {
        var hasChildren = item.children && item.children.length > 0;
        var activeClass = (currentItemId === item.id) ? ' active' : '';
        var disabledClass = !item.is_active ? ' disabled' : '';

        html += '<li>';
        html += '<div class="tree-node' + activeClass + disabledClass + '" data-id="' + item.id + '" onclick="selectNode(' + item.id + ')">';

        if (hasChildren) {
            html += '<span class="node-toggle" onclick="event.stopPropagation(); toggleNode(this)"><i class="fa fa-caret-down"></i></span>';
        } else {
            html += '<span class="node-toggle"></span>';
        }

        if (item.icon) {
            html += '<span class="node-icon"><i class="' + item.icon + '"></i></span>';
        }

        html += '<span class="node-label">' + escapeHtml(item.title) + '</span>';

        if (item.url) {
            html += '<span class="node-url" title="/' + escapeHtml(item.url) + '">/' + escapeHtml(item.url) + '</span>';
        }

        html += '</div>';

        if (hasChildren) {
            html += '<ul class="children-list">' + buildTreeHtml(item.children, level + 1) + '</ul>';
        }

        html += '</li>';
    });
    return html;
}

function toggleNode(el) {
    var $ul = $(el).closest('li').children('ul');
    var $icon = $(el).find('i');
    if ($ul.is(':visible')) {
        $ul.slideUp(150);
        $icon.removeClass('fa-caret-down').addClass('fa-caret-right');
    } else {
        $ul.slideDown(150);
        $icon.removeClass('fa-caret-right').addClass('fa-caret-down');
    }
}

function expandAll() {
    $('#menuTree ul.children-list').slideDown(150);
    $('#menuTree .node-toggle i').removeClass('fa-caret-right').addClass('fa-caret-down');
}

function collapseAll() {
    $('#menuTree ul.children-list').slideUp(150);
    $('#menuTree .node-toggle i').removeClass('fa-caret-down').addClass('fa-caret-right');
}

// ── Parent Select Dropdown ──

function populateParentSelect() {
    var $sel = $('#parent_id');
    var currentVal = $sel.val();
    $sel.find('option:not(:first)').remove();
    addParentOptions(treeData, $sel, 0);
    $sel.val(currentVal);
}

function addParentOptions(items, $sel, depth) {
    items.forEach(function(item) {
        var prefix = '—'.repeat(depth) + (depth > 0 ? ' ' : '');
        $sel.append('<option value="' + item.id + '">' + prefix + escapeHtml(item.title) + '</option>');
        if (item.children && item.children.length) {
            addParentOptions(item.children, $sel, depth + 1);
        }
    });
}

// ── Node Selection ──

function selectNode(id) {
    currentItemId = id;
    isCreateMode = false;
    var item = flatItems[id];
    if (!item) return;

    $('#menuTree .tree-node').removeClass('active');
    $('#menuTree .tree-node[data-id="' + id + '"]').addClass('active');

    $('#noSelection').hide();
    $('#editFormContainer').show();
    $('#editPanelTitle').text(LanguageManager.trans('menu_items.edit_form'));
    $('#btnDelete').show();

    $('#item_id').val(item.id);
    $('#title_key').val(item.title_key).trigger('input');
    $('#url').val(item.url || '');
    $('#icon').val(item.icon || '');
    $('#parent_id').val(item.parent_id || '');
    $('#permission_id').val(item.permission_id || '');
    $('#sort_order').val(item.sort_order || 0);
    $('#is_active').prop('checked', item.is_active);
}

// ── Create New ──

function createNew() {
    currentItemId = null;
    isCreateMode = true;

    $('#menuTree .tree-node').removeClass('active');
    $('#noSelection').hide();
    $('#editFormContainer').show();
    $('#editPanelTitle').text(LanguageManager.trans('menu_items.add_new'));
    $('#btnDelete').hide();

    $('#item_id').val('');
    $('#title_key').val('').trigger('input');
    $('#url').val('');
    $('#icon').val('');
    $('#parent_id').val('');
    $('#permission_id').val('');
    $('#sort_order').val(0);
    $('#is_active').prop('checked', true);
}

// ── Save ──

function saveItem() {
    var data = {
        _token: CSRF_TOKEN,
        title_key: $('#title_key').val(),
        url: $('#url').val() || null,
        icon: $('#icon').val() || null,
        parent_id: $('#parent_id').val() || null,
        permission_id: $('#permission_id').val() || null,
        sort_order: parseInt($('#sort_order').val()) || 0,
        is_active: $('#is_active').is(':checked') ? 1 : 0
    };

    var url, method;
    if (isCreateMode) {
        url = '/menu-items';
        method = 'POST';
    } else {
        url = '/menu-items/' + currentItemId;
        method = 'PUT';
    }

    $('.loading').show();
    $('#btnSave').attr('disabled', true);

    $.ajax({
        url: url,
        type: method,
        data: data,
        dataType: 'json',
        success: function(resp) {
            $('.loading').hide();
            $('#btnSave').attr('disabled', false);
            if (resp.status) {
                swal(LanguageManager.trans('common.success'), resp.message, "success");
                if (isCreateMode && resp.data) {
                    currentItemId = resp.data.id;
                    isCreateMode = false;
                }
                loadTree();
                setTimeout(function() {
                    if (currentItemId) selectNode(currentItemId);
                }, 300);
            } else {
                swal(LanguageManager.trans('common.error'), resp.message, "error");
            }
        },
        error: function(xhr) {
            $('.loading').hide();
            $('#btnSave').attr('disabled', false);
            var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Error';
            swal(LanguageManager.trans('common.error'), msg, "error");
        }
    });
}

// ── Delete ──

function deleteItem() {
    if (!currentItemId) return;
    swal({
        title: LanguageManager.trans('menu_items.confirm_delete'),
        text: LanguageManager.trans('menu_items.confirm_delete_msg'),
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn-danger",
        confirmButtonText: LanguageManager.trans('common.yes_delete'),
        cancelButtonText: LanguageManager.trans('common.cancel'),
        closeOnConfirm: false
    }, function() {
        $('.loading').show();
        $.ajax({
            url: '/menu-items/' + currentItemId,
            type: 'DELETE',
            data: { _token: CSRF_TOKEN },
            dataType: 'json',
            success: function(resp) {
                $('.loading').hide();
                if (resp.status) {
                    swal(LanguageManager.trans('common.deleted'), resp.message, "success");
                    currentItemId = null;
                    $('#editFormContainer').hide();
                    $('#noSelection').show();
                    loadTree();
                } else {
                    swal(LanguageManager.trans('common.error'), resp.message, "error");
                }
            },
            error: function() {
                $('.loading').hide();
                swal(LanguageManager.trans('common.error'), LanguageManager.trans('messages.error_occurred'), "error");
            }
        });
    });
}

// ── Helpers ──

function escapeHtml(str) {
    if (!str) return '';
    return $('<div>').text(str).html();
}

function resolveTranslation(parts) {
    if (parts.length === 2 && parts[0] === 'menu_items') {
        return LanguageManager.trans('menu_items.' + parts[1]) || null;
    }
    return null;
}
