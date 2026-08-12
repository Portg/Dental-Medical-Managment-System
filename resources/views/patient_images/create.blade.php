{{--
    添加/编辑影像弹窗。

    结构刻意保持「一块上传 + 一组字段」两段：
      · 上传区未选文件时是拖拽框，选完就地换成缩略图 + 文件名 + 重新选择，
        不再并排放一张常驻的空预览卡；
      · 字段合成一个两列网格，不再拆成「基础信息 / 辅助信息」两张带渐变标题栏的
        section —— 那两张卡的说明文字重复，且把必填的「拍摄日期」推到了折叠线以下。

    必填星号与 PatientImageController::store() 的校验规则一一对应：
    title / patient_id / image_date / image_type / image_file。改校验记得改这里。

    样式在 public/css/patient-images.css，Blade 里不要再内联 <style>。
--}}
<div class="modal fade modal-form modal-form-lg" id="imageModal" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('common.close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="imageModalLabel">{{ __('patient_images.add_image') }}</h4>
            </div>
            <div class="modal-body">
                <form id="imageForm" class="patient-image-form" enctype="multipart/form-data">
                    @csrf
                    <div class="alert alert-danger" style="display:none">
                        <ul></ul>
                    </div>
                    <input type="hidden" name="image_id" id="image_id">

                    {{-- 上传区：拖拽框与已选文件块互斥显示 --}}
                    <div class="patient-image-upload">
                        <div class="patient-image-picked" id="selected_file_meta">
                            <div class="patient-image-picked__thumb" id="image_preview_frame">
                                <i class="fa fa-picture-o" id="image_preview_placeholder"></i>
                                <img id="preview_image" src="" alt="" style="display:none;">
                            </div>
                            <div class="patient-image-picked__meta">
                                <div class="patient-image-picked__name" id="selected_file_name">{{ __('patient_images.selected_file') }}</div>
                                <div class="patient-image-picked__hint">{{ __('patient_images.file_hint') }}</div>
                            </div>
                            <button type="button" class="btn btn-default btn-sm" id="btn_change_file">
                                {{ __('patient_images.change_file') }}
                            </button>
                        </div>

                        <label class="patient-image-dropzone" id="image_dropzone">
                            <input type="file" name="image_file" id="image_file" accept="image/*">
                            <div class="patient-image-dropzone__content">
                                <div class="patient-image-dropzone__icon">
                                    <i class="fa fa-cloud-upload"></i>
                                </div>
                                <div class="patient-image-dropzone__title">
                                    {{ __('patient_images.dropzone_title') }} <span class="text-danger">*</span>
                                </div>
                                <div class="patient-image-dropzone__hint">{{ __('patient_images.file_hint') }}</div>
                            </div>
                        </label>
                    </div>

                    <div class="patient-image-grid">
                        <div class="patient-image-field">
                            <label for="patient_id">
                                {{ __('patient_images.patient') }} <span class="text-danger">*</span>
                            </label>
                            <select name="patient_id" id="patient_id" class="form-control select2">
                                <option value=""></option>
                                @foreach($patients as $patient)
                                    <option value="{{ $patient->id }}">{{ $patient->full_name }} ({{ $patient->patient_no }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="patient-image-field">
                            <label for="image_type">
                                {{ __('patient_images.image_type') }} <span class="text-danger">*</span>
                            </label>
                            <select name="image_type" id="image_type" class="form-control">
                                <option value="">{{ __('common.please_select') }}</option>
                                <option value="X-Ray">{{ __('patient_images.type_x_ray') }}</option>
                                <option value="CT">{{ __('patient_images.type_ct') }}</option>
                                <option value="Intraoral">{{ __('patient_images.type_intraoral') }}</option>
                                <option value="Extraoral">{{ __('patient_images.type_extraoral') }}</option>
                                <option value="Other">{{ __('patient_images.type_other') }}</option>
                            </select>
                        </div>

                        <div class="patient-image-field">
                            <label for="image_date">
                                {{ __('patient_images.image_date') }} <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="image_date" id="image_date" class="form-control js-date" autocomplete="off">
                        </div>

                        <div class="patient-image-field">
                            <label for="tooth_number">
                                {{ __('patient_images.tooth_number') }}
                                <span class="patient-image-field-optional">{{ __('common.optional') }}</span>
                            </label>
                            <input type="text" name="tooth_number" id="tooth_number" class="form-control"
                                   placeholder="{{ __('patient_images.tooth_number_placeholder') }}">
                        </div>

                        <div class="patient-image-field patient-image-field--full">
                            <label for="title">
                                {{ __('patient_images.title') }} <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="title" id="title" class="form-control"
                                   placeholder="{{ __('patient_images.title_placeholder') }}">
                        </div>

                        <div class="patient-image-field patient-image-field--full">
                            <label for="description">
                                {{ __('patient_images.description') }}
                                <span class="patient-image-field-optional">{{ __('common.optional') }}</span>
                            </label>
                            <textarea name="description" id="description" class="form-control" rows="3"
                                      placeholder="{{ __('patient_images.description_placeholder') }}"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn btn-primary" onclick="saveImage()">{{ __('common.save') }}</button>
            </div>
        </div>
    </div>
</div>
