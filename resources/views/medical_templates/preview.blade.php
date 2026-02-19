<div class="modal fade modal-form modal-form-lg" id="preview-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">{{ __('templates.preview_title') }}</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>{{ __('templates.name') }}:</strong> <span id="preview-name"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>{{ __('templates.code') }}:</strong> <span id="preview-code"></span></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>{{ __('templates.category') }}:</strong> <span id="preview-category"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>{{ __('templates.type') }}:</strong> <span id="preview-type"></span></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <p><strong>{{ __('templates.description') }}:</strong> <span id="preview-description"></span></p>
                    </div>
                </div>
                <hr>

                <!-- SOAP Format Preview -->
                <div id="preview-soap-content" style="display:none;">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong class="text-primary">S</strong> - {{ __('templates.subjective') }}
                        </div>
                        <div class="panel-body" id="preview-subjective">
                            <em class="text-muted">{{ __('templates.no_content') }}</em>
                        </div>
                    </div>
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong class="text-primary">O</strong> - {{ __('templates.objective') }}
                        </div>
                        <div class="panel-body" id="preview-objective">
                            <em class="text-muted">{{ __('templates.no_content') }}</em>
                        </div>
                    </div>
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong class="text-primary">A</strong> - {{ __('templates.assessment') }}
                        </div>
                        <div class="panel-body" id="preview-assessment">
                            <em class="text-muted">{{ __('templates.no_content') }}</em>
                        </div>
                    </div>
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <strong class="text-primary">P</strong> - {{ __('templates.plan') }}
                        </div>
                        <div class="panel-body" id="preview-plan">
                            <em class="text-muted">{{ __('templates.no_content') }}</em>
                        </div>
                    </div>
                </div>

                <!-- Simple Content Preview -->
                <div id="preview-simple-content">
                    <div class="row">
                        <div class="col-md-12">
                            <p><strong>{{ __('templates.content') }}:</strong></p>
                            <div id="preview-content" style="white-space: pre-wrap; background: #f5f5f5; padding: 15px; border-radius: 4px;"></div>
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
