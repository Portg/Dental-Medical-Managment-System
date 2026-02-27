<div class="modal fade modal-form modal-form-lg" id="schedule-modal" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">{{ __('doctor_schedules.schedule_form') }}</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger" style="display:none">
                    <ul></ul>
                </div>
                <form action="#" id="schedule-form" autocomplete="off">
                    @csrf
                    <input type="hidden" id="id" name="id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-primary">{{ __('doctor_schedules.doctor') }} <span class="text-danger">*</span></label>
                                <select name="doctor_id" class="form-control" required>
                                    <option value="">{{ __('doctor_schedules.select_doctor') }}</option>
                                    @foreach($doctors as $doctor)
                                        <option value="{{ $doctor->id }}">{{ $doctor->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-primary">{{ __('doctor_schedules.branch') }}</label>
                                <select name="branch_id" class="form-control">
                                    <option value="">{{ __('doctor_schedules.select_branch') }}</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="text-primary">{{ __('doctor_schedules.date') }} <span class="text-danger">*</span></label>
                                <input type="text" name="schedule_date" class="form-control datepicker" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="text-primary">{{ __('doctor_schedules.start_time') }} <span class="text-danger">*</span></label>
                                <input type="time" name="start_time" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="text-primary">{{ __('doctor_schedules.end_time') }} <span class="text-danger">*</span></label>
                                <input type="time" name="end_time" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="text-primary">{{ __('doctor_schedules.max_patients') }} <span class="text-danger">*</span></label>
                                <input type="number" name="max_patients" class="form-control" min="1" value="1" required>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label class="text-primary">{{ __('doctor_schedules.recurring') }}</label><br>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="is_recurring" value="1" onchange="toggleRecurring(this)">
                                    {{ __('doctor_schedules.enable_recurring') }}
                                </label>
                            </div>
                        </div>
                    </div>
                    <div id="recurring_options" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="text-primary">{{ __('doctor_schedules.recurring_pattern') }}</label>
                                    <select name="recurring_pattern" class="form-control">
                                        <option value="daily">{{ __('doctor_schedules.pattern_daily') }}</option>
                                        <option value="weekly">{{ __('doctor_schedules.pattern_weekly') }}</option>
                                        <option value="monthly">{{ __('doctor_schedules.pattern_monthly') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="text-primary">{{ __('doctor_schedules.recurring_until') }}</label>
                                    <input type="text" name="recurring_until" class="form-control datepicker">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="text-primary">{{ __('doctor_schedules.notes') }}</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn btn-primary" id="btn-save" onclick="save_data()">{{ __('common.save_changes') }}</button>
            </div>
        </div>
    </div>
</div>
<script>
function toggleRecurring(checkbox) {
    if (checkbox.checked) {
        $('#recurring_options').show();
    } else {
        $('#recurring_options').hide();
    }
}
</script>
