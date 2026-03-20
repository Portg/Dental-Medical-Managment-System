<form id="scheduleSettingsForm" class="form-horizontal ss-form">
    @csrf
    <div class="row">
        <div class="col-md-8 col-md-offset-2">

            <div class="ss-section-title">
                <i class="far fa-calendar-check"></i> {{ __('system_settings.schedule_booking_rules') }}
            </div>

            <div class="form-group">
                <label class="control-label col-md-6">
                    {{ __('system_settings.schedule_require_schedule_for_booking') }}
                    <small class="text-muted display-block">{{ __('system_settings.schedule_require_schedule_for_booking_hint') }}</small>
                </label>
                <div class="col-md-4">
                    <div class="ss-toggle">
                        <input type="hidden" name="require_schedule_for_booking" value="0">
                        <input type="checkbox" name="require_schedule_for_booking" id="require_schedule_for_booking"
                               value="1" {{ ($schedule['require_schedule_for_booking'] ?? false) ? 'checked' : '' }}>
                        <label for="require_schedule_for_booking" class="ss-toggle-label"></label>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="col-md-offset-4 col-md-8">
                    <button type="button" class="btn btn-primary" onclick="saveSettings('schedule')">
                        <i class="fa fa-save"></i> {{ __('common.save') }}
                    </button>
                </div>
            </div>

        </div>
    </div>
</form>
