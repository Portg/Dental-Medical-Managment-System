{{--
    Menu Items Management Page
    ============================
    Left-right split layout: tree + edit form
--}}
@extends(\App\Http\Helper\FunctionsHelper::navigation())

@section('css')
    @include('layouts.page_loader')
    <link rel="stylesheet" href="{{ asset('css/menu-items.css') }}">
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
    LanguageManager.loadFromPHP(@json(__('menu_items')), 'menu_items');
</script>
<script src="{{ asset('include_js/menu_items_index.js') }}?v={{ filemtime(public_path('include_js/menu_items_index.js')) }}"></script>
@endsection
