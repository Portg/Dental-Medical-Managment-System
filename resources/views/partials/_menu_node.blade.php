@if($item->children->isNotEmpty())
    {{-- 有子节点 → 展开式菜单 --}}
    <li class="nav-item">
        <a href="javascript:;" class="nav-link nav-toggle">
            @if($item->icon)<i class="{{ $item->icon }}"></i>@endif
            <span class="title">{{ __($item->title_key) }}</span>
            <span class="arrow"></span>
        </a>
        <ul class="sub-menu">
            @foreach($item->children as $child)
                @include('partials._menu_node', ['item' => $child, 'level' => $level + 1])
            @endforeach
        </ul>
    </li>
@else
    {{-- 叶子节点 → 直链 --}}
    <li class="nav-item {{ $level === 1 ? 'start' : '' }}">
        <a href="{{ url($item->effective_url ?? $item->url) }}" class="nav-link">
            @if($item->icon)<i class="{{ $item->icon }}"></i>@endif
            <span class="title">{{ __($item->title_key) }}</span>
        </a>
    </li>
@endif
