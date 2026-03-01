<form id="clinicSettingsForm" class="form-horizontal ss-form">
    @csrf
    <div class="row">
        <div class="col-md-8 col-md-offset-2">

            {{-- Section: Appointment Time Range --}}
            <div class="ss-section-title">
                <i class="fa fa-clock-o"></i> {{ __('system_settings.clinic_time_range') }}
            </div>

            <div class="form-group">
                <label class="control-label col-md-4">{{ __('system_settings.clinic_start_time') }}</label>
                <div class="col-md-3">
                    <input type="time" name="start_time" class="form-control" value="{{ $clinic['start_time'] ?? '08:30' }}">
                </div>
                <label class="control-label col-md-1" style="text-align:center;">~</label>
                <div class="col-md-3">
                    <input type="time" name="end_time" class="form-control" value="{{ $clinic['end_time'] ?? '18:30' }}">
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-md-4">{{ __('system_settings.clinic_slot_interval') }}</label>
                <div class="col-md-4">
                    <select name="slot_interval" class="form-control">
                        @foreach([15, 30, 60] as $iv)
                            <option value="{{ $iv }}" {{ ($clinic['slot_interval'] ?? 30) == $iv ? 'selected' : '' }}>{{ $iv }} {{ __('system_settings.minutes') }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">{{ __('system_settings.clinic_slot_interval_hint') }}</small>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-md-4">{{ __('system_settings.clinic_default_duration') }}</label>
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="number" name="default_duration" class="form-control" min="15" max="240" step="15"
                               value="{{ $clinic['default_duration'] ?? 30 }}">
                        <span class="input-group-addon">{{ __('system_settings.minutes') }}</span>
                    </div>
                    <small class="text-muted">{{ __('system_settings.clinic_default_duration_hint') }}</small>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-md-4">{{ __('system_settings.clinic_grid_start_hour') }}</label>
                <div class="col-md-2">
                    <input type="number" name="grid_start_hour" class="form-control" min="0" max="23"
                           value="{{ $clinic['grid_start_hour'] ?? 8 }}">
                </div>
                <label class="control-label col-md-1" style="text-align:center;">~</label>
                <div class="col-md-2">
                    <input type="number" name="grid_end_hour" class="form-control" min="1" max="24"
                           value="{{ $clinic['grid_end_hour'] ?? 21 }}">
                </div>
                <div class="col-md-3">
                    <small class="text-muted ss-inline-hint">{{ __('system_settings.clinic_grid_range_hint') }}</small>
                </div>
            </div>

            {{-- Section: Display --}}
            <div class="ss-section-title">
                <i class="fa fa-eye"></i> {{ __('system_settings.clinic_display_settings') }}
            </div>

            <div class="form-group">
                <label class="control-label col-md-4">{{ __('system_settings.clinic_hide_off_duty_doctors') }}</label>
                <div class="col-md-8">
                    <label class="ss-switch">
                        <input type="hidden" name="hide_off_duty_doctors" value="0">
                        <input type="checkbox" name="hide_off_duty_doctors" value="1"
                               {{ ($clinic['hide_off_duty_doctors'] ?? false) ? 'checked' : '' }}>
                        <span class="ss-slider"></span>
                    </label>
                    <small class="text-muted">{{ __('system_settings.clinic_hide_off_duty_doctors_hint') }}</small>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-md-4">{{ __('system_settings.clinic_show_appointment_notes') }}</label>
                <div class="col-md-8">
                    <label class="ss-switch">
                        <input type="hidden" name="show_appointment_notes" value="0">
                        <input type="checkbox" name="show_appointment_notes" value="1"
                               {{ ($clinic['show_appointment_notes'] ?? true) ? 'checked' : '' }}>
                        <span class="ss-slider"></span>
                    </label>
                    <small class="text-muted">{{ __('system_settings.clinic_show_appointment_notes_hint') }}</small>
                </div>
            </div>

            {{-- Section: Rules --}}
            <div class="ss-section-title">
                <i class="fa fa-gavel"></i> {{ __('system_settings.clinic_rules') }}
            </div>

            <div class="form-group">
                <label class="control-label col-md-4">{{ __('system_settings.clinic_allow_overbooking') }}</label>
                <div class="col-md-8">
                    <label class="ss-switch">
                        <input type="hidden" name="allow_overbooking" value="0">
                        <input type="checkbox" name="allow_overbooking" value="1"
                               {{ ($clinic['allow_overbooking'] ?? false) ? 'checked' : '' }}>
                        <span class="ss-slider"></span>
                    </label>
                    <small class="text-muted">{{ __('system_settings.clinic_allow_overbooking_hint') }}</small>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-md-4">{{ __('system_settings.clinic_max_advance_days') }}</label>
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="number" name="max_advance_days" class="form-control" min="0"
                               value="{{ $clinic['max_advance_days'] ?? 90 }}">
                        <span class="input-group-addon">{{ __('system_settings.days') }}</span>
                    </div>
                    <small class="text-muted">{{ __('system_settings.clinic_max_advance_days_hint') }}</small>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-md-4">{{ __('system_settings.clinic_min_advance_hours') }}</label>
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="number" name="min_advance_hours" class="form-control" min="0"
                               value="{{ $clinic['min_advance_hours'] ?? 0 }}">
                        <span class="input-group-addon">{{ __('system_settings.hours') }}</span>
                    </div>
                    <small class="text-muted">{{ __('system_settings.clinic_min_advance_hours_hint') }}</small>
                </div>
            </div>

            <hr>
            <div class="form-group">
                <div class="col-md-8 col-md-offset-4">
                    <button type="button" class="btn btn-primary" onclick="saveSettings('clinic')">
                        <i class="fa fa-check"></i> {{ __('common.save') }}
                    </button>
                </div>
            </div>

        </div>
    </div>
</form>
