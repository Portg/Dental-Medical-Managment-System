<div class="modal fade modal-form modal-form-sm" id="editMemberModal" tabindex="-1" role="dialog" aria-labelledby="editMemberModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="editMemberModalLabel">{{ __('members.edit_member') }}</h4>
            </div>
            <div class="modal-body">
                <form id="editMemberForm" class="form-horizontal">
                    @csrf
                    <input type="hidden" name="member_id" id="edit_member_id">
                    <div class="alert alert-danger" style="display:none">
                        <ul></ul>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-md-4 text-primary">{{ __('members.member_no') }}</label>
                        <div class="col-md-8">
                            <input type="text" id="edit_member_no" class="form-control" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-md-4 text-primary">{{ __('members.patient_name') }}</label>
                        <div class="col-md-8">
                            <input type="text" id="edit_patient_name" class="form-control" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-md-4 text-primary">{{ __('members.level') }} <span class="text-danger">*</span></label>
                        <div class="col-md-8">
                            <select name="member_level_id" id="edit_member_level_id" class="form-control">
                                <option value="">{{ __('common.select') }}</option>
                                @foreach($levels as $level)
                                    @php $editDiscountText = $level->discount_rate < 100 ? number_format($level->discount_rate / 10, 1) . __('members.discount_unit') : __('members.no_discount'); @endphp
                                    <option value="{{ $level->id }}" data-discount="{{ $level->discount_rate }}">{{ $level->name }} ({{ $editDiscountText }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-md-4 text-primary">{{ __('members.expiry_date') }}</label>
                        <div class="col-md-8">
                            <input type="date" name="member_expiry" id="edit_member_expiry" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-md-4 text-primary">{{ __('members.status') }}</label>
                        <div class="col-md-8">
                            <select name="member_status" id="edit_member_status" class="form-control">
                                <option value="Active">{{ __('members.status_active') }}</option>
                                <option value="Expired">{{ __('members.status_expired') }}</option>
                                <option value="Inactive">{{ __('members.status_inactive') }}</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn default" data-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn green" onclick="updateMember()">{{ __('common.save') }}</button>
            </div>
        </div>
    </div>
</div>
