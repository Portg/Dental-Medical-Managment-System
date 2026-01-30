<div class="modal fade" id="template-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                <h4 class="modal-title">{{ __('templates.create_template') }}</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none"></div>
                <form action="#" id="template-form" class="form-horizontal" autocomplete="off">
                    @csrf
                    <input type="hidden" id="template_id" name="id">
                    <div class="form-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label col-md-4 text-primary">{{ __('templates.name') }} <span class="text-danger">*</span></label>
                                    <div class="col-md-8">
                                        <input type="text" name="name" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label col-md-4 text-primary">{{ __('templates.code') }} <span class="text-danger">*</span></label>
                                    <div class="col-md-8">
                                        <input type="text" name="code" class="form-control" required placeholder="{{ __('templates.code_hint') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label col-md-4 text-primary">{{ __('templates.category') }} <span class="text-danger">*</span></label>
                                    <div class="col-md-8">
                                        <select name="category" class="form-control" required>
                                            <option value="personal">{{ __('templates.personal') }}</option>
                                            <option value="department">{{ __('templates.department') }}</option>
                                            <option value="system">{{ __('templates.system') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label col-md-4 text-primary">{{ __('templates.type') }} <span class="text-danger">*</span></label>
                                    <div class="col-md-8">
                                        <select name="type" id="template_type" class="form-control" required onchange="toggleContentFields()">
                                            <option value="progress_note">{{ __('templates.progress_note') }}</option>
                                            <option value="diagnosis">{{ __('templates.diagnosis') }}</option>
                                            <option value="treatment_plan">{{ __('templates.treatment_plan') }}</option>
                                            <option value="chief_complaint">{{ __('templates.chief_complaint') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label col-md-4 text-primary">{{ __('templates.department') }}</label>
                                    <div class="col-md-8">
                                        <input type="text" name="department" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label col-md-4 text-primary">{{ __('common.status') }}</label>
                                    <div class="col-md-8">
                                        <label class="checkbox-inline">
                                            <input type="checkbox" name="is_active" value="1" checked> {{ __('common.active') }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label col-md-2 text-primary">{{ __('templates.description') }}</label>
                                    <div class="col-md-10">
                                        <input type="text" name="description" class="form-control" placeholder="{{ __('templates.description_hint') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SOAP Fields for progress_note type -->
                        <div id="soap-fields">
                            <hr>
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle"></i> {{ __('templates.soap_hint') }}
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label col-md-2 text-primary">
                                            <strong>S</strong> - {{ __('templates.subjective') }}
                                        </label>
                                        <div class="col-md-10">
                                            <textarea name="content_subjective" class="form-control" rows="3" placeholder="{{ __('templates.subjective_hint') }}"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label col-md-2 text-primary">
                                            <strong>O</strong> - {{ __('templates.objective') }}
                                        </label>
                                        <div class="col-md-10">
                                            <textarea name="content_objective" class="form-control" rows="3" placeholder="{{ __('templates.objective_hint') }}"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label col-md-2 text-primary">
                                            <strong>A</strong> - {{ __('templates.assessment') }}
                                        </label>
                                        <div class="col-md-10">
                                            <textarea name="content_assessment" class="form-control" rows="3" placeholder="{{ __('templates.assessment_hint') }}"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label col-md-2 text-primary">
                                            <strong>P</strong> - {{ __('templates.plan') }}
                                        </label>
                                        <div class="col-md-10">
                                            <textarea name="content_plan" class="form-control" rows="3" placeholder="{{ __('templates.plan_hint') }}"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Simple content field for other types -->
                        <div id="simple-content-field" style="display:none;">
                            <hr>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label col-md-2 text-primary">{{ __('templates.content') }} <span class="text-danger">*</span></label>
                                        <div class="col-md-10">
                                            <textarea name="content_simple" class="form-control" rows="6" placeholder="{{ __('templates.simple_content_hint') }}"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" id="btn-save" class="btn green" onclick="save_template()">{{ __('common.save_record') }}</button>
                <button type="button" class="btn dark btn-outline" data-dismiss="modal">{{ __('common.close') }}</button>
            </div>
        </div>
    </div>
</div>

<script>
function toggleContentFields() {
    var type = document.getElementById('template_type').value;
    var soapFields = document.getElementById('soap-fields');
    var simpleField = document.getElementById('simple-content-field');

    if (type === 'progress_note') {
        soapFields.style.display = 'block';
        simpleField.style.display = 'none';
    } else {
        soapFields.style.display = 'none';
        simpleField.style.display = 'block';
    }
}

function getTemplateContent() {
    var type = document.getElementById('template_type').value;

    if (type === 'progress_note') {
        return JSON.stringify({
            subjective: $('textarea[name="content_subjective"]').val() || '',
            objective: $('textarea[name="content_objective"]').val() || '',
            assessment: $('textarea[name="content_assessment"]').val() || '',
            plan: $('textarea[name="content_plan"]').val() || ''
        });
    } else {
        return $('textarea[name="content_simple"]').val() || '';
    }
}

function setTemplateContent(content, type) {
    if (type === 'progress_note') {
        try {
            var data = typeof content === 'string' ? JSON.parse(content) : content;
            $('textarea[name="content_subjective"]').val(data.subjective || '');
            $('textarea[name="content_objective"]').val(data.objective || '');
            $('textarea[name="content_assessment"]').val(data.assessment || '');
            $('textarea[name="content_plan"]').val(data.plan || '');
        } catch (e) {
            // If not valid JSON, try to set as simple content
            $('textarea[name="content_simple"]').val(content);
        }
    } else {
        $('textarea[name="content_simple"]').val(content);
    }
}

function clearTemplateForm() {
    $('#template-form')[0].reset();
    $('#template_id').val('');
    $('textarea[name="content_subjective"]').val('');
    $('textarea[name="content_objective"]').val('');
    $('textarea[name="content_assessment"]').val('');
    $('textarea[name="content_plan"]').val('');
    $('textarea[name="content_simple"]').val('');
    toggleContentFields();
}
</script>
