<div class="modal fade modal-form modal-form-lg" id="treatment_plan_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <style>
                #treatment_plan_modal .modal-content {
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 18px 40px rgba(22, 41, 56, 0.12);
                }

                #treatment_plan_modal .modal-header {
                    padding: 18px 24px;
                    border-bottom: 1px solid #e5eaef;
                    background: #fff;
                }

                #treatment_plan_modal .modal-title {
                    font-size: 18px;
                    font-weight: 600;
                    color: #183247;
                }

                #treatment_plan_modal .modal-body {
                    padding: 24px;
                    background: #fff;
                }

                #treatment_plan_modal .alert-danger {
                    margin-bottom: 18px;
                    border-radius: 6px;
                }

                #treatment_plan_modal .alert-danger ul {
                    margin: 0;
                    padding-left: 18px;
                }

                #treatment_plan_modal .plan-form-shell {
                    display: flex;
                    flex-direction: column;
                    gap: 20px;
                }

                #treatment_plan_modal .plan-form-card {
                    padding: 0;
                }

                #treatment_plan_modal .plan-form-card.is-primary {
                    padding: 0;
                }

                #treatment_plan_modal .plan-card-header {
                    margin-bottom: 12px;
                }

                #treatment_plan_modal .plan-card-title {
                    margin: 0;
                    font-size: 15px;
                    font-weight: 600;
                    color: #183247;
                }

                #treatment_plan_modal .plan-grid {
                    display: grid;
                    grid-template-columns: repeat(12, minmax(0, 1fr));
                    gap: 16px;
                }

                #treatment_plan_modal .plan-form-card + .plan-form-card {
                    border-top: 1px solid #e8edf2;
                    padding-top: 20px;
                }

                #treatment_plan_modal .plan-field {
                    grid-column: span 12;
                }

                #treatment_plan_modal .plan-field.span-3 {
                    grid-column: span 3;
                }

                #treatment_plan_modal .plan-field.span-4 {
                    grid-column: span 4;
                }

                #treatment_plan_modal .plan-field.span-6 {
                    grid-column: span 6;
                }

                #treatment_plan_modal .plan-field.span-8 {
                    grid-column: span 8;
                }

                #treatment_plan_modal .plan-field label {
                    display: block;
                    margin-bottom: 8px;
                    font-size: 13px;
                    font-weight: 600;
                    color: #25455e;
                }

                #treatment_plan_modal .plan-field .form-control {
                    height: 40px;
                    border-radius: 6px;
                    border-color: #d5dfe8;
                    box-shadow: none;
                    color: #223645;
                }

                #treatment_plan_modal .plan-field .form-control:focus {
                    border-color: #2d6f94;
                    box-shadow: 0 0 0 3px rgba(45, 111, 148, 0.08);
                }

                #treatment_plan_modal .plan-field textarea.form-control {
                    height: auto;
                    min-height: 110px;
                    resize: vertical;
                    line-height: 1.6;
                    padding-top: 10px;
                }

                #treatment_plan_modal .plan-primary-input {
                    font-size: 15px;
                    font-weight: 500;
                    height: 44px;
                }

                #treatment_plan_modal #planned_procedures {
                    min-height: 136px;
                }

                #treatment_plan_modal #plan_description,
                #treatment_plan_modal #completion_notes {
                    min-height: 104px;
                }

                #treatment_plan_modal .modal-footer {
                    display: flex;
                    justify-content: flex-end;
                    align-items: center;
                    gap: 12px;
                    padding: 16px 24px;
                    border-top: 1px solid #e5eaef;
                    background: #fff;
                }

                #treatment_plan_modal .modal-footer .btn {
                    min-width: 108px;
                    height: 38px;
                    border-radius: 6px;
                    box-shadow: none;
                }

                #treatment_plan_modal .modal-footer .btn-default {
                    border-color: #cfd8e1;
                    color: #5f6f7d;
                    background: #fff;
                }

                #treatment_plan_modal .modal-footer .btn-primary {
                    border-color: #1f5f7d;
                    background: #1f5f7d;
                }

                @media (max-width: 991px) {
                    #treatment_plan_modal .modal-body,
                    #treatment_plan_modal .modal-header,
                    #treatment_plan_modal .modal-footer {
                        padding-left: 18px;
                        padding-right: 18px;
                    }

                    #treatment_plan_modal .plan-field.span-3,
                    #treatment_plan_modal .plan-field.span-4,
                    #treatment_plan_modal .plan-field.span-6,
                    #treatment_plan_modal .plan-field.span-8 {
                        grid-column: span 12;
                    }
                }
            </style>
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="treatment_plan_modal_title">{{ __('medical_cases.add_treatment_plan') }}</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="treatment_plan_form" autocomplete="off">
                    @csrf
                    <input type="hidden" name="treatment_plan_id" id="treatment_plan_id">
                    <input type="hidden" name="medical_case_id" id="plan_medical_case_id">
                    <input type="hidden" name="patient_id" id="plan_patient_id">
                    <div class="plan-form-shell">
                        <section class="plan-form-card is-primary">
                            <div class="plan-card-header">
                                <h5 class="plan-card-title">{{ __('medical_cases.plan_overview') }}</h5>
                            </div>
                            <div class="plan-grid">
                                <div class="plan-field span-12">
                                    <label for="plan_name">{{ __('medical_cases.plan_name') }}</label>
                                    <input type="text" name="plan_name" id="plan_name" class="form-control plan-primary-input" required>
                                </div>
                            </div>
                        </section>

                        <section class="plan-form-card">
                            <div class="plan-card-header">
                                <h5 class="plan-card-title">{{ __('medical_cases.plan_execution') }}</h5>
                            </div>
                            <div class="plan-grid">
                                <div class="plan-field span-3">
                                    <label for="priority">{{ __('medical_cases.priority') }}</label>
                                    <select name="priority" id="priority" class="form-control">
                                        <option value="Low">{{ __('medical_cases.priority_low') }}</option>
                                        <option value="Medium" selected>{{ __('medical_cases.priority_medium') }}</option>
                                        <option value="High">{{ __('medical_cases.priority_high') }}</option>
                                        <option value="Urgent">{{ __('medical_cases.priority_urgent') }}</option>
                                    </select>
                                </div>
                                <div class="plan-field span-3">
                                    <label for="plan_status">{{ __('medical_cases.status') }}</label>
                                    <select name="plan_status" id="plan_status" class="form-control">
                                        <option value="Planned">{{ __('medical_cases.plan_status_planned') }}</option>
                                        <option value="In Progress">{{ __('medical_cases.plan_status_in_progress') }}</option>
                                        <option value="Completed">{{ __('medical_cases.plan_status_completed') }}</option>
                                        <option value="Cancelled">{{ __('medical_cases.plan_status_cancelled') }}</option>
                                    </select>
                                </div>
                                <div class="plan-field span-3">
                                    <label for="start_date">{{ __('medical_cases.start_date') }}</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control">
                                </div>
                                <div class="plan-field span-3">
                                    <label for="target_completion_date">{{ __('medical_cases.target_completion_date') }}</label>
                                    <input type="date" name="target_completion_date" id="target_completion_date" class="form-control">
                                </div>
                                <div class="plan-field span-3">
                                    <label for="estimated_cost">{{ __('medical_cases.estimated_cost') }}</label>
                                    <input type="number" name="estimated_cost" id="estimated_cost" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                        </section>

                        <section class="plan-form-card">
                            <div class="plan-card-header">
                                <h5 class="plan-card-title">{{ __('medical_cases.plan_details') }}</h5>
                            </div>
                            <div class="plan-grid">
                                <div class="plan-field span-8">
                                    <label for="planned_procedures">{{ __('medical_cases.planned_procedures') }}</label>
                                    <textarea name="planned_procedures" id="planned_procedures" class="form-control template-enabled phrase-enabled" data-template-type="treatment_plan" rows="5"></textarea>
                                </div>
                                <div class="plan-field span-4">
                                    <label for="plan_description">{{ __('medical_cases.description') }}</label>
                                    <textarea name="description" id="plan_description" class="form-control template-enabled phrase-enabled" data-template-type="treatment_plan" rows="4"></textarea>
                                </div>
                            </div>
                        </section>

                        <section class="plan-form-card" id="completion_fields_card" style="display: none;">
                            <div class="plan-card-header">
                                <h5 class="plan-card-title">{{ __('medical_cases.completion_information') }}</h5>
                            </div>
                            <div class="plan-grid">
                                <div class="plan-field span-3" id="actual_cost_row">
                                    <label for="actual_cost">{{ __('medical_cases.actual_cost') }}</label>
                                    <input type="number" name="actual_cost" id="actual_cost" class="form-control" step="0.01" min="0">
                                </div>
                                <div class="plan-field span-3" id="actual_completion_date_row">
                                    <label for="actual_completion_date">{{ __('medical_cases.actual_completion_date') }}</label>
                                    <input type="date" name="actual_completion_date" id="actual_completion_date" class="form-control">
                                </div>
                                <div class="plan-field span-6" id="completion_notes_row">
                                    <label for="completion_notes">{{ __('medical_cases.completion_notes') }}</label>
                                    <textarea name="completion_notes" id="completion_notes" class="form-control" rows="4"></textarea>
                                </div>
                            </div>
                        </section>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn btn-primary" id="btn_save_treatment_plan" onclick="saveTreatmentPlan()">{{ __('medical_cases.save_treatment_plan') }}</button>
            </div>
        </div>
    </div>
</div>
