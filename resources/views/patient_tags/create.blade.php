{{--
    Patient Tag Form Modal
    Uses form-modal.css for styling
--}}
<div class="modal fade modal-form modal-form-sm" id="tag-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">{{ __('patient_tags.create_tag') }}</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="tag-form" class="form-horizontal" autocomplete="off">
                    @csrf
                    <input type="hidden" id="tag_id" name="id">

                    {{-- Name --}}
                    <div class="form-row">
                        @include('components.form.text-field', [
                            'name' => 'name',
                            'label' => __('patient_tags.name'),
                            'required' => true,
                        ])
                    </div>

                    {{-- Color --}}
                    <div class="form-row">
                        <div class="form-group">
                            <label class="control-label col-md-4">
                                <span class="required-asterisk">*</span>{{ __('patient_tags.color') }}
                            </label>
                            <div class="col-md-8">
                                <input type="color" name="color" class="form-control" value="#999999" required style="height: 40px; padding: 2px;">
                            </div>
                        </div>
                    </div>

                    {{-- Icon --}}
                    <div class="form-row">
                        @include('components.form.text-field', [
                            'name' => 'icon',
                            'label' => __('patient_tags.icon'),
                            'placeholder' => 'fa fa-star',
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

                    {{-- Sort Order --}}
                    <div class="form-row">
                        @include('components.form.text-field', [
                            'name' => 'sort_order',
                            'label' => __('patient_tags.sort_order'),
                            'type' => 'number',
                            'value' => '0',
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
                <button type="button" id="btn-save" class="btn btn-primary" onclick="save_tag()">
                    {{ __('common.save_record') }}
                </button>
            </div>
        </div>
    </div>
</div>
