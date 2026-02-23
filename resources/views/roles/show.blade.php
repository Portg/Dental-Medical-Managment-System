@extends('layouts.app')

@section('css')
<style>
    /* ============================================================
       角色详情页 - Tab导航增强
       ============================================================ */
    .tabbable-line > .nav-tabs {
        border-bottom: 2px solid #ebeef5;
        margin-bottom: 0;
    }
    .tabbable-line > .nav-tabs > li > a {
        color: #606266;
        font-size: 14px;
        font-weight: 500;
        padding: 10px 20px;
        border: none;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
        transition: color 0.2s, border-color 0.2s;
    }
    .tabbable-line > .nav-tabs > li > a:hover {
        color: #00838f;
        background: transparent;
        border-color: transparent;
        border-bottom-color: #b2ebf2;
    }
    .tabbable-line > .nav-tabs > li.active > a,
    .tabbable-line > .nav-tabs > li.active > a:hover,
    .tabbable-line > .nav-tabs > li.active > a:focus {
        color: #00838f;
        background: transparent;
        border: none;
        border-bottom: 2px solid #00838f;
    }
    .tab-pane {
        padding: 20px 0;
    }

    /* ============================================================
       角色详情页 - 面包屑与容器
       ============================================================ */
    .role-breadcrumb a {
        color: #00838f;
    }
    .role-badge {
        display: inline-block;
        padding: 2px 10px;
        background: #e0f7fa;
        color: #00838f;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 500;
        margin-left: 4px;
    }

    /* ============================================================
       Tab 1 - 基本信息表格美化
       ============================================================ */
    .role-info-table {
        max-width: 600px;
    }
    .role-info-table td {
        padding: 10px 16px;
        border-bottom: 1px solid #f0f0f0;
    }
    .role-info-table td:first-child {
        font-weight: 600;
        width: 140px;
        color: #606266;
        background: #fafbfc;
    }
    .role-info-table tr:last-child td {
        border-bottom: none;
    }

    /* ============================================================
       Tab 2 - 权限配置：工具栏
       ============================================================ */
    .perm-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        margin-bottom: 16px;
        border-bottom: 1px solid #ebeef5;
    }
    .perm-toolbar-left {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    .perm-search-wrap {
        position: relative;
        width: 240px;
    }
    .perm-search-icon {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #909399;
        font-size: 13px;
    }
    .perm-search-wrap .form-control {
        padding-left: 32px;
        height: 32px;
        border-radius: 6px;
        font-size: 13px;
    }
    .perm-toolbar-actions {
        display: flex;
        gap: 6px;
    }
    .perm-toolbar-actions .btn {
        border-radius: 4px;
    }

    /* ============================================================
       Tab 2 - 权限配置：模块卡片
       ============================================================ */
    .perm-module-card {
        margin-bottom: 12px;
        border: 1px solid #e4e7ed;
        border-radius: 8px;
        overflow: hidden;
        background: #fff;
        transition: box-shadow 0.2s;
    }
    .perm-module-card:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    .perm-module-card.is-collapsed .perm-module-body {
        display: none;
    }
    .perm-module-card.is-collapsed .perm-collapse-icon {
        transform: rotate(-90deg);
    }
    .perm-module-card.search-hidden {
        display: none;
    }
    .perm-module-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 16px;
        background: linear-gradient(to bottom, #fafbfc, #f5f7fa);
        border-bottom: 1px solid #ebeef5;
        cursor: pointer;
        user-select: none;
    }
    .perm-module-header:hover {
        background: linear-gradient(to bottom, #f5f7fa, #f0f2f5);
    }
    .perm-module-header-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .perm-collapse-icon {
        color: #909399;
        font-size: 12px;
        transition: transform 0.2s;
        width: 14px;
    }
    .perm-module-title {
        margin: 0;
        font-size: 14px;
        font-weight: 600;
        color: #303133;
    }
    .perm-module-badge {
        display: inline-block;
        padding: 1px 8px;
        font-size: 12px;
        border-radius: 10px;
        background: #e0f7fa;
        color: #00838f;
        font-weight: 500;
    }
    .perm-module-badge.all-checked {
        background: #00838f;
        color: #fff;
    }
    .btn-select-module {
        border-radius: 4px;
        font-size: 12px;
    }

    /* ============================================================
       Tab 2 - 权限配置：模块体（网格布局）
       ============================================================ */
    .perm-module-body {
        padding: 12px 16px;
    }
    .perm-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 6px 16px;
    }
    @media (max-width: 1200px) {
        .perm-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 768px) {
        .perm-grid { grid-template-columns: 1fr; }
        .perm-toolbar { flex-direction: column; gap: 10px; align-items: stretch; }
        .perm-toolbar-left { flex-direction: column; }
        .perm-search-wrap { width: 100%; }
    }

    /* ============================================================
       Tab 2 - 权限配置：权限条目（自定义 checkbox）
       ============================================================ */
    .perm-item {
        display: flex;
        align-items: center;
        padding: 6px 8px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: normal;
        margin: 0;
        transition: background 0.15s;
    }
    .perm-item:hover {
        background: #f5f7fa;
    }
    .perm-item.search-hidden {
        display: none;
    }
    .perm-item.search-highlight {
        background: #fffbe6;
    }
    .perm-item input[type="checkbox"] {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }
    .perm-checkbox-custom {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid #c0c4cc;
        border-radius: 3px;
        margin-right: 8px;
        flex-shrink: 0;
        position: relative;
        transition: all 0.15s;
    }
    .perm-item input:checked + .perm-checkbox-custom {
        background: #00838f;
        border-color: #00838f;
    }
    .perm-item input:checked + .perm-checkbox-custom::after {
        content: '';
        position: absolute;
        left: 3px;
        top: 0;
        width: 6px;
        height: 10px;
        border: solid #fff;
        border-width: 0 2px 2px 0;
        transform: rotate(45deg);
    }
    .perm-item input:focus + .perm-checkbox-custom {
        box-shadow: 0 0 0 2px rgba(0, 131, 143, 0.2);
    }
    .perm-item-text {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 13px;
        color: #303133;
    }
    .perm-item-name {
        white-space: nowrap;
    }
    .perm-item-info {
        color: #c0c4cc;
        font-size: 12px;
        cursor: help;
    }
    .perm-item-info:hover {
        color: #00838f;
    }

    /* ============================================================
       Tab 2 - 权限配置：无搜索结果
       ============================================================ */
    .perm-no-results {
        text-align: center;
        padding: 40px 20px;
        color: #909399;
        font-size: 14px;
    }
    .perm-no-results i {
        font-size: 32px;
        margin-bottom: 8px;
        display: block;
        color: #c0c4cc;
    }

    /* ============================================================
       Tab 3 - 菜单可见性
       ============================================================ */
    .sidebar-preview-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        margin-bottom: 16px;
        border-bottom: 1px solid #ebeef5;
    }
    .sidebar-hint {
        margin: 0;
        font-size: 13px;
        color: #909399;
    }
    .sidebar-toolbar-actions {
        display: flex;
        gap: 8px;
    }

    /* 菜单树容器 */
    .sidebar-mock {
        border: 1px solid #e4e7ed;
        border-radius: 8px;
        background: #fff;
        overflow: hidden;
    }
    .sidebar-mock-header {
        padding: 12px 16px;
        color: #303133;
        font-size: 14px;
        font-weight: 600;
        background: linear-gradient(to bottom, #fafbfc, #f5f7fa);
        border-bottom: 1px solid #ebeef5;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .sidebar-mock-header i { color: #909399; }
    .sidebar-mock-menu {
        list-style: none;
        padding: 6px 0;
        margin: 0;
    }
    .sidebar-mock-menu ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    /* 节点行 */
    .sp-node-row {
        display: flex;
        align-items: center;
        padding: 7px 16px;
        gap: 10px;
        cursor: default;
        transition: background 0.15s;
        border-radius: 4px;
        margin: 1px 6px;
    }
    .sp-node-row:hover {
        background: #f5f7fa;
    }
    .sp-level-2 .sp-node-row { padding-left: 34px; }
    .sp-level-3 .sp-node-row { padding-left: 52px; }

    /* Toggle 开关 */
    .sp-switch {
        position: relative;
        display: inline-block;
        width: 36px;
        height: 20px;
        flex-shrink: 0;
        margin: 0;
        cursor: pointer;
    }
    .sp-switch input {
        opacity: 0;
        width: 0;
        height: 0;
        position: absolute;
    }
    .sp-slider {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: #c0c4cc;
        border-radius: 10px;
        transition: background 0.25s;
    }
    .sp-slider::before {
        content: '';
        position: absolute;
        width: 16px;
        height: 16px;
        left: 2px;
        bottom: 2px;
        background: #fff;
        border-radius: 50%;
        transition: transform 0.25s;
        box-shadow: 0 1px 3px rgba(0,0,0,0.15);
    }
    .sp-switch input:checked + .sp-slider {
        background: #00838f;
    }
    .sp-switch input:checked + .sp-slider::before {
        transform: translateX(16px);
    }
    .sp-switch:hover .sp-slider {
        opacity: 0.85;
    }

    /* 无权限开关 */
    .sp-switch.sp-disabled {
        cursor: not-allowed;
        opacity: 0.4;
    }
    .sp-switch.sp-disabled .sp-slider {
        background: #dcdfe6;
    }

    /* 图例中的 demo 开关（缩小） */
    .sp-switch-demo {
        width: 32px;
        height: 18px;
        pointer-events: none;
    }
    .sp-switch-demo .sp-slider { border-radius: 9px; }
    .sp-switch-demo .sp-slider::before {
        width: 14px;
        height: 14px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }
    .sp-switch-demo input:checked + .sp-slider::before {
        transform: translateX(14px);
    }

    /* 图标和标题 */
    .sp-icon { color: #909399; font-size: 14px; width: 18px; text-align: center; }
    .sp-title { color: #303133; font-size: 13px; flex: 1; }
    .sp-url { font-size: 10px; color: #c0c4cc; max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    /* 状态：已隐藏 */
    .sp-node.is-hidden .sp-node-row { background: #fafafa; }
    .sp-node.is-hidden .sp-title { color: #c0c4cc; text-decoration: line-through; }
    .sp-node.is-hidden .sp-icon { color: #dcdfe6; }
    .sp-node.is-hidden .sp-url { color: #dcdfe6; }

    /* 状态：无权限 */
    .sp-node.no-perm .sp-node-row { opacity: 0.45; }
    .sp-node.no-perm .sp-title { color: #c0c4cc; }
    .sp-node.no-perm .sp-icon { color: #dcdfe6; }

    /* 右侧面板 */
    .sidebar-panel {
        border: 1px solid #e4e7ed;
        border-radius: 8px;
        background: #fff;
        overflow: hidden;
    }
    .sidebar-panel-section {
        padding: 16px;
    }
    .sidebar-panel-section + .sidebar-panel-section {
        border-top: 1px solid #ebeef5;
    }
    .sidebar-panel-title {
        margin: 0 0 12px;
        font-size: 13px;
        font-weight: 600;
        color: #606266;
    }

    /* 图例列表 */
    .legend-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .legend-row {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 13px;
        color: #606266;
    }

    /* 统计 */
    .sidebar-stats {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .stat-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 13px;
        color: #606266;
    }
    .stat-row .stat-value {
        font-weight: 600;
        color: #303133;
    }
    .stat-row .stat-value.text-teal { color: #00838f; }
    .stat-row .stat-value.text-gray { color: #909399; }
    .stat-row .stat-value.text-muted { color: #c0c4cc; }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <span class="caption-subject role-breadcrumb">
                        <a href="{{ url('roles') }}">{{ __('roles.title') }}</a>
                        / <span class="role-badge">{{ $role->name }}</span>
                    </span>
                </div>
                <div class="actions">
                    <a href="{{ url('roles') }}" class="btn btn-default btn-sm">
                        {{ __('roles.back_to_list') }}
                    </a>
                </div>
            </div>
            <div class="portlet-body">
                <div class="tabbable-line">
                    <ul class="nav nav-tabs">
                        <li class="active">
                            <a href="#tab_info" data-toggle="tab">{{ __('roles.tab_info') }}</a>
                        </li>
                        <li>
                            <a href="#tab_permissions" data-toggle="tab">{{ __('roles.tab_permissions') }}</a>
                        </li>
                        <li>
                            <a href="#tab_sidebar" data-toggle="tab">{{ __('roles.tab_menu_visibility') }}</a>
                        </li>
                        <li>
                            <a href="#tab_users" data-toggle="tab" id="tab_users_trigger">{{ __('roles.tab_users') }}</a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active" id="tab_info">
                            @include('roles._tab_info')
                        </div>
                        <div class="tab-pane" id="tab_permissions">
                            @include('roles._tab_permissions')
                        </div>
                        <div class="tab-pane" id="tab_sidebar">
                            @include('roles._tab_sidebar')
                        </div>
                        <div class="tab-pane" id="tab_users">
                            @include('roles._tab_users')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script type="text/javascript">
$(function () {
    LanguageManager.loadAllFromPHP({
        'roles': @json(__('roles')),
        'common': @json(__('common'))
    });

    // ================================================================
    //  Tab 2: 权限配置
    // ================================================================
    var $permForm = $('#permissions-form');
    var allExpanded = true;

    // --- 计数刷新 ---
    function updateModuleCounts() {
        $permForm.find('.perm-module-card').each(function () {
            var $card = $(this);
            var total = $card.find('input[type="checkbox"]').length;
            var checked = $card.find('input[type="checkbox"]:checked').length;
            $card.find('.perm-checked-count').text(checked);
            $card.find('.perm-module-badge')
                 .toggleClass('all-checked', checked > 0 && checked === total);
        });
    }
    // 初始计数 + Tab 切换时重新计数（防止隐藏 Tab 时序问题）
    setTimeout(updateModuleCounts, 0);
    $('a[href="#tab_permissions"]').on('shown.bs.tab', updateModuleCounts);

    // --- checkbox 变更时刷新计数 ---
    $permForm.on('change', 'input[type="checkbox"]', function () {
        updateModuleCounts();
    });

    // --- 模板加载 ---
    $('#template-menu').on('click', 'a[data-template]', function (e) {
        e.preventDefault();
        var slug = $(this).data('template');
        if (!confirm(@json(__('roles.template_confirm')))) return;

        $.ajax({
            url: '/roles/templates/' + slug + '/permissions',
            type: 'GET',
            success: function (res) {
                if (res.status && res.data) {
                    // 先取消全选，再勾选模板权限
                    $permForm.find('input[type="checkbox"]').prop('checked', false);
                    res.data.forEach(function (id) {
                        $permForm.find('input[value="' + id + '"]').prop('checked', true);
                    });
                    updateModuleCounts();
                    toastr.info(@json(__('roles.template_loaded')));
                } else {
                    toastr.error(res.message || LanguageManager.trans('common.error'));
                }
            },
            error: function () {
                toastr.error(LanguageManager.trans('common.error'));
            }
        });
    });

    // --- 全选 ---
    $('#btn-select-all-perms').on('click', function () {
        $permForm.find('.perm-item:not(.search-hidden) input[type="checkbox"]').prop('checked', true);
        updateModuleCounts();
    });

    // --- 取消全选 ---
    $('#btn-deselect-all-perms').on('click', function () {
        $permForm.find('.perm-item:not(.search-hidden) input[type="checkbox"]').prop('checked', false);
        updateModuleCounts();
    });

    // --- 全选本模块（仅作用于搜索可见的权限项） ---
    $(document).on('click', '.btn-select-module', function (e) {
        e.stopPropagation();
        var $card = $(this).closest('.perm-module-card');
        var $cbs = $card.find('.perm-item:not(.search-hidden) input[type="checkbox"]');
        var allChecked = $cbs.filter(':not(:checked)').length === 0;
        $cbs.prop('checked', !allChecked);
        updateModuleCounts();
    });

    // --- 模块折叠/展开 ---
    $(document).on('click', '.perm-module-header', function (e) {
        if ($(e.target).closest('.btn-select-module').length) return;
        $(this).closest('.perm-module-card').toggleClass('is-collapsed');
    });

    // --- 全部收起/展开 ---
    $('#btn-toggle-expand').on('click', function () {
        allExpanded = !allExpanded;
        $permForm.find('.perm-module-card').toggleClass('is-collapsed', !allExpanded);
        $(this).find('i').toggleClass('fa-compress fa-expand');
        $(this).find('.toggle-text').text(
            allExpanded ? @json(__('roles.collapse_all')) : @json(__('roles.expand_all'))
        );
    });

    // --- 搜索过滤 ---
    var searchTimer;
    $(document).on('input', '#perm-search-input', function () {
        clearTimeout(searchTimer);
        var keyword = $(this).val().trim().toLowerCase();
        searchTimer = setTimeout(function () {
            var hasVisible = false;
            $('#permissions-form .perm-module-card').each(function () {
                var $card = $(this);
                var moduleName = ($card.attr('data-module') || '').toLowerCase();
                var moduleMatch = keyword && moduleName.indexOf(keyword) !== -1;
                var moduleVisible = false;
                $card.find('.perm-item').each(function () {
                    var $item = $(this);
                    var name = ($item.attr('data-name') || '') + '';
                    var slug = ($item.attr('data-slug') || '') + '';
                    var match = !keyword || moduleMatch || name.indexOf(keyword) !== -1 || slug.indexOf(keyword) !== -1;
                    $item.toggleClass('search-hidden', !match);
                    $item.toggleClass('search-highlight', match && keyword.length > 0);
                    if (match) moduleVisible = true;
                });
                $card.toggleClass('search-hidden', !moduleVisible);
                if (moduleVisible) hasVisible = true;
                if (keyword && moduleVisible) {
                    $card.removeClass('is-collapsed');
                }
            });
            $('#perm-no-results').toggle(!hasVisible);
        }, 200);
    });

    // --- 保存权限 ---
    $('#btn-save-permissions').on('click', function () {
        var ids = [];
        $permForm.find('input[name="permission_ids[]"]:checked').each(function () {
            ids.push(parseInt($(this).val()));
        });
        var $btn = $(this);
        var origHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> ' + @json(__('messages.saving')));

        $.ajax({
            url: '{{ url("roles/" . $role->id . "/sync-permissions") }}',
            type: 'POST',
            data: { permission_ids: ids },
            success: function (res) {
                if (res.status) {
                    toastr.success(res.message);
                } else {
                    toastr.error(res.message);
                }
            },
            error: function () {
                toastr.error(LanguageManager.trans('common.error'));
            },
            complete: function () {
                $btn.prop('disabled', false).html(origHtml);
            }
        });
    });

    // --- tooltip 初始化 ---
    try {
        $permForm.find('[data-toggle="tooltip"]').tooltip();
    } catch (e) {}

    // ================================================================
    //  Tab 3: 菜单可见性（Toggle 开关）
    // ================================================================
    var $spTree = $('#sidebar-preview-tree');

    // --- 统计刷新 ---
    function updateSidebarStats() {
        var visible = 0, hidden = 0, noPerm = 0;
        $spTree.find('.sp-node').each(function () {
            if ($(this).data('has-perm') === 1 || $(this).data('has-perm') === '1') {
                if ($(this).hasClass('is-hidden')) { hidden++; } else { visible++; }
            } else {
                noPerm++;
            }
        });
        $('#sidebar-stats').html(
            '<div class="stat-row"><span>' + @json(__('common.visible')) + '</span><span class="stat-value text-teal">' + visible + '</span></div>' +
            '<div class="stat-row"><span>' + @json(__('common.hidden')) + '</span><span class="stat-value text-gray">' + hidden + '</span></div>' +
            '<div class="stat-row"><span>' + @json(__('common.no_permission')) + '</span><span class="stat-value text-muted">' + noPerm + '</span></div>'
        );
    }
    // 初始统计
    $('a[href="#tab_sidebar"]').on('shown.bs.tab', function () { updateSidebarStats(); });
    setTimeout(function () { if ($('#tab_sidebar').hasClass('active')) updateSidebarStats(); }, 0);

    // --- 切换可见性（checkbox change） ---
    $spTree.on('change', '.sp-switch:not(.sp-disabled) input', function () {
        var $node = $(this).closest('.sp-node');
        if (this.checked) {
            $node.removeClass('is-hidden');
        } else {
            $node.addClass('is-hidden');
        }
        updateSidebarStats();
    });

    // --- 保存覆盖 ---
    $('#btn-save-sidebar').on('click', function () {
        var hiddenIds = [];
        $spTree.find('.sp-node.is-hidden[data-has-perm="1"]').each(function () {
            hiddenIds.push(parseInt($(this).data('id')));
        });

        var $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            url: '{{ url("roles/" . $role->id . "/menu-overrides") }}',
            type: 'POST',
            data: { hidden_ids: hiddenIds },
            success: function (res) {
                if (res.status) {
                    toastr.success(res.message);
                } else {
                    toastr.error(res.message);
                }
            },
            error: function () {
                toastr.error(LanguageManager.trans('common.error'));
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
    });

    // --- 恢复默认 ---
    $('#btn-reset-sidebar').on('click', function () {
        $spTree.find('.sp-node.is-hidden[data-has-perm="1"]').each(function () {
            $(this).removeClass('is-hidden');
            $(this).find('.sp-switch input').prop('checked', true);
        });
        updateSidebarStats();
        toastr.info(@json(__('roles.sidebar_reset')));
    });

    // ================================================================
    //  Tab 4: 用户列表（懒加载 DataTable）
    // ================================================================
    var usersTableInit = false;

    $('#tab_users_trigger').on('shown.bs.tab', function () {
        if (usersTableInit) return;
        usersTableInit = true;

        $('#role_users_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ url("roles/" . $role->id . "/users") }}',
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, width: '50px' },
                { data: 'name', name: 'surname', orderable: false },
                { data: 'email', name: 'email' },
                { data: 'phone_number', name: 'phone_no' },
                { data: 'created_at', name: 'created_at', width: '120px' }
            ],
            language: typeof DataTableLang !== 'undefined' ? DataTableLang : {}
        });
    });
});
</script>
@endsection
