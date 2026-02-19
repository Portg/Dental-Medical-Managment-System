<div class="modal fade modal-form" id="memberModal" tabindex="-1" role="dialog" aria-labelledby="memberModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="memberModalLabel">{{ __('members.register_member') }}</h4>
            </div>
            <div class="modal-body">
                <form id="memberForm" class="form-horizontal">
                    @csrf
                    <div class="alert alert-danger" style="display:none">
                        <ul></ul>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-md-4 text-primary">{{ __('members.patient') }} <span class="text-danger">*</span></label>
                        <div class="col-md-8">
                            <select name="patient_id" id="patient_id" class="form-control select2">
                                <option value="">{{ __('common.select') }}</option>
                                @foreach($patients as $patient)
                                    <option value="{{ $patient->id }}">{{ $patient->full_name }} ({{ $patient->patient_no }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-md-4 text-primary">{{ __('members.level') }} <span class="text-danger">*</span></label>
                        <div class="col-md-8">
                            <select name="member_level_id" id="member_level_id" class="form-control">
                                <option value="">{{ __('common.select') }}</option>
                                @foreach($levels as $level)
                                    <option value="{{ $level->id }}" data-discount="{{ $level->discount_rate }}">{{ $level->name }} ({{ 100 - $level->discount_rate }}% {{ __('members.discount') }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-md-4 text-primary">{{ __('members.initial_balance') }}</label>
                        <div class="col-md-8">
                            <input type="number" name="initial_balance" id="initial_balance" class="form-control" step="0.01" min="0" value="0">
                        </div>
                    </div>

                    <div class="form-group" id="payment_method_group" style="display:none;">
                        <label class="control-label col-md-4 text-primary">{{ __('members.payment_method') }}</label>
                        <div class="col-md-8">
                            <select name="payment_method" id="payment_method" class="form-control">
                                <option value="Cash">{{ __('members.payment_cash') }}</option>
                                <option value="Card">{{ __('members.payment_card') }}</option>
                                <option value="Bank Transfer">{{ __('members.payment_bank') }}</option>
                                <option value="Mobile Payment">{{ __('members.payment_mobile') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-md-4 text-primary">{{ __('members.expiry_date') }}</label>
                        <div class="col-md-8">
                            <input type="date" name="member_expiry" id="member_expiry" class="form-control">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn default" data-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn btn-primary" onclick="saveMember()">{{ __('common.save') }}</button>
            </div>
        </div>
    </div>
</div>
