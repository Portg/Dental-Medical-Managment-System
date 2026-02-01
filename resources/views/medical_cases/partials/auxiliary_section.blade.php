{{-- Auxiliary Examination Section --}}
@php
    $existingImages = isset($case) && $case->related_images ? $case->related_images : [];
    if (is_string($existingImages)) {
        $existingImages = json_decode($existingImages, true) ?: [];
    }
@endphp

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
                    {{-- Render existing images --}}
                </div>
            </div>
            <input type="hidden" name="related_images" id="related_images"
                   value="{{ json_encode($existingImages) }}">
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Render existing image previews on page load
    var existingImages = @json($existingImages);
    if (existingImages && existingImages.length > 0) {
        existingImages.forEach(function(image) {
            if (typeof addImagePreview === 'function') {
                addImagePreview(image);
            }
        });
    }
});
</script>
