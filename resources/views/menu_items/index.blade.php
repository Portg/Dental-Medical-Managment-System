{{--
    Menu Items Management Page
    ============================
    Left-right split layout: tree + edit form
--}}
@extends(\App\Http\Helper\FunctionsHelper::navigation())

@section('css')
    @include('layouts.page_loader')
    <style>
        /* ── Layout ── */
        .menu-mgmt-container { display: flex; gap: 20px; min-height: 500px; }
        .menu-tree-panel { flex: 0 0 380px; border: 1px solid #e7ecf1; border-radius: 4px; background: #fff; }
        .menu-edit-panel { flex: 1; border: 1px solid #e7ecf1; border-radius: 4px; background: #fff; }
        .panel-header { padding: 12px 15px; border-bottom: 1px solid #e7ecf1; display: flex; align-items: center; justify-content: space-between; }
        .panel-header h4 { margin: 0; font-size: 15px; font-weight: 600; }
        .panel-body { padding: 15px; }

        /* ── Tree ── */
        .menu-tree ul { list-style: none; padding-left: 0; margin: 0; }
        .menu-tree ul ul { padding-left: 20px; }
        .tree-node { padding: 6px 10px; cursor: pointer; border-radius: 3px; display: flex; align-items: center; gap: 6px; margin-bottom: 2px; }
        .tree-node:hover { background: #f5f7fa; }
        .tree-node.active { background: #e8f0fe; color: #1a73e8; }
        .tree-node .node-icon { width: 18px; text-align: center; color: #999; font-size: 13px; }
        .tree-node .node-label { flex: 1; font-size: 13px; }
        .tree-node .node-toggle { width: 16px; cursor: pointer; color: #bbb; font-size: 11px; }
        .tree-node .node-url { font-size: 10px; color: #bbb; max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex-shrink: 0; }
        .tree-node.disabled { opacity: 0.5; }

        /* ── Form ── */
        .edit-form .form-group { margin-bottom: 12px; }
        .edit-form label { font-weight: 600; font-size: 13px; margin-bottom: 4px; }
        .edit-form .hint { font-size: 11px; color: #999; margin-top: 2px; }
        .no-selection { text-align: center; color: #999; padding: 60px 20px; }
        .no-selection i { font-size: 48px; color: #ddd; display: block; margin-bottom: 15px; }
        .title-preview { padding: 8px 12px; background: #f8f9fa; border-radius: 3px; font-size: 13px; min-height: 34px; }

        /* ── Actions ── */
        .tree-actions { display: flex; gap: 8px; }
        .tree-actions .btn { padding: 3px 10px; font-size: 12px; }
        .form-actions { margin-top: 20px; padding-top: 15px; border-top: 1px solid #e7ecf1; display: flex; gap: 10px; }
    </style>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-body">
                {{-- Page Header --}}
                <div class="page-header-l1" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                    <h1 class="page-title" style="margin: 0;">{{ __('menu_items.page_title') }}</h1>
                    <button type="button" class="btn btn-primary" onclick="createNew()">
                        {{ __('menu_items.add_new') }}
                    </button>
                </div>

                {{-- Left-Right Split --}}
                <div class="menu-mgmt-container">
                    {{-- Left: Tree --}}
                    <div class="menu-tree-panel">
                        <div class="panel-header">
                            <h4>{{ __('menu_items.menu_tree') }}</h4>
                            <div class="tree-actions">
                                <button class="btn btn-default btn-xs" onclick="expandAll()">{{ __('menu_items.expand_all') }}</button>
                                <button class="btn btn-default btn-xs" onclick="collapseAll()">{{ __('menu_items.collapse_all') }}</button>
                            </div>
                        </div>
                        <div class="panel-body">
                            <div id="menuTree" class="menu-tree">
                                <div class="text-center" style="padding: 30px; color: #999;">
                                    <i class="icon-refresh icon-spin"></i> {{ __('common.loading') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Right: Edit Form --}}
                    <div class="menu-edit-panel">
                        <div class="panel-header">
                            <h4 id="editPanelTitle">{{ __('menu_items.edit_form') }}</h4>
                        </div>
                        <div class="panel-body">
                            {{-- No Selection State --}}
                            <div id="noSelection" class="no-selection">
                                <i class="icon-cursor"></i>
                                {{ __('menu_items.no_selection') }}
                            </div>

                            {{-- Edit Form --}}
                            <div id="editFormContainer" class="edit-form" style="display: none;">
                                <input type="hidden" id="item_id" value="">

                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label>{{ __('menu_items.title_key') }}</label>
                                            <input type="text" id="title_key" class="form-control" placeholder="menu.patients_list">
                                            <div class="hint">{{ __('menu_items.title_key_hint') }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{ __('menu_items.preview') }}</label>
                                            <div id="titlePreview" class="title-preview">—</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>{{ __('menu_items.url') }}</label>
                                            <input type="text" id="url" class="form-control" placeholder="patients">
                                            <div class="hint">{{ __('menu_items.url_hint') }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>{{ __('menu_items.icon') }}</label>
                                            <input type="text" id="icon" class="form-control" placeholder="icon-users">
                                            <div class="hint">{{ __('menu_items.icon_hint') }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>{{ __('menu_items.parent') }}</label>
                                            <select id="parent_id" class="form-control">
                                                <option value="">{{ __('menu_items.parent_none') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>{{ __('menu_items.permission') }}</label>
                                            <select id="permission_id" class="form-control">
                                                <option value="">{{ __('menu_items.permission_none') }}</option>
                                                @foreach($permissions as $perm)
                                                    <option value="{{ $perm->id }}">[{{ $perm->module }}] {{ $perm->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{ __('menu_items.sort_order') }}</label>
                                            <input type="number" id="sort_order" class="form-control" value="0" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{ __('menu_items.is_active') }}</label>
                                            <div>
                                                <label style="font-weight: normal;">
                                                    <input type="checkbox" id="is_active" checked> {{ __('menu_items.is_active') }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-actions">
                                    <button type="button" class="btn btn-primary" id="btnSave" onclick="saveItem()">
                                        {{ __('menu_items.save') }}
                                    </button>
                                    <button type="button" class="btn btn-danger" id="btnDelete" onclick="deleteItem()" style="display: none;">
                                        {{ __('menu_items.delete') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="loading">
    <i class="icon-refresh" style="font-size: 24px;"></i><br/>
    <span>{{ __('common.loading') }}</span>
</div>
@endsection

@section('js')
<script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
<script type="text/javascript">
    var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
    var treeData = [];
    var flatItems = {};
    var currentItemId = null;
    var isCreateMode = false;

    // ── Translations PHP → JS ──
    var translations = @json(__('menu_items'));

    $(document).ready(function() {
        loadTree();

        // Title key → live preview
        $('#title_key').on('input', function() {
            var key = $(this).val();
            if (key) {
                // Try to resolve from loaded translations
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
            var isGroup = !item.url && hasChildren;
            var activeClass = (currentItemId === item.id) ? ' active' : '';
            var disabledClass = !item.is_active ? ' disabled' : '';

            html += '<li>';
            html += '<div class="tree-node' + activeClass + disabledClass + '" data-id="' + item.id + '" onclick="selectNode(' + item.id + ')">';

            // Toggle arrow
            if (hasChildren) {
                html += '<span class="node-toggle" onclick="event.stopPropagation(); toggleNode(this)"><i class="fa fa-caret-down"></i></span>';
            } else {
                html += '<span class="node-toggle"></span>';
            }

            // Icon
            if (item.icon) {
                html += '<span class="node-icon"><i class="' + item.icon + '"></i></span>';
            }

            // Label
            html += '<span class="node-label">' + escapeHtml(item.title) + '</span>';

            // URL path
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

        // Highlight
        $('#menuTree .tree-node').removeClass('active');
        $('#menuTree .tree-node[data-id="' + id + '"]').addClass('active');

        // Show form
        $('#noSelection').hide();
        $('#editFormContainer').show();
        $('#editPanelTitle').text(translations.edit_form || 'Edit Menu Item');
        $('#btnDelete').show();

        // Populate fields
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
        $('#editPanelTitle').text(translations.add_new || 'Add Menu Item');
        $('#btnDelete').hide();

        // Clear fields
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
                    swal("{{ __('common.success') }}", resp.message, "success");
                    if (isCreateMode && resp.data) {
                        currentItemId = resp.data.id;
                        isCreateMode = false;
                    }
                    loadTree();
                    // Re-select after reload
                    setTimeout(function() {
                        if (currentItemId) selectNode(currentItemId);
                    }, 300);
                } else {
                    swal("{{ __('common.error') }}", resp.message, "error");
                }
            },
            error: function(xhr) {
                $('.loading').hide();
                $('#btnSave').attr('disabled', false);
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Error';
                swal("{{ __('common.error') }}", msg, "error");
            }
        });
    }

    // ── Delete ──

    function deleteItem() {
        if (!currentItemId) return;
        swal({
            title: translations.confirm_delete || 'Confirm Delete',
            text: translations.confirm_delete_msg || 'Delete this item and all children?',
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: "{{ __('common.yes_delete') }}",
            cancelButtonText: "{{ __('common.cancel') }}",
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
                        swal("{{ __('common.deleted') }}", resp.message, "success");
                        currentItemId = null;
                        $('#editFormContainer').hide();
                        $('#noSelection').show();
                        loadTree();
                    } else {
                        swal("{{ __('common.error') }}", resp.message, "error");
                    }
                },
                error: function() {
                    $('.loading').hide();
                    swal("{{ __('common.error') }}", "{{ __('messages.error_occurred') }}", "error");
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
        // Attempt to resolve from server-side __() by matching known translations
        // For menu.* keys, use the translations loaded with the page
        if (parts.length === 2 && parts[0] === 'menu_items') {
            return translations[parts[1]] || null;
        }
        // For other keys, return null (server will resolve on save)
        return null;
    }
</script>
@endsection
