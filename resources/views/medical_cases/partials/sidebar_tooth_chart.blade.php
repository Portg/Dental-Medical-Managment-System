{{-- Tooth Chart Mini --}}
@php
    // Auto-detect default tab by patient age: ≤12 → deciduous, else permanent
    $defaultTab = 'permanent';
    if (isset($case) && $case->patient) {
        $p = $case->patient;
        $patientAge = null;
        if ($p->date_of_birth) {
            $patientAge = \Carbon\Carbon::parse($p->date_of_birth)->age;
        } elseif ($p->dob) {
            $patientAge = \Carbon\Carbon::parse($p->dob)->age;
        } elseif ($p->age !== null && $p->age !== '') {
            $patientAge = (int) $p->age;
        }
        if ($patientAge !== null && $patientAge <= 12) {
            $defaultTab = 'deciduous';
        }
    }
@endphp
<div class="portlet light bordered sidebar-tool-panel @if($needPatientSelection ?? false) disabled @endif">
    <div class="portlet-title">
        <div class="caption font-dark">
            <span class="caption-subject">{{ __('medical_cases.tooth_chart') }}</span>
        </div>
    </div>
    <div class="portlet-body" style="text-align: center;">
        {{-- Tab Switcher --}}
        <div class="tooth-chart-tabs">
            <button type="button" class="tooth-chart-tab {{ $defaultTab === 'permanent' ? 'active' : '' }}" data-target="permanent">
                {{ __('odontogram.permanent') }}
            </button>
            <button type="button" class="tooth-chart-tab {{ $defaultTab === 'deciduous' ? 'active' : '' }}" data-target="deciduous">
                {{ __('odontogram.decidua') }}
            </button>
        </div>

        {{-- Permanent Teeth Panel --}}
        <div class="tooth-chart-panel" id="tooth-panel-permanent" @if($defaultTab === 'deciduous') style="display: none;" @endif>
            {{-- Quadrant labels - upper --}}
            <div class="quadrant-labels">
                <span>{{ __('odontogram.upper_right_abbr') }}</span>
                <span>{{ __('odontogram.upper_left_abbr') }}</span>
            </div>
            {{-- Upper teeth row --}}
            <div style="display: flex; justify-content: center; margin-bottom: 4px;">
                @for($i = 18; $i >= 11; $i--)
                    <div class="tooth-mini" data-tooth="{{ $i }}" title="{{ $i }}">{{ $i }}</div>
                @endfor
                <div style="flex: 0 0 4px;"></div>
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
                <div style="flex: 0 0 4px;"></div>
                @for($i = 31; $i <= 38; $i++)
                    <div class="tooth-mini" data-tooth="{{ $i }}" title="{{ $i }}">{{ $i }}</div>
                @endfor
            </div>
            {{-- Quadrant labels - lower --}}
            <div class="quadrant-labels">
                <span>{{ __('odontogram.lower_right_abbr') }}</span>
                <span>{{ __('odontogram.lower_left_abbr') }}</span>
            </div>
        </div>

        {{-- Deciduous Teeth Panel --}}
        <div class="tooth-chart-panel" id="tooth-panel-deciduous" @if($defaultTab === 'permanent') style="display: none;" @endif>
            {{-- Quadrant labels - upper --}}
            <div class="quadrant-labels">
                <span>{{ __('odontogram.upper_right_abbr') }}</span>
                <span>{{ __('odontogram.upper_left_abbr') }}</span>
            </div>
            {{-- Upper deciduous row --}}
            <div style="display: flex; justify-content: center; margin-bottom: 4px;">
                @for($i = 55; $i >= 51; $i--)
                    <div class="tooth-mini deciduous" data-tooth="{{ $i }}" title="{{ $i }}">{{ $i }}</div>
                @endfor
                <div style="flex: 0 0 4px;"></div>
                @for($i = 61; $i <= 65; $i++)
                    <div class="tooth-mini deciduous" data-tooth="{{ $i }}" title="{{ $i }}">{{ $i }}</div>
                @endfor
            </div>
            {{-- Separator --}}
            <div style="height: 2px; background: #e8e8e8; margin: 4px 0;"></div>
            {{-- Lower deciduous row --}}
            <div style="display: flex; justify-content: center;">
                @for($i = 85; $i >= 81; $i--)
                    <div class="tooth-mini deciduous" data-tooth="{{ $i }}" title="{{ $i }}">{{ $i }}</div>
                @endfor
                <div style="flex: 0 0 4px;"></div>
                @for($i = 71; $i <= 75; $i++)
                    <div class="tooth-mini deciduous" data-tooth="{{ $i }}" title="{{ $i }}">{{ $i }}</div>
                @endfor
            </div>
            {{-- Quadrant labels - lower --}}
            <div class="quadrant-labels">
                <span>{{ __('odontogram.lower_right_abbr') }}</span>
                <span>{{ __('odontogram.lower_left_abbr') }}</span>
            </div>
        </div>

        <div class="text-muted" style="font-size: 11px; margin-top: 8px;">{{ __('medical_cases.tooth_chart_hint') }}</div>
    </div>
</div>
