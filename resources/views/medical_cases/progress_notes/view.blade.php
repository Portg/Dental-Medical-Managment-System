<div class="modal fade modal-form modal-form-lg" id="view_progress_note_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">{{ __('medical_cases.view_progress_note') }}</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>{{ __('medical_cases.note_date') }}:</strong> <span id="view_note_date"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>{{ __('medical_cases.note_type') }}:</strong> <span id="view_note_type"></span></p>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <strong>S</strong> - {{ __('medical_cases.subjective') }}
                            </div>
                            <div class="panel-body" id="view_subjective">-</div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <strong>O</strong> - {{ __('medical_cases.objective') }}
                            </div>
                            <div class="panel-body" id="view_objective">-</div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <strong>A</strong> - {{ __('medical_cases.assessment') }}
                            </div>
                            <div class="panel-body" id="view_assessment">-</div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <strong>P</strong> - {{ __('medical_cases.plan') }}
                            </div>
                            <div class="panel-body" id="view_plan">-</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.close') }}</button>
            </div>
        </div>
    </div>
</div>
