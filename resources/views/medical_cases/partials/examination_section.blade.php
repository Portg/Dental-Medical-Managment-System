{{-- Examination Section (O - Objective) --}}
@php
    $examinationTeeth = isset($case) && $case->examination_teeth ? $case->examination_teeth : [];
@endphp

<div class="soap-section">
    <div class="soap-section-header">
        <div class="soap-section-title">
            {{ __('medical_cases.examination_section') }}
            <span class="required">*</span>
        </div>
        <div class="soap-section-hint">{{ __('medical_cases.examination_hint') }}</div>
    </div>
    <div class="soap-section-body">
        {{-- Examination Textarea --}}
        <textarea
            name="examination"
            id="examination"
            class="soap-textarea"
            placeholder="{{ __('medical_cases.examination_placeholder') }}"
            required
        >{{ $case->examination ?? '' }}</textarea>

        {{-- Examination Teeth Selection --}}
        <div class="teeth-tags" id="examination-teeth-tags">
            @foreach($examinationTeeth as $tooth)
                <span class="tooth-tag" data-tooth="{{ $tooth }}">
                    {{ $tooth }}
                    <span class="remove-tooth" onclick="removeTooth('examination', '{{ $tooth }}')">&times;</span>
                </span>
            @endforeach
            <button type="button" class="add-teeth-btn" onclick="openToothSelector('examination')">
                {{ __('medical_cases.add_teeth') }}
            </button>
        </div>
        <input type="hidden" name="examination_teeth" id="examination_teeth"
               value="{{ json_encode($examinationTeeth) }}">
    </div>
</div>
