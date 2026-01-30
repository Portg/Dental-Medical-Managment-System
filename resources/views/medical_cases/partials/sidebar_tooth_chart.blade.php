{{-- Tooth Chart Mini --}}
<div class="portlet light bordered sidebar-tool-panel @if($needPatientSelection ?? false) disabled @endif">
    <div class="portlet-title">
        <div class="caption font-dark">
            <span class="caption-subject">{{ __('medical_cases.tooth_chart') }}</span>
        </div>
    </div>
    <div class="portlet-body" style="text-align: center;">
        {{-- Upper teeth row --}}
        <div style="display: flex; justify-content: center; margin-bottom: 4px;">
            @for($i = 18; $i >= 11; $i--)
                <div class="tooth-mini" data-tooth="{{ $i }}" title="{{ $i }}">{{ $i }}</div>
            @endfor
            <div style="width: 8px;"></div>
            @for($i = 21; $i <= 28; $i++)
                <div class="tooth-mini" data-tooth="{{ $i }}" title="{{ $i }}">{{ $i }}</div>
            @endfor
        </div>
        {{-- Separator --}}
        <div style="height: 2px; background: #e8e8e8; margin: 4px 0;"></div>
        {{-- Lower teeth row --}}
        <div style="display: flex; justify-content: center;">
            @for($i = 48; $i >= 41; $i--)
                <div class="tooth-mini" data-tooth="{{ $i }}" title="{{ $i }}">{{ $i }}</div>
            @endfor
            <div style="width: 8px;"></div>
            @for($i = 31; $i <= 38; $i++)
                <div class="tooth-mini" data-tooth="{{ $i }}" title="{{ $i }}">{{ $i }}</div>
            @endfor
        </div>
        <div class="text-muted" style="font-size: 11px; margin-top: 8px;">{{ __('medical_cases.tooth_chart_hint') }}</div>
    </div>
</div>
