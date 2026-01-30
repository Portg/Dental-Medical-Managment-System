{{--
    Patient Source Form Modal
    Uses form-modal.css for styling
--}}
<div class="modal fade modal-form modal-form-sm" id="source-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">{{ __('patient_tags.create_source') }}</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="source-form" class="form-horizontal" autocomplete="off">
                    @csrf
                    <input type="hidden" id="source_id" name="id">

                    {{-- Name --}}
                    <div class="form-row">
                        @include('components.form.text-field', [
                            'name' => 'name',
                            'label' => __('patient_tags.name'),
                            'required' => true,
                        ])
                    </div>

                    {{-- Code --}}
                    <div class="form-row">
                        @include('components.form.text-field', [
                            'name' => 'code',
                            'label' => __('patient_tags.code'),
                            'required' => true,
                            'maxlength' => 20,
                            'placeholder' => __('patient_tags.code_hint'),
                        ])
                    </div>

                    {{-- Description --}}
                    <div class="form-row">
                        @include('components.form.textarea-field', [
                            'name' => 'description',
                            'label' => __('patient_tags.description'),
                            'rows' => 2,
                        ])
                    </div>

                    {{-- Status --}}
                    <div class="form-row">
                        @include('components.form.checkbox-field', [
                            'name' => 'is_active',
                            'label' => __('common.status'),
                            'text' => __('common.active'),
                            'value' => '1',
                            'checked' => true,
                        ])
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" id="btn-save" class="btn btn-primary" onclick="save_source()">
                    {{ __('common.save_record') }}
                </button>
            </div>
        </div>
    </div>
</div>
