<div class="modal fade modal-form modal-form-lg" id="progress_note_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="progress_note_modal_title">{{ __('medical_cases.add_progress_note') }}</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="progress_note_form" class="form-horizontal" autocomplete="off">
                    @csrf
                    <input type="hidden" name="progress_note_id" id="progress_note_id">
                    <input type="hidden" name="medical_case_id" id="note_medical_case_id">
                    <input type="hidden" name="patient_id" id="note_patient_id">
                    <div class="form-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label col-md-4 text-primary">{{ __('medical_cases.note_date') }} <span class="text-danger">*</span></label>
                                    <div class="col-md-8">
                                        <input type="datetime-local" name="note_date" id="note_date" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label col-md-4 text-primary">{{ __('medical_cases.note_type') }}</label>
                                    <div class="col-md-8">
                                        <select name="note_type" id="note_type" class="form-control">
                                            <option value="SOAP">{{ __('medical_cases.note_type_soap') }}</option>
                                            <option value="General">{{ __('medical_cases.note_type_general') }}</option>
                                            <option value="Follow-up">{{ __('medical_cases.note_type_follow_up') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label col-md-2 text-primary">{{ __('templates.use_template') }}</label>
                                    <div class="col-md-10">
                                        <select id="progress_note_template_select" class="form-control" style="width: 100%;">
                                            <option value="">{{ __('templates.select_template') }}</option>
                                        </select>
                                        <small class="text-muted"><i class="fa fa-lightbulb-o"></i> {{ __('templates.type_slash_to_search') }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <strong>{{ __('medical_cases.soap_explanation') }}</strong><br>
                            <small>{{ __('medical_cases.soap_description') }}</small>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label col-md-2 text-primary">
                                        <strong>S</strong> - {{ __('medical_cases.subjective') }}
                                        <i class="fa fa-info-circle" data-toggle="tooltip" title="{{ __('medical_cases.subjective_help') }}"></i>
                                    </label>
                                    <div class="col-md-10">
                                        <textarea name="subjective" id="subjective" class="form-control template-enabled phrase-enabled" data-template-type="progress_note" rows="3" placeholder="{{ __('medical_cases.subjective_placeholder') }}"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label col-md-2 text-primary">
                                        <strong>O</strong> - {{ __('medical_cases.objective') }}
                                        <i class="fa fa-info-circle" data-toggle="tooltip" title="{{ __('medical_cases.objective_help') }}"></i>
                                    </label>
                                    <div class="col-md-10">
                                        <textarea name="objective" id="objective" class="form-control template-enabled phrase-enabled" data-template-type="progress_note" rows="3" placeholder="{{ __('medical_cases.objective_placeholder') }}"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label col-md-2 text-primary">
                                        <strong>A</strong> - {{ __('medical_cases.assessment') }}
                                        <i class="fa fa-info-circle" data-toggle="tooltip" title="{{ __('medical_cases.assessment_help') }}"></i>
                                    </label>
                                    <div class="col-md-10">
                                        <textarea name="assessment" id="assessment" class="form-control template-enabled phrase-enabled" data-template-type="progress_note" rows="3" placeholder="{{ __('medical_cases.assessment_placeholder') }}"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label col-md-2 text-primary">
                                        <strong>P</strong> - {{ __('medical_cases.plan') }}
                                        <i class="fa fa-info-circle" data-toggle="tooltip" title="{{ __('medical_cases.plan_help') }}"></i>
                                    </label>
                                    <div class="col-md-10">
                                        <textarea name="plan" id="plan" class="form-control template-enabled phrase-enabled" data-template-type="progress_note" rows="3" placeholder="{{ __('medical_cases.plan_placeholder') }}"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn green" id="btn_save_progress_note" onclick="saveProgressNote()">{{ __('common.save_record') }}</button>
            </div>
        </div>
    </div>
</div>
