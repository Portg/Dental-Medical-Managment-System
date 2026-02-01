{{-- Quick Phrases --}}
<div class="portlet light bordered sidebar-tool-panel @if($needPatientSelection ?? false) disabled @endif">
    <div class="portlet-title">
        <div class="caption font-dark">
            <span class="caption-subject">{{ __('medical_cases.quick_phrases') }}</span>
        </div>
    </div>
    <div class="portlet-body">
        <div style="background: #f8f9fa; border-radius: 4px; padding: 8px 10px; margin-bottom: 10px; font-size: 12px; color: #666; line-height: 1.8;">
            <div>💡 {{ __('medical_cases.hint_template_picker', ['key' => '/']) }}</div>
            <div>💡 {{ __('medical_cases.hint_phrase_picker', ['key' => ';']) }}</div>
        </div>
        <div class="quick-phrases-grid" style="display: flex; flex-wrap: wrap; gap: 6px;">
            {{-- Common examination phrases --}}
            <span class="quick-phrase btn btn-xs btn-default" data-phrase="{{ __('medical_cases.phrase_probe_normal') }}">
                {{ __('medical_cases.phrase_probe_normal_short') }}
            </span>
            <span class="quick-phrase btn btn-xs btn-default" data-phrase="{{ __('medical_cases.phrase_gum_bleeding') }}">
                {{ __('medical_cases.phrase_gum_bleeding_short') }}
            </span>
            <span class="quick-phrase btn btn-xs btn-default" data-phrase="{{ __('medical_cases.phrase_calculus') }}">
                {{ __('medical_cases.phrase_calculus_short') }}
            </span>
            <span class="quick-phrase btn btn-xs btn-default" data-phrase="{{ __('medical_cases.phrase_cavity') }}">
                {{ __('medical_cases.phrase_cavity_short') }}
            </span>
            <span class="quick-phrase btn btn-xs btn-default" data-phrase="{{ __('medical_cases.phrase_sensitivity') }}">
                {{ __('medical_cases.phrase_sensitivity_short') }}
            </span>
            <span class="quick-phrase btn btn-xs btn-default" data-phrase="{{ __('medical_cases.phrase_mobility') }}">
                {{ __('medical_cases.phrase_mobility_short') }}
            </span>
            <span class="quick-phrase btn btn-xs btn-default" data-phrase="{{ __('medical_cases.phrase_percussion_pain') }}">
                {{ __('medical_cases.phrase_percussion_short') }}
            </span>
            <span class="quick-phrase btn btn-xs btn-default" data-phrase="{{ __('medical_cases.phrase_xray_normal') }}">
                {{ __('medical_cases.phrase_xray_normal_short') }}
            </span>
        </div>
    </div>
</div>
