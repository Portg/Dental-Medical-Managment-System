{{-- FDI dental chart editor — aligned with medical_cases tooth selector --}}
@php
    $dceDefaultTab = 'permanent';
    if (isset($patient)) {
        $patientAge = null;
        if (!empty($patient->date_of_birth)) {
            $patientAge = \Carbon\Carbon::parse($patient->date_of_birth)->age;
        } elseif (!empty($patient->dob)) {
            $patientAge = \Carbon\Carbon::parse($patient->dob)->age;
        } elseif (isset($patient->age) && $patient->age !== '' && $patient->age !== null) {
            $patientAge = (int) $patient->age;
        }
        if ($patientAge !== null && $patientAge <= 12) {
            $dceDefaultTab = 'deciduous';
        }
    }
@endphp

<div id="dce-editor" class="dce-wrap">
    <div class="dce-hint">
        <i class="fa fa-info-circle"></i>
        {{ __('odontogram.editor_hint') }}
    </div>

    <div class="dce-status-bar" role="toolbar" aria-label="{{ __('odontogram.select_status') }}">
        <button type="button" class="dce-status-btn active" data-status="caries">
            <span class="dce-status-swatch" style="background:#EAB308"></span>
            <span class="dce-status-label">{{ __('odontogram.caries') }}</span>
        </button>
        <button type="button" class="dce-status-btn" data-status="filled">
            <span class="dce-status-swatch" style="background:#EF4444"></span>
            <span class="dce-status-label">{{ __('odontogram.filling') }}</span>
        </button>
        <button type="button" class="dce-status-btn" data-status="rct">
            <span class="dce-status-swatch" style="background:#F97316"></span>
            <span class="dce-status-label">{{ __('odontogram.endodontics') }}</span>
        </button>
        <button type="button" class="dce-status-btn" data-status="crown">
            <span class="dce-status-swatch" style="background:#2563EB"></span>
            <span class="dce-status-label">{{ __('odontogram.crown') }}</span>
        </button>
        <button type="button" class="dce-status-btn" data-status="missing">
            <span class="dce-status-swatch" style="background:#F43F5E"></span>
            <span class="dce-status-label">{{ __('odontogram.absent') }}</span>
        </button>
        <button type="button" class="dce-status-btn" data-status="implant">
            <span class="dce-status-swatch" style="background:#A855F7"></span>
            <span class="dce-status-label">{{ __('odontogram.implant') }}</span>
        </button>
        <button type="button" class="dce-status-btn" data-status="impacted">
            <span class="dce-status-swatch" style="background:#14B8A6"></span>
            <span class="dce-status-label">{{ __('odontogram.impacted_teeth') }}</span>
        </button>
        <button type="button" class="dce-status-btn dce-clear" data-status="clear">
            <span class="dce-status-swatch"></span>
            <span class="dce-status-label">{{ __('odontogram.clear_mark') }}</span>
        </button>
    </div>

    <div class="dce-tabs">
        <button type="button" class="dce-tab {{ $dceDefaultTab === 'permanent' ? 'active' : '' }}" data-target="dce-panel-permanent">
            {{ __('odontogram.permanent') }}
        </button>
        <button type="button" class="dce-tab {{ $dceDefaultTab === 'deciduous' ? 'active' : '' }}" data-target="dce-panel-deciduous">
            {{ __('odontogram.decidua') }}
        </button>
    </div>

    <div class="dce-panel {{ $dceDefaultTab === 'permanent' ? 'active' : '' }}" id="dce-panel-permanent">
        <div class="dce-grid">
            <div class="dce-quadrant-label">{{ __('odontogram.upper_right') }} | {{ __('odontogram.upper_left') }}</div>
            <div class="dce-row">
                @for($i = 18; $i >= 11; $i--)
                    <div class="dce-tooth" data-tooth="{{ $i }}"><span class="dce-num">{{ $i }}</span><span class="dce-mark"></span></div>
                @endfor
                <div class="dce-sep-v"></div>
                @for($i = 21; $i <= 28; $i++)
                    <div class="dce-tooth" data-tooth="{{ $i }}"><span class="dce-num">{{ $i }}</span><span class="dce-mark"></span></div>
                @endfor
            </div>
            <div class="dce-sep-h"></div>
            <div class="dce-row">
                @for($i = 48; $i >= 41; $i--)
                    <div class="dce-tooth" data-tooth="{{ $i }}"><span class="dce-num">{{ $i }}</span><span class="dce-mark"></span></div>
                @endfor
                <div class="dce-sep-v"></div>
                @for($i = 31; $i <= 38; $i++)
                    <div class="dce-tooth" data-tooth="{{ $i }}"><span class="dce-num">{{ $i }}</span><span class="dce-mark"></span></div>
                @endfor
            </div>
            <div class="dce-quadrant-label">{{ __('odontogram.lower_right') }} | {{ __('odontogram.lower_left') }}</div>
        </div>
    </div>

    <div class="dce-panel {{ $dceDefaultTab === 'deciduous' ? 'active' : '' }}" id="dce-panel-deciduous">
        <div class="dce-grid">
            <div class="dce-quadrant-label">{{ __('odontogram.upper_right') }} | {{ __('odontogram.upper_left') }}</div>
            <div class="dce-row">
                @for($i = 55; $i >= 51; $i--)
                    <div class="dce-tooth deciduous" data-tooth="{{ $i }}"><span class="dce-num">{{ $i }}</span><span class="dce-mark"></span></div>
                @endfor
                <div class="dce-sep-v"></div>
                @for($i = 61; $i <= 65; $i++)
                    <div class="dce-tooth deciduous" data-tooth="{{ $i }}"><span class="dce-num">{{ $i }}</span><span class="dce-mark"></span></div>
                @endfor
            </div>
            <div class="dce-sep-h" style="width:60%"></div>
            <div class="dce-row">
                @for($i = 85; $i >= 81; $i--)
                    <div class="dce-tooth deciduous" data-tooth="{{ $i }}"><span class="dce-num">{{ $i }}</span><span class="dce-mark"></span></div>
                @endfor
                <div class="dce-sep-v"></div>
                @for($i = 71; $i <= 75; $i++)
                    <div class="dce-tooth deciduous" data-tooth="{{ $i }}"><span class="dce-num">{{ $i }}</span><span class="dce-mark"></span></div>
                @endfor
            </div>
            <div class="dce-quadrant-label">{{ __('odontogram.lower_right') }} | {{ __('odontogram.lower_left') }}</div>
        </div>
    </div>

    <div class="dce-summary" id="dce-summary"></div>

    <div class="dce-actions">
        <button type="button" class="btn green-meadow" id="dce-save-btn">
            <i class="fa fa-save"></i> {{ __('odontogram.save_changes') }}
        </button>
    </div>
</div>
