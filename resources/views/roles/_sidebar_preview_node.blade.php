@php
    $hasPerm = $item['has_permission'];
    $isHidden = $item['is_hidden'];
    $hasChildren = !empty($item['children']);
    $nodeClass = !$hasPerm ? 'no-perm' : ($isHidden ? 'is-hidden' : '');
@endphp
<li class="sp-node {{ $nodeClass }}" data-id="{{ $item['id'] }}" data-has-perm="{{ $hasPerm ? '1' : '0' }}">
    <div class="sp-node-row sp-level-{{ $level }}">
        {{-- Toggle 开关 --}}
        @if($hasPerm)
            <label class="sp-switch" title="{{ $isHidden ? __('common.hidden') : __('common.visible') }}">
                <input type="checkbox" {{ $isHidden ? '' : 'checked' }}>
                <span class="sp-slider"></span>
            </label>
        @else
            <label class="sp-switch sp-disabled" title="{{ __('common.no_permission') }}">
                <input type="checkbox" disabled>
                <span class="sp-slider"></span>
            </label>
        @endif

        {{-- 图标 + 标题 --}}
        @if($item['icon'])
            <i class="{{ $item['icon'] }} sp-icon"></i>
        @endif
        <span class="sp-title">{{ $item['title'] }}</span>

        {{-- URL --}}
        @if($item['url'])
            <span class="sp-url">/{{ $item['url'] }}</span>
        @endif
    </div>

    @if($hasChildren)
        <ul class="sp-children">
            @foreach($item['children'] as $child)
                @include('roles._sidebar_preview_node', ['item' => $child, 'level' => $level + 1])
            @endforeach
        </ul>
    @endif
</li>
