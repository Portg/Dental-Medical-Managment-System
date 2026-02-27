{{-- Appointment Form Drawer - Design spec F-APT-001: 500px right-side drawer --}}
{{-- CSS: public/css/appointment-drawer.css (loaded by parent page) --}}
{{-- JS:  public/include_js/appointment_drawer.js (loaded by parent page) --}}

{{-- Drawer overlay --}}
<div class="appointment-drawer-overlay" id="appointment-drawer-overlay" onclick="closeAppointmentDrawer()"></div>

{{-- Drawer: data-trans provides server-rendered translations for appointment_drawer.js --}}
@php
$drawerTrans = [
    'search_patient_placeholder' => __('appointment.search_patient_placeholder'),
    'choose_doctor' => __('appointment.choose_doctor'),
    'auto_assign' => __('appointment.auto_assign'),
    'select_procedure' => __('appointment.select_procedure'),
    'weekday_sun' => __('appointment.weekday_sun'),
    'weekday_mon' => __('appointment.weekday_mon'),
    'weekday_tue' => __('appointment.weekday_tue'),
    'weekday_wed' => __('appointment.weekday_wed'),
    'weekday_thu' => __('appointment.weekday_thu'),
    'weekday_fri' => __('appointment.weekday_fri'),
    'weekday_sat' => __('appointment.weekday_sat'),
    'allergy_label' => __('appointment.allergy_label'),
    'no_available_slots' => __('appointment.no_available_slots'),
    'past_slot' => __('appointment.past_slot'),
    'all_slots_past' => __('appointment.all_slots_past'),
    'patient_required' => __('appointment.patient_required'),
    'doctor_required' => __('appointment.doctor_required'),
    'date_required' => __('appointment.date_required'),
    'time_required' => __('appointment.time_required'),
];
@endphp
<div class="appointment-drawer" id="appointment-drawer"
     data-trans="{{ json_encode($drawerTrans) }}">
    <div class="drawer-header">
        <h4 id="drawer-title">{{ __('appointment.new_appointment') }}</h4>
        <button type="button" class="drawer-close" onclick="closeAppointmentDrawer()">&times;</button>
    </div>

    <div class="drawer-body">
        <div class="alert alert-danger" id="appointment-errors" style="display:none">
            <ul></ul>
        </div>

        <form id="appointment-form" autocomplete="off">
            @csrf
            <input type="hidden" id="appointment_id" name="id">

            {{-- Patient Selection --}}
            <div class="form-group-drawer">
                <label class="form-section-label">
                    <span class="required-asterisk">*</span>{{ __('appointment.patient') }}
                </label>
                <select id="drawer_patient" name="patient_id" class="form-control drawer-form-control" style="width: 100%;"></select>

                {{-- Patient Info Card (shown after selection) --}}
                <div class="patient-info-card" id="patient-info-card">
                    <div class="patient-info-header">
                        <div class="patient-avatar" id="patient-avatar">张</div>
                        <div class="patient-info-main">
                            <span class="patient-name" id="patient-name">张三</span>
                            <span class="patient-vip-badge" id="patient-vip" style="display:none;">VIP</span>
                            <div class="patient-meta" id="patient-meta">男 35岁</div>
                        </div>
                    </div>
                    <div class="patient-allergy-warning" id="patient-allergy" style="display:none;">
                        <i class="fa fa-exclamation-triangle"></i>
                        <span id="patient-allergy-text">过敏：青霉素</span>
                    </div>
                    <div class="patient-last-visit" id="patient-last-visit">
                        {{ __('appointment.last_visit') }}：<span id="last-visit-text">-</span>
                    </div>
                </div>
            </div>

            {{-- Appointment Date --}}
            <div class="form-group-drawer">
                <label class="form-section-label">
                    <span class="required-asterisk">*</span>{{ __('appointment.select_date') }}
                </label>
                <div style="display: flex; align-items: center;">
                    <input type="text" id="appointment_date" name="appointment_date"
                           class="form-control drawer-form-control" style="width: 160px;"
                           placeholder="yyyy-mm-dd">
                    <span class="date-weekday" id="date-weekday"></span>
                </div>
            </div>

            {{-- Doctor Selection --}}
            <div class="form-group-drawer">
                <label class="form-section-label">
                    <span class="required-asterisk">*</span>{{ __('appointment.select_doctor') }}
                </label>
                <select id="drawer_doctor" name="doctor_id" class="form-control drawer-form-control" style="width: 100%;"></select>
            </div>

            {{-- Time Slot Selection --}}
            <div class="form-group-drawer">
                <label class="form-section-label">
                    <span class="required-asterisk">*</span>{{ __('appointment.time_slot_selection') }}
                </label>
                <div class="time-slot-container" id="time-slot-container">
                    <div class="time-slot-period" id="morning-slots">
                        <div class="time-slot-period-title">{{ __('appointment.morning') }}</div>
                        <div class="time-slots-grid" id="morning-slots-grid">
                            {{-- Dynamic slots loaded here --}}
                            <div class="text-muted" style="font-size: 12px;">{{ __('appointment.choose_doctor') }}</div>
                        </div>
                    </div>
                    <div class="time-slot-divider"></div>
                    <div class="time-slot-period" id="afternoon-slots">
                        <div class="time-slot-period-title">{{ __('appointment.afternoon') }}</div>
                        <div class="time-slots-grid" id="afternoon-slots-grid">
                            {{-- Dynamic slots loaded here --}}
                        </div>
                    </div>
                </div>
                <input type="hidden" id="appointment_time" name="appointment_time">
            </div>

            {{-- Chair Selection (Optional) --}}
            <div class="form-group-drawer">
                <label class="form-section-label">{{ __('appointment.chair_selection') }}</label>
                <select id="drawer_chair" name="chair_id" class="form-control drawer-form-control" style="width: 100%;">
                    <option value="">{{ __('appointment.auto_assign') }}</option>
                </select>
                <div class="field-hint">{{ __('appointment.chair_optional_hint') }}</div>
            </div>

            {{-- Appointment Service --}}
            <div class="form-group-drawer">
                <label class="form-section-label">{{ __('appointment.appointment_service') }}</label>
                <select id="drawer_service" name="service_id" class="form-control drawer-form-control" style="width: 100%;">
                    <option value=""></option>
                </select>
            </div>

            {{-- Visit Type --}}
            <div class="form-group-drawer">
                <label class="form-section-label">
                    <span class="required-asterisk">*</span>{{ __('appointment.visit_type') }}
                </label>
                <div class="visit-type-group">
                    <label class="visit-type-radio">
                        <input type="radio" name="appointment_type" value="first_visit">
                        {{ __('appointment.first_visit') }}
                    </label>
                    <label class="visit-type-radio">
                        <input type="radio" name="appointment_type" value="revisit" checked>
                        {{ __('appointment.revisit') }}
                    </label>
                </div>
            </div>

            {{-- Estimated Duration --}}
            <div class="form-group-drawer">
                <label class="form-section-label">{{ __('appointment.estimated_duration_label') }}</label>
                <div class="duration-input-group">
                    <input type="number" id="duration_minutes" name="duration_minutes"
                           class="form-control drawer-form-control" value="30" min="15" max="240" step="15">
                    <span>{{ __('appointment.minutes') }}</span>
                </div>
            </div>

            {{-- Notes --}}
            <div class="form-group-drawer">
                <label class="form-section-label">{{ __('appointment.notes') }}</label>
                <textarea id="notes" name="notes" class="form-control drawer-form-control" rows="3"
                          placeholder="{{ __('appointment.enter_general_notes') }}"></textarea>
            </div>

            {{-- Hidden: visit_information for compatibility --}}
            <input type="hidden" name="visit_information" value="appointment">
        </form>
    </div>

    <div class="drawer-footer">
        <button type="button" class="btn btn-default" onclick="closeAppointmentDrawer()">{{ __('common.cancel') }}</button>
        <button type="button" class="btn btn-primary" id="btn-save-appointment" onclick="saveAppointment()">
            {{ __('appointment.confirm_appointment') }}
        </button>
    </div>
</div>

