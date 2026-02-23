{{-- Tab 3: 菜单可见性 --}}
<div class="sidebar-preview-toolbar">
    <p class="sidebar-hint">
        {{ __('roles.sidebar_hint') }}
    </p>
    <div class="sidebar-toolbar-actions">
        <button type="button" class="btn btn-sm btn-default" id="btn-reset-sidebar">
            {{ __('roles.reset_sidebar') }}
        </button>
        <button type="button" class="btn btn-sm btn-primary" id="btn-save-sidebar">
            {{ __('roles.save_sidebar') }}
        </button>
    </div>
</div>

<div class="row">
    {{-- 左栏：菜单树 --}}
    <div class="col-md-8">
        <div class="sidebar-mock">
            <div class="sidebar-mock-header">
                <span>{{ $role->name }}</span>
            </div>
            <ul class="sidebar-mock-menu" id="sidebar-preview-tree">
                @foreach($sidebarPreview as $item)
                    @include('roles._sidebar_preview_node', ['item' => $item, 'level' => 1])
                @endforeach
            </ul>
        </div>
    </div>

    {{-- 右栏：图例 + 统计 --}}
    <div class="col-md-4">
        <div class="sidebar-panel">
            <div class="sidebar-panel-section">
                <h5 class="sidebar-panel-title">{{ __('common.legend') }}</h5>
                <div class="legend-list">
                    <div class="legend-row">
                        <label class="sp-switch sp-switch-demo">
                            <input type="checkbox" checked disabled>
                            <span class="sp-slider"></span>
                        </label>
                        <span>{{ __('common.visible') }}</span>
                    </div>
                    <div class="legend-row">
                        <label class="sp-switch sp-switch-demo">
                            <input type="checkbox" disabled>
                            <span class="sp-slider"></span>
                        </label>
                        <span>{{ __('common.hidden') }}</span>
                    </div>
                    <div class="legend-row">
                        <label class="sp-switch sp-switch-demo sp-disabled">
                            <input type="checkbox" disabled>
                            <span class="sp-slider"></span>
                        </label>
                        <span>{{ __('common.no_permission') }}</span>
                    </div>
                </div>
            </div>
            <div class="sidebar-panel-section">
                <h5 class="sidebar-panel-title">{{ __('common.statistics') }}</h5>
                <div class="sidebar-stats" id="sidebar-stats">
                    {{-- JS 动态填充 --}}
                </div>
            </div>
        </div>
    </div>
</div>
