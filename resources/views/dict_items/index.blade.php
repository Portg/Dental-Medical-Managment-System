@extends(\App\Http\Helper\FunctionsHelper::navigation())

@section('css')
    <link rel="stylesheet" href="{{ asset('css/dict-items.css') }}">
@endsection

@section('content')
    <div class="dict-layout">
        {{-- ── Left: Type Navigation ── --}}
        <div class="dict-type-nav">
            <div class="nav-search">
                <input type="text" id="type-search" placeholder="{{ __('common.search') }}...">
            </div>
            <div class="nav-list">
                @foreach($grouped as $type => $items)
                    <a href="javascript:;" class="nav-item {{ $loop->first ? 'active' : '' }}" data-type="{{ $type }}">
                        <span class="item-label">
                            <i class="fa fa-list-ul item-icon"></i>
                            {{ __('dict_items.type_' . $type, [], null) !== 'dict_items.type_' . $type ? __('dict_items.type_' . $type) : $type }}
                        </span>
                        <span class="item-count">{{ $items->count() }}</span>
                    </a>
                @endforeach
            </div>
            <div class="nav-footer">
                <button type="button" class="btn btn-default btn-xs btn-block" id="btn-add-type">
                    <i class="fa fa-plus"></i> {{ __('dict_items.add_type') }}
                </button>
            </div>
        </div>

        {{-- ── Right: Items Content ── --}}
        <div class="dict-content">
            @forelse($grouped as $type => $items)
                <div class="dict-type-panel {{ $loop->first ? 'active' : '' }}" data-type="{{ $type }}">
                    <div class="dict-content-header">
                        <div class="header-title">
                            {{ __('dict_items.type_' . $type, [], null) !== 'dict_items.type_' . $type ? __('dict_items.type_' . $type) : $type }}
                            <span class="type-code">{{ $type }}</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary btn-add-item" data-type="{{ $type }}">
                            <i class="fa fa-plus"></i> {{ __('dict_items.add_item') }}
                        </button>
                    </div>
                    <div class="dict-content-body">
                        <table class="table table-bordered dict-item-table">
                            <thead>
                                <tr>
                                    <th width="50">{{ __('common.id') }}</th>
                                    <th width="150">{{ __('dict_items.code') }}</th>
                                    <th width="200">{{ __('dict_items.name') }}</th>
                                    <th width="80">{{ __('dict_items.sort_order') }}</th>
                                    <th width="100">{{ __('dict_items.is_active') }}</th>
                                    <th width="140">{{ __('common.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $item)
                                    <tr data-id="{{ $item->id }}">
                                        <td>{{ $item->id }}</td>
                                        <td><span class="display-text">{{ $item->code }}</span></td>
                                        <td>
                                            <span class="display-text">{{ $item->name }}</span>
                                            <input type="text" class="form-control edit-field edit-name" value="{{ $item->name }}" style="display:none;">
                                        </td>
                                        <td>
                                            <span class="display-text">{{ $item->sort_order }}</span>
                                            <input type="number" class="form-control edit-field edit-sort" value="{{ $item->sort_order }}" style="display:none;">
                                        </td>
                                        <td>
                                            @if($item->is_active)
                                                <span class="label label-success display-text">{{ __('dict_items.active') }}</span>
                                            @else
                                                <span class="label label-default display-text">{{ __('dict_items.inactive') }}</span>
                                            @endif
                                            <select class="form-control edit-field edit-active" style="display:none;">
                                                <option value="1" {{ $item->is_active ? 'selected' : '' }}>{{ __('dict_items.active') }}</option>
                                                <option value="0" {{ !$item->is_active ? 'selected' : '' }}>{{ __('dict_items.inactive') }}</option>
                                            </select>
                                        </td>
                                        <td>
                                            <span class="action-display">
                                                <button type="button" class="btn-action-edit btn-edit"><i class="fa fa-pencil"></i> {{ __('common.edit') }}</button>
                                                <button type="button" class="btn-action-delete btn-delete"><i class="fa fa-trash"></i> {{ __('common.delete') }}</button>
                                            </span>
                                            <span class="action-edit" style="display:none;">
                                                <button type="button" class="btn-action-save btn-save"><i class="fa fa-check"></i> {{ __('common.save') }}</button>
                                                <button type="button" class="btn-action-cancel btn-cancel"><i class="fa fa-times"></i> {{ __('common.cancel') }}</button>
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="dict-empty-hint">
                    <i class="fa fa-book"></i>
                    <p>{{ __('dict_items.no_items') }}</p>
                </div>
            @endforelse
            <div class="dict-no-match" style="display:none;">
                <div class="dict-empty-hint">
                    <i class="fa fa-search"></i>
                    <p>{{ __('dict_items.no_match') }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>LanguageManager.loadFromPHP(@json(__('dict_items')), 'dict_items');</script>
    <script src="{{ asset('include_js/dict_items.js') }}?v={{ filemtime(public_path('include_js/dict_items.js')) }}"></script>
@endsection
