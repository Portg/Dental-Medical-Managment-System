{{-- Tab 2: 权限配置（增强版） --}}
<form id="permissions-form">
    {{-- 工具栏 --}}
    <div class="perm-toolbar">
        <div class="perm-toolbar-left">
            <div class="perm-search-wrap">
                <input type="text" id="perm-search-input" class="form-control"
                       placeholder="{{ __('roles.search_permissions') }}">
            </div>
            <div class="perm-toolbar-actions">
                {{-- 模板下拉 --}}
                <div class="btn-group">
                    <button type="button" class="btn btn-xs btn-info dropdown-toggle" data-toggle="dropdown">
                        {{ __('roles.load_template') }} <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu" id="template-menu">
                        @foreach($roleTemplates as $tpl)
                            <li>
                                <a href="javascript:;" data-template="{{ $tpl['slug'] }}">
                                    {{ $tpl['label'] }}
                                    <small class="text-muted">({{ $tpl['count'] }})</small>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <button type="button" class="btn btn-xs btn-default" id="btn-select-all-perms">
                    {{ __('roles.select_all') }}
                </button>
                <button type="button" class="btn btn-xs btn-default" id="btn-deselect-all-perms">
                    {{ __('roles.deselect_all') }}
                </button>
                <button type="button" class="btn btn-xs btn-default" id="btn-toggle-expand">
                    <span class="toggle-text">{{ __('roles.collapse_all') }}</span>
                </button>
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-primary" id="btn-save-permissions">
            {{ __('roles.save_permissions') }}
        </button>
    </div>

    {{-- 无搜索结果提示 --}}
    <div id="perm-no-results" class="perm-no-results" style="display:none;">
        <span>{{ __('roles.no_search_results') }}</span>
    </div>

    {{-- 模块卡片列表 --}}
    @forelse($permissionsGrouped as $module => $permissions)
        @php
            $checkedInModule = collect($permissions)->filter(fn($p) => in_array($p->id, $rolePermissionIds))->count();
            $totalInModule = count($permissions);
        @endphp
        <div class="perm-module-card" data-module="{{ $module }}">
            <div class="perm-module-header">
                <div class="perm-module-header-left">
                    <h5 class="perm-module-title">{{ $module ?: __('common.general') }}</h5>
                    <span class="perm-module-badge {{ $checkedInModule > 0 && $checkedInModule === $totalInModule ? 'all-checked' : '' }}">
                        <span class="perm-checked-count">{{ $checkedInModule }}</span> / {{ $totalInModule }}
                    </span>
                </div>
                <button type="button" class="btn btn-xs btn-default btn-select-module">
                    {{ __('roles.select_module') }}
                </button>
            </div>
            <div class="perm-module-body">
                <div class="perm-grid">
                    @foreach($permissions as $permission)
                        <label class="perm-item"
                               data-name="{{ mb_strtolower($permission->name) }}"
                               data-slug="{{ $permission->slug }}">
                            <input type="checkbox" name="permission_ids[]"
                                   value="{{ $permission->id }}"
                                   {{ in_array($permission->id, $rolePermissionIds) ? 'checked' : '' }}>
                            <span class="perm-checkbox-custom"></span>
                            <span class="perm-item-text">
                                <span class="perm-item-name">{{ $permission->name }}</span>
                                @if($permission->description)
                                    <i class="fa fa-info-circle perm-item-info"
                                       title="{{ $permission->description }}"
                                       data-toggle="tooltip" data-placement="top"></i>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    @empty
        <p class="text-muted">{{ __('roles.no_permissions') }}</p>
    @endforelse
</form>

