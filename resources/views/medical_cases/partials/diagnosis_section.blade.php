{{-- Diagnosis Section (A - Assessment) --}}
@php
    $relatedTeeth = isset($case) && $case->related_teeth ? $case->related_teeth : [];
@endphp

<div class="soap-section">
    <div class="soap-section-header">
        <div class="soap-section-title">
            {{ __('medical_cases.diagnosis_section') }}
            <span class="required">*</span>
        </div>
        <div class="soap-section-hint">{{ __('medical_cases.diagnosis_hint') }}</div>
    </div>
    <div class="soap-section-body">
        {{-- Diagnosis Textarea --}}
        <textarea
            name="diagnosis"
            id="diagnosis"
            class="soap-textarea"
            placeholder="{{ __('medical_cases.diagnosis_placeholder') }}"
            required
        >{{ $case->diagnosis ?? '' }}</textarea>

        {{-- Related Teeth Selection --}}
        <div style="margin-top: 12px;">
            <label style="font-size: 13px; color: #666; margin-bottom: 6px; display: block;">
                {{ __('medical_cases.related_teeth') }}
            </label>
            <div class="teeth-tags" id="related-teeth-tags">
                @foreach($relatedTeeth as $tooth)
                    <span class="tooth-tag" data-tooth="{{ $tooth }}">
                        {{ $tooth }}
                        <span class="remove-tooth" onclick="removeTooth('related', '{{ $tooth }}')">&times;</span>
                    </span>
                @endforeach
                <button type="button" class="add-teeth-btn" onclick="openToothSelector('related')">
                    {{ __('medical_cases.add_teeth') }}
                </button>
            </div>
            <input type="hidden" name="related_teeth" id="related_teeth"
                   value="{{ json_encode($relatedTeeth) }}">
        </div>
    </div>
</div>
