{{--
    Form Section Component
    ======================

    Usage:
    @include('components.form.section', [
        'id' => 'section-basic',
        'title' => __('patient.basic_info'),
        'icon' => 'fa-user',
        'collapsed' => false,          // Optional, default: false
        'hint' => __('common.optional') // Optional
    ])
        ... section content ...
    @endinclude

    Or with slot:
    @component('components.form.section', ['id' => 'section-basic', 'title' => 'Basic Info', 'icon' => 'fa-user'])
        ... section content ...
    @endcomponent
--}}

@php
    $collapsed = $collapsed ?? false;
    $hint = $hint ?? null;
    $icon = $icon ?? 'fa-folder';
@endphp

<div class="form-section {{ $collapsed ? 'collapsed' : '' }}" id="{{ $id }}">
    <div class="form-section-header" onclick="toggleSection('{{ $id }}')">
        <h5 class="form-section-title">
            <i class="fa {{ $icon }}"></i>
            {{ $title }}
            @if($hint)
                <span class="section-hint">({{ $hint }})</span>
            @endif
        </h5>
        <i class="fa fa-chevron-down form-section-toggle {{ $collapsed ? 'collapsed' : '' }}"></i>
    </div>
    <div class="form-section-body">
        {{ $slot }}
    </div>
</div>
