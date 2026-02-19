<div class="modal fade modal-form" id="phrase-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">{{ __('templates.create_phrase') }}</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none"></div>
                <form action="#" id="phrase-form" class="form-horizontal" autocomplete="off">
                    @csrf
                    <input type="hidden" id="phrase_id" name="id">

                    @include('components.form.text-field', [
                        'name' => 'shortcut',
                        'label' => __('templates.shortcut'),
                        'required' => true,
                        'maxlength' => 20,
                        'placeholder' => __('templates.shortcut_hint'),
                    ])

                    @include('components.form.text-field', [
                        'name' => 'phrase',
                        'label' => __('templates.phrase'),
                        'required' => true,
                        'placeholder' => __('templates.phrase_hint'),
                    ])

                    @include('components.form.select-field', [
                        'name' => 'category',
                        'label' => __('templates.category'),
                        'placeholder' => __('common.select'),
                        'options' => [
                            ['value' => 'examination', 'text' => __('templates.examination')],
                            ['value' => 'diagnosis', 'text' => __('templates.diagnosis')],
                            ['value' => 'treatment', 'text' => __('templates.treatment')],
                            ['value' => 'other', 'text' => __('templates.other')],
                        ],
                    ])

                    @include('components.form.select-field', [
                        'name' => 'scope',
                        'label' => __('templates.scope'),
                        'required' => true,
                        'options' => [
                            ['value' => 'system', 'text' => __('templates.system')],
                            ['value' => 'personal', 'text' => __('templates.personal')],
                        ],
                    ])

                    @include('components.form.checkbox-field', [
                        'name' => 'is_active',
                        'label' => __('common.status'),
                        'text' => __('common.active'),
                        'value' => '1',
                        'checked' => true,
                    ])
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" id="btn-save" class="btn btn-primary" onclick="save_phrase()">{{ __('common.save_record') }}</button>
            </div>
        </div>
    </div>
</div>
