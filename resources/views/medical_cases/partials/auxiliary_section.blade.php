{{-- Auxiliary Examination Section --}}
<div class="soap-section">
    <div class="soap-section-header">
        <div class="soap-section-title">
            {{ __('medical_cases.auxiliary_section') }}
        </div>
        <div class="soap-section-hint">{{ __('medical_cases.auxiliary_hint') }}</div>
    </div>
    <div class="soap-section-body">
        {{-- Auxiliary Examination Textarea --}}
        <textarea
            name="auxiliary_examination"
            id="auxiliary_examination"
            class="soap-textarea"
            placeholder="{{ __('medical_cases.auxiliary_placeholder') }}"
        >{{ $case->auxiliary_examination ?? '' }}</textarea>

        {{-- Image Upload Area --}}
        <div class="auxiliary-images" style="margin-top: 12px;">
            <label style="font-size: 13px; color: #666; margin-bottom: 8px; display: block;">
                {{ __('medical_cases.attach_images') }}
            </label>
            <div class="image-upload-area">
                <button type="button" class="btn btn-default btn-sm" onclick="openImageSelector()">
                    {{ __('medical_cases.select_images') }}
                </button>
                <div id="auxiliary-image-preview" style="margin-top: 10px; display: flex; flex-wrap: wrap; gap: 8px;">
                    {{-- Image previews will be added here --}}
                </div>
            </div>
            <input type="hidden" name="auxiliary_images" id="auxiliary_images"
                   value="{{ $case->auxiliary_images ?? '[]' }}">
        </div>
    </div>
</div>
