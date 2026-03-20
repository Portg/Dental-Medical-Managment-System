@php
    $activeLabs = \App\Lab::where('is_active', true)->whereNull('deleted_at')->orderBy('name')
        ->get(['id', 'name', 'contact', 'phone', 'specialties', 'avg_turnaround_days']);
    $labCaseDoctors = \App\User::where('is_doctor', true)
        ->whereNull('deleted_at')
        ->orderBy('surname')
        ->get(['id', \Illuminate\Support\Facades\DB::raw("CONCAT(surname, othername) as name")]);
@endphp
<div class="modal fade modal-form modal-form-lg" id="addPatientLabCaseModal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">{{ __('lab_cases.create_lab_case') }}</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none"><ul></ul></div>
                <form action="#" id="patientLabCaseForm" autocomplete="off">
                    @csrf
                    <input type="hidden" name="patient_id" value="{{ $patient->id }}">

                    {{-- ── Basic Info ── --}}
                    @component('components.form.section', ['id' => 'patient-lc-basic-section', 'title' => __('lab_cases.basic_info'), 'icon' => 'fa-file-text-o'])
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="text-primary">{{ __('lab_cases.doctor') }} *</label>
                                    <select name="doctor_id" class="form-control">
                                        <option value="">{{ __('lab_cases.select_doctor') }}</option>
                                        @foreach($labCaseDoctors as $doc)
                                            <option value="{{ $doc->id }}">{{ $doc->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="text-primary">{{ __('lab_cases.lab') }} *</label>
                                    <select id="patient_lc_lab_id" name="lab_id" class="form-control">
                                        <option value="">{{ __('lab_cases.select_lab') }}</option>
                                        @foreach($activeLabs as $lab)
                                            <option value="{{ $lab->id }}"
                                                    data-turnaround="{{ $lab->avg_turnaround_days }}"
                                                    data-contact="{{ $lab->contact }}"
                                                    data-phone="{{ $lab->phone }}"
                                                    data-specialties="{{ $lab->specialties }}">{{ $lab->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>{{ __('lab_cases.processing_days') }}</label>
                                    <input type="number" id="patient_lc_processing_days" name="processing_days" class="form-control" value="7" min="1" max="365">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>{{ __('lab_cases.sent_date') }}</label>
                                    <input type="date" id="patient_lc_sent_date" name="sent_date" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>{{ __('lab_cases.expected_return_date') }}</label>
                                    <input type="date" id="patient_lc_expected_return_date" name="expected_return_date" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>{{ __('lab_cases.lab_fee') }}</label>
                                    <input type="number" name="lab_fee" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>{{ __('lab_cases.patient_charge') }}</label>
                                    <input type="number" name="patient_charge" class="form-control" step="0.01" min="0">
                                </div>
                            </div>
                        </div>
                        {{-- Lab Info Reference Box --}}
                        <div id="patient_lc_lab_info_box" class="info-box" style="display:none; margin-top:12px;">
                            <i class="fa fa-info-circle"></i>
                            <strong>{{ __('lab_cases.lab_info') }}</strong>
                            <span style="margin-left:12px;">{{ __('lab_cases.lab_info_contact') }}: <span id="patient_lc_lab_info_contact">-</span></span>
                            <span style="margin-left:12px;">{{ __('lab_cases.lab_info_phone') }}: <span id="patient_lc_lab_info_phone">-</span></span>
                            <span style="margin-left:12px;">{{ __('lab_cases.lab_info_specialties') }}: <span id="patient_lc_lab_info_specialties">-</span></span>
                            <span style="margin-left:12px;">{{ __('lab_cases.lab_info_turnaround') }}: <span id="patient_lc_lab_info_turnaround">-</span></span>
                        </div>
                        <div class="row" style="margin-top:10px;">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ __('lab_cases.special_requirements') }}</label>
                                    <textarea name="special_requirements" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ __('lab_cases.notes') }}</label>
                                    <textarea name="notes" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                    @endcomponent

                    {{-- ── Items ── --}}
                    @component('components.form.section', ['id' => 'patient-lc-items-section', 'title' => __('lab_cases.items'), 'icon' => 'fa-list'])
                        <div class="billing-table-wrapper" style="max-height:240px; height:auto;">
                            <table class="billing-table">
                                <thead>
                                    <tr>
                                        <th style="width:30px">#</th>
                                        <th>{{ __('lab_cases.prosthesis_type') }} *</th>
                                        <th>{{ __('lab_cases.material') }}</th>
                                        <th>{{ __('lab_cases.color_shade') }}</th>
                                        <th>{{ __('lab_cases.teeth_positions') }}</th>
                                        <th style="width:60px">{{ __('lab_cases.qty') }}</th>
                                        <th style="width:30px"></th>
                                    </tr>
                                </thead>
                                <tbody id="patient-lc-item-rows" class="item-rows-container">
                                    {{-- Rows added by JS --}}
                                </tbody>
                            </table>
                        </div>
                        <div style="padding: 8px 0 0;">
                            <button type="button" class="btn btn-default btn-sm" onclick="addPatientItemRow()">
                                <i class="fa fa-plus"></i> {{ __('lab_cases.add_item_row') }}
                            </button>
                        </div>
                    @endcomponent
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-default" data-dismiss="modal">{{ __('lab_cases.cancel') }}</button>
                <button class="btn btn-primary" id="btn-patient-lc-create" onclick="savePatientLabCase()">{{ __('common.save_changes') }}</button>
            </div>
        </div>
    </div>
</div>
