{{-- Treatment Section (P - Plan) --}}
@php
    $treatmentServices = isset($case) && $case->treatment_services ? json_decode($case->treatment_services, true) : [];
@endphp

<div class="soap-section">
    <div class="soap-section-header">
        <div class="soap-section-title">
            {{ __('medical_cases.treatment_section') }}
            <span class="required">*</span>
        </div>
        <div class="soap-section-hint">{{ __('medical_cases.treatment_hint') }}</div>
    </div>
    <div class="soap-section-body">
        {{-- Treatment Textarea --}}
        <textarea
            name="treatment"
            id="treatment"
            class="soap-textarea"
            placeholder="{{ __('medical_cases.treatment_placeholder') }}"
            required
        >{{ $case->treatment ?? '' }}</textarea>

        {{-- Treatment Services Selection --}}
        <div style="margin-top: 12px;">
            <label style="font-size: 13px; color: #666; margin-bottom: 6px; display: block;">
                {{ __('medical_cases.treatment_services') }}
            </label>
            <div class="service-tags" id="treatment-service-tags">
                @foreach($treatmentServices as $service)
                    <span class="service-tag" data-id="{{ $service['id'] }}">
                        {{ $service['name'] ?? $service['id'] }}
                        <span class="remove-service" onclick="removeService('{{ $service['id'] }}')">&times;</span>
                    </span>
                @endforeach
                <button type="button" class="add-teeth-btn" onclick="openServiceSelector()">
                    {{ __('medical_cases.add_service') }}
                </button>
            </div>
            <input type="hidden" name="treatment_services" id="treatment_services"
                   value="{{ json_encode($treatmentServices) }}">
        </div>
    </div>
</div>
