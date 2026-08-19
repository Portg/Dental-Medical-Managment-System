{{-- 辅助检查：分行录入「牙位 + 文字」，另附影像资料。

     文字部分改成分行明细（见 case_items_section）；影像资料原样保留 ——
     它是独立的一组数据（related_images），与分行无关。 --}}
@php
    $existingImages = isset($case) && $case->related_images ? $case->related_images : [];
    if (is_string($existingImages)) {
        $existingImages = json_decode($existingImages, true) ?: [];
    }
@endphp

@include('medical_cases.partials.case_items_section', [
    'section'     => 'auxiliary_examination',
    'title'       => __('medical_cases.auxiliary_section'),
    'hint'        => __('medical_cases.auxiliary_hint'),
    'rows'        => ($caseItems['auxiliary_examination'] ?? []),
    'required'    => false,
])

<div class="soap-section">
    <div class="soap-section-body">
        {{-- Image Upload Area --}}
        <div class="auxiliary-images">
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
