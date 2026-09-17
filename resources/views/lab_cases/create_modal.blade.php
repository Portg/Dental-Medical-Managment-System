<div class="modal fade modal-form modal-form-lg" id="create-lab-case-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">{{ __('lab_cases.create_lab_case') }}</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none"><ul></ul></div>
                <form action="#" id="create-lab-case-form" autocomplete="off">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-primary">{{ __('lab_cases.patient') }} *</label>
                                <select id="create_patient_id" name="patient_id" class="form-control" style="width:100%"></select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-primary">{{ __('lab_cases.doctor') }} *</label>
                                <select id="create_doctor_id" name="doctor_id" class="form-control" style="width:100%"></select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="text-primary">{{ __('lab_cases.lab') }} *</label>
                                <select id="create_lab_id" name="lab_id" class="form-control">
                                    <option value="">{{ __('lab_cases.select_lab') }}</option>
                                    @foreach($labs as $lab)
                                        <option value="{{ $lab->id }}"
                                                data-turnaround="{{ $lab->avg_turnaround_days ?? '' }}"
                                                data-contact="{{ $lab->contact ?? '' }}"
                                                data-phone="{{ $lab->phone ?? '' }}"
                                                data-specialties="{{ $lab->specialties ?? '' }}">{{ $lab->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('lab_cases.processing_days') }}</label>
                                <input type="number" id="create_processing_days" name="processing_days" class="form-control" value="7" min="1" max="365">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('lab_cases.expected_return_date') }}</label>
                                <input type="text" id="create_expected_return_date" name="expected_return_date" class="form-control js-date" autocomplete="off">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('lab_cases.sent_date') }}</label>
                                <input type="text" id="create_sent_date" name="sent_date" class="form-control js-date" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('lab_cases.lab_fee') }}</label>
                                <input type="number" name="lab_fee" class="form-control" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('lab_cases.patient_charge') }}</label>
                                <input type="number" name="patient_charge" class="form-control" step="0.01" min="0">
                            </div>
                        </div>
                    </div>
                    <div id="create_lab_info_box" class="alert alert-info" style="display:none; padding:8px 12px;">
                        <span>{{ __('lab_cases.lab_info_contact') }}: <strong id="create_lab_info_contact">-</strong></span>
                        <span style="margin-left:12px;">{{ __('lab_cases.lab_info_phone') }}: <strong id="create_lab_info_phone">-</strong></span>
                        <span style="margin-left:12px;">{{ __('lab_cases.lab_info_turnaround') }}: <strong id="create_lab_info_turnaround">-</strong></span>
                    </div>

                    {{-- 明细走 items[]，与 store 校验一致；原先平铺字段提交会被拒 --}}
                    <div class="form-group" style="margin-top:12px;">
                        <label class="text-primary">{{ __('lab_cases.items') }} *</label>
                        <div class="table-responsive">
                            <table class="table table-bordered table-condensed">
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
                                <tbody id="create-item-rows" class="item-rows-container"></tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-xs btn-default" onclick="addItemRow('create')">
                            <i class="fa fa-plus"></i> {{ __('lab_cases.add_item_row') }}
                        </button>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('lab_cases.special_requirements') }}</label>
                                <textarea name="special_requirements" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('lab_cases.notes') }}</label>
                                <textarea name="notes" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-default" data-dismiss="modal">{{ __('lab_cases.cancel') }}</button>
                <button class="btn btn-primary" id="btn-create" onclick="saveLabCase()">{{ __('common.save_changes') }}</button>
            </div>
        </div>
    </div>
</div>
