{{-- FDI dental chart editor — select teeth first, then apply status --}}
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
    {{-- Step 1: pick teeth --}}
    <section class="dce-step dce-step-teeth" aria-labelledby="dce-step-teeth-title">
        <header class="dce-step-head">
            <span class="dce-step-num">1</span>
            <h3 id="dce-step-teeth-title" class="dce-step-title">{{ __('odontogram.step_teeth') }}</h3>
            <span class="dce-tool-chip is-empty" id="dce-selection-chip">
                <span id="dce-selection-label">{{ __('odontogram.no_teeth_selected') }}</span>
            </span>
            <div class="dce-tabs">
                <button type="button" class="dce-tab {{ $dceDefaultTab === 'permanent' ? 'active' : '' }}" data-target="dce-panel-permanent">
                    {{ __('odontogram.permanent') }}
                </button>
                <button type="button" class="dce-tab {{ $dceDefaultTab === 'deciduous' ? 'active' : '' }}" data-target="dce-panel-deciduous">
                    {{ __('odontogram.decidua') }}
                </button>
            </div>
        </header>

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
    </section>

    <div class="dce-flow" aria-hidden="true">
        <i class="fa fa-arrow-down"></i>
    </div>

    {{-- Step 2: apply status to selection --}}
    <section class="dce-step" id="dce-status-step" aria-labelledby="dce-step-status-title">
        <header class="dce-step-head">
            <span class="dce-step-num">2</span>
            <h3 id="dce-step-status-title" class="dce-step-title">{{ __('odontogram.step_status') }}</h3>
        </header>
        <div class="dce-status-bar" role="toolbar" aria-label="{{ __('odontogram.select_status') }}">
            <button type="button" class="dce-status-btn" data-status="caries" data-bg="#EAB308" data-label="{{ __('odontogram.caries') }}">
                <span class="dce-status-swatch" style="background:#EAB308"></span>
                <span class="dce-status-label">{{ __('odontogram.caries') }}</span>
            </button>
            <button type="button" class="dce-status-btn" data-status="filled" data-bg="#EF4444" data-label="{{ __('odontogram.filling') }}">
                <span class="dce-status-swatch" style="background:#EF4444"></span>
                <span class="dce-status-label">{{ __('odontogram.filling') }}</span>
            </button>
            <button type="button" class="dce-status-btn" data-status="rct" data-bg="#F97316" data-label="{{ __('odontogram.endodontics') }}">
                <span class="dce-status-swatch" style="background:#F97316"></span>
                <span class="dce-status-label">{{ __('odontogram.endodontics') }}</span>
            </button>
            <button type="button" class="dce-status-btn" data-status="crown" data-bg="#2563EB" data-label="{{ __('odontogram.crown') }}">
                <span class="dce-status-swatch" style="background:#2563EB"></span>
                <span class="dce-status-label">{{ __('odontogram.crown') }}</span>
            </button>
            <button type="button" class="dce-status-btn" data-status="missing" data-bg="#F43F5E" data-label="{{ __('odontogram.absent') }}">
                <span class="dce-status-swatch" style="background:#F43F5E"></span>
                <span class="dce-status-label">{{ __('odontogram.absent') }}</span>
            </button>
            <button type="button" class="dce-status-btn" data-status="implant" data-bg="#A855F7" data-label="{{ __('odontogram.implant') }}">
                <span class="dce-status-swatch" style="background:#A855F7"></span>
                <span class="dce-status-label">{{ __('odontogram.implant') }}</span>
            </button>
            <button type="button" class="dce-status-btn" data-status="impacted" data-bg="#14B8A6" data-label="{{ __('odontogram.impacted_teeth') }}">
                <span class="dce-status-swatch" style="background:#14B8A6"></span>
                <span class="dce-status-label">{{ __('odontogram.impacted_teeth') }}</span>
            </button>
            <button type="button" class="dce-status-btn dce-clear" data-status="clear" data-bg="transparent" data-label="{{ __('odontogram.clear_mark') }}">
                <span class="dce-status-swatch dce-eraser"><i class="fa fa-eraser"></i></span>
                <span class="dce-status-label">{{ __('odontogram.clear_mark') }}</span>
            </button>
        </div>
    </section>

    <div class="dce-footer">
        <div class="dce-summary" id="dce-summary"></div>
        <button type="button" class="btn green-meadow" id="dce-save-btn">
            <i class="fa fa-save"></i> {{ __('odontogram.save_changes') }}
        </button>
    </div>
</div>
