<div class="modal fade modal-form modal-form-lg" id="view_treatment_plan_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">{{ __('medical_cases.view_treatment_plan') }}</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-8">
                        <h4 id="view_plan_name"></h4>
                    </div>
                    <div class="col-md-4 text-right">
                        <span id="view_plan_status"></span>
                        <span id="view_plan_priority"></span>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-12">
                        <p><strong>{{ __('medical_cases.description') }}:</strong></p>
                        <p id="view_plan_description">-</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <p><strong>{{ __('medical_cases.planned_procedures') }}:</strong></p>
                        <p id="view_planned_procedures" style="white-space: pre-wrap;">-</p>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-3">
                        <p><strong>{{ __('medical_cases.estimated_cost') }}:</strong><br><span id="view_estimated_cost">-</span></p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>{{ __('medical_cases.actual_cost') }}:</strong><br><span id="view_actual_cost">-</span></p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>{{ __('medical_cases.start_date') }}:</strong><br><span id="view_start_date">-</span></p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>{{ __('medical_cases.target_completion_date') }}:</strong><br><span id="view_target_date">-</span></p>
                    </div>
                </div>
                <div class="row" id="view_completion_row" style="display: none;">
                    <div class="col-md-6">
                        <p><strong>{{ __('medical_cases.actual_completion_date') }}:</strong><br><span id="view_actual_completion_date">-</span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>{{ __('medical_cases.completion_notes') }}:</strong><br><span id="view_completion_notes">-</span></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.close') }}</button>
            </div>
        </div>
    </div>
</div>
