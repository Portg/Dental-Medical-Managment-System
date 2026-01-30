{{-- Appointment Form Drawer - Design spec F-APT-001: 500px right-side drawer --}}
<style>
    /* Drawer styles */
    .appointment-drawer-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1040;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s, visibility 0.3s;
    }
    .appointment-drawer-overlay.open {
        opacity: 1;
        visibility: visible;
    }

    .appointment-drawer {
        position: fixed;
        top: 0;
        right: -520px;
        width: 500px;
        max-width: 95%;
        height: 100%;
        background: #fff;
        z-index: 1050;
        box-shadow: -2px 0 8px rgba(0,0,0,0.15);
        transition: right 0.3s ease;
        display: flex;
        flex-direction: column;
    }
    .appointment-drawer.open {
        right: 0;
    }

    .drawer-header {
        padding: 16px 20px;
        border-bottom: 1px solid #e8e8e8;
        background: linear-gradient(to bottom, #fff, #fafafa);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
    }
    .drawer-header h4 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        color: #333;
    }
    .drawer-close {
        background: none;
        border: none;
        font-size: 24px;
        color: #999;
        cursor: pointer;
        padding: 0;
        line-height: 1;
    }
    .drawer-close:hover {
        color: #333;
    }

    .drawer-body {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
    }

    .drawer-footer {
        padding: 16px 20px;
        border-top: 1px solid #e8e8e8;
        background: #fafafa;
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        flex-shrink: 0;
    }

    /* Form styles */
    .form-section-label {
        font-size: 13px;
        color: #666;
        margin-bottom: 6px;
        display: block;
    }
    .form-section-label .required-asterisk {
        color: #E74C3C;
        margin-right: 2px;
    }

    .form-group-drawer {
        margin-bottom: 20px;
    }

    /* Patient info card */
    .patient-info-card {
        background: #f8f9fa;
        border: 1px solid #e8e8e8;
        border-radius: 6px;
        padding: 12px;
        margin-top: 10px;
        display: none;
    }
    .patient-info-card.show {
        display: block;
    }
    .patient-info-header {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .patient-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: #4472C4;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        font-weight: 600;
    }
    .patient-info-main {
        flex: 1;
    }
    .patient-name {
        font-size: 15px;
        font-weight: 600;
        color: #333;
    }
    .patient-meta {
        font-size: 12px;
        color: #666;
        margin-top: 2px;
    }
    .patient-vip-badge {
        background: #FFD700;
        color: #333;
        padding: 1px 6px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: 600;
        margin-left: 6px;
    }
    .patient-allergy-warning {
        margin-top: 8px;
        padding: 6px 10px;
        background: #fff3cd;
        border: 1px solid #ffc107;
        border-radius: 4px;
        color: #856404;
        font-size: 12px;
    }
    .patient-allergy-warning i {
        margin-right: 4px;
    }
    .patient-last-visit {
        margin-top: 6px;
        font-size: 12px;
        color: #666;
    }

    /* Time slot selector */
    .time-slot-container {
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        padding: 12px;
        background: #fafafa;
    }
    .time-slot-period {
        margin-bottom: 12px;
    }
    .time-slot-period:last-child {
        margin-bottom: 0;
    }
    .time-slot-period-title {
        font-size: 13px;
        font-weight: 600;
        color: #666;
        margin-bottom: 8px;
    }
    .time-slots-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .time-slot {
        min-width: 70px;
        padding: 8px 12px;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        background: #fff;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 13px;
    }
    .time-slot:hover:not(.disabled):not(.booked) {
        border-color: #4472C4;
        background: #f0f5ff;
    }
    .time-slot.selected {
        background: #4472C4;
        border-color: #2B579A;
        color: #fff;
    }
    .time-slot.booked {
        background: #f5f5f5;
        border-color: #e0e0e0;
        color: #999;
        cursor: not-allowed;
    }
    .time-slot.booked .slot-patient {
        font-size: 10px;
        color: #999;
        display: block;
        margin-top: 2px;
    }
    .time-slot.rest {
        background: #f5f5f5;
        border: none;
        color: #ccc;
        cursor: not-allowed;
        text-decoration: line-through;
    }
    .time-slot-divider {
        border-top: 1px dashed #e0e0e0;
        margin: 12px 0;
    }

    /* Drawer form controls */
    .drawer-form-control {
        border-radius: 4px;
        border: 1px solid #d9d9d9;
    }
    .drawer-form-control:focus {
        border-color: #4472C4;
        box-shadow: 0 0 0 2px rgba(68, 114, 196, 0.1);
    }

    /* Visit type radio */
    .visit-type-group {
        display: flex;
        gap: 16px;
    }
    .visit-type-radio {
        display: flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
    }
    .visit-type-radio input {
        margin: 0;
    }

    /* Duration input */
    .duration-input-group {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .duration-input-group input {
        width: 80px;
        text-align: center;
    }
    .duration-input-group span {
        color: #666;
        font-size: 13px;
    }

    /* Chair hint */
    .field-hint {
        font-size: 11px;
        color: #999;
        margin-top: 4px;
    }

    /* Date with weekday */
    .date-weekday {
        display: inline-block;
        margin-left: 8px;
        color: #666;
        font-size: 13px;
    }
</style>

{{-- Drawer overlay --}}
<div class="appointment-drawer-overlay" id="appointment-drawer-overlay" onclick="closeAppointmentDrawer()"></div>

{{-- Drawer --}}
<div class="appointment-drawer" id="appointment-drawer">
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

<script>
// Wait for jQuery to be available
(function checkJQuery() {
    if (typeof jQuery === 'undefined') {
        setTimeout(checkJQuery, 50);
        return;
    }
    var $ = jQuery;
    initAppointmentDrawer();
})();

function initAppointmentDrawer() {
    var $ = jQuery;

// Drawer open/close functions
window.openAppointmentDrawer = function(prefillData) {
    document.getElementById('appointment-drawer').classList.add('open');
    document.getElementById('appointment-drawer-overlay').classList.add('open');
    document.body.style.overflow = 'hidden';

    // Reset form
    resetAppointmentForm();

    // Prefill data if provided
    if (prefillData) {
        if (prefillData.patient_id) {
            // Load patient and select
            loadPatientById(prefillData.patient_id);
        }
        if (prefillData.doctor_id) {
            // Load doctor and select
            loadDoctorById(prefillData.doctor_id);
        }
        if (prefillData.date) {
            $('#appointment_date').val(prefillData.date);
            updateWeekday(prefillData.date);
        }
        if (prefillData.time) {
            $('#appointment_time').val(prefillData.time);
        }
    }
}

window.closeAppointmentDrawer = function() {
    document.getElementById('appointment-drawer').classList.remove('open');
    document.getElementById('appointment-drawer-overlay').classList.remove('open');
    document.body.style.overflow = '';
};

window.resetAppointmentForm = function() {
    $('#appointment-form')[0].reset();
    $('#appointment_id').val('');
    $('#drawer_patient').val(null).trigger('change');
    $('#drawer_doctor').val(null).trigger('change');
    $('#drawer_chair').val('');
    $('#drawer_service').val('');
    $('#patient-info-card').removeClass('show');
    $('#appointment-errors').hide().find('ul').empty();
    $('#date-weekday').text('');
    clearTimeSlots();
    // Set default visit type
    $('input[name="appointment_type"][value="revisit"]').prop('checked', true);
    // Set default duration
    $('#duration_minutes').val(30);
}

window.clearTimeSlots = function() {
    $('#morning-slots-grid').html('<div class="text-muted" style="font-size: 12px;">{{ __("appointment.choose_doctor") }}</div>');
    $('#afternoon-slots-grid').html('');
    $('#appointment_time').val('');
};

// Update weekday display
window.updateWeekday = function(dateStr) {
    if (!dateStr) {
        $('#date-weekday').text('');
        return;
    }
    var date = new Date(dateStr);
    var weekdays = [
        '{{ __("appointment.weekday_sun") }}',
        '{{ __("appointment.weekday_mon") }}',
        '{{ __("appointment.weekday_tue") }}',
        '{{ __("appointment.weekday_wed") }}',
        '{{ __("appointment.weekday_thu") }}',
        '{{ __("appointment.weekday_fri") }}',
        '{{ __("appointment.weekday_sat") }}'
    ];
    $('#date-weekday').text(weekdays[date.getDay()]);
};

// Calculate age from birthday
window.calculateAge = function(birthday) {
    if (!birthday) return null;
    var today = new Date();
    var birthDate = new Date(birthday);
    var age = today.getFullYear() - birthDate.getFullYear();
    var m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    return age;
};

// Show patient info card
window.showPatientInfoCard = function(patient) {
    if (!patient) {
        $('#patient-info-card').removeClass('show');
        return;
    }

    // Avatar (first character of name)
    var firstName = patient.surname || patient.othername || '?';
    $('#patient-avatar').text(firstName.charAt(0));

    // Name
    $('#patient-name').text((patient.surname || '') + ' ' + (patient.othername || ''));

    // VIP badge
    if (patient.member_status === 'Active') {
        $('#patient-vip').show();
    } else {
        $('#patient-vip').hide();
    }

    // Meta (gender + age)
    var gender = patient.gender === 'Male' ? '{{ __("patient.male") }}' : '{{ __("patient.female") }}';
    var age = calculateAge(patient.dob);
    $('#patient-meta').text(gender + (age ? ' ' + age + '{{ __("common.years_old") }}' : ''));

    // Allergy warning
    var hasAllergy = (patient.drug_allergies && patient.drug_allergies.length > 0) ||
                     patient.drug_allergies_other;
    if (hasAllergy) {
        var allergyText = patient.allergies_display || patient.drug_allergies_other || '';
        $('#patient-allergy-text').text('{{ __("appointment.allergy_label") }}：' + allergyText);
        $('#patient-allergy').show();
    } else {
        $('#patient-allergy').hide();
    }

    // Last visit
    if (patient.last_visit) {
        $('#last-visit-text').text(patient.last_visit.date + ' ' + patient.last_visit.service);
    } else {
        $('#last-visit-text').text('-');
    }

    // Auto-determine visit type
    if (patient.has_appointments) {
        $('input[name="appointment_type"][value="revisit"]').prop('checked', true);
    } else {
        $('input[name="appointment_type"][value="first_visit"]').prop('checked', true);
    }

    $('#patient-info-card').addClass('show');
};

// Load time slots for selected doctor and date
window.loadTimeSlots = function() {
    var doctorId = $('#drawer_doctor').val();
    var date = $('#appointment_date').val();

    if (!doctorId || !date) {
        clearTimeSlots();
        return;
    }

    $.ajax({
        url: '/api/doctor-time-slots',
        data: { doctor_id: doctorId, date: date },
        success: function(response) {
            renderTimeSlots(response);
        },
        error: function() {
            clearTimeSlots();
        }
    });
};

// Render time slots
window.renderTimeSlots = function(data) {
    var morningHtml = '';
    var afternoonHtml = '';

    // Default time slots if no schedule data
    var defaultSlots = [
        { time: '09:00', period: 'morning' },
        { time: '09:30', period: 'morning' },
        { time: '10:00', period: 'morning' },
        { time: '10:30', period: 'morning' },
        { time: '11:00', period: 'morning' },
        { time: '11:30', period: 'morning' },
        { time: '14:00', period: 'afternoon' },
        { time: '14:30', period: 'afternoon' },
        { time: '15:00', period: 'afternoon' },
        { time: '15:30', period: 'afternoon' },
        { time: '16:00', period: 'afternoon' },
        { time: '16:30', period: 'afternoon' },
        { time: '17:00', period: 'afternoon' },
        { time: '17:30', period: 'afternoon' }
    ];

    var slots = data && data.slots ? data.slots : defaultSlots;
    var booked = data && data.booked ? data.booked : {};

    slots.forEach(function(slot) {
        var slotTime = slot.time;
        var isBooked = booked[slotTime];
        var isRest = slot.is_rest;
        var slotClass = 'time-slot';
        var slotContent = slotTime;
        var onclick = '';

        if (isRest) {
            slotClass += ' rest';
        } else if (isBooked) {
            slotClass += ' booked';
            slotContent += '<span class="slot-patient">' + isBooked.patient_name + '</span>';
        } else {
            onclick = 'selectTimeSlot(this, "' + slotTime + '")';
        }

        var html = '<div class="' + slotClass + '" onclick="' + onclick + '">' + slotContent + '</div>';

        if (slot.period === 'morning') {
            morningHtml += html;
        } else {
            afternoonHtml += html;
        }
    });

    $('#morning-slots-grid').html(morningHtml || '<div class="text-muted" style="font-size: 12px;">{{ __("appointment.no_available_slots") }}</div>');
    $('#afternoon-slots-grid').html(afternoonHtml);
};

// Select time slot
window.selectTimeSlot = function(element, time) {
    // Remove previous selection
    $('.time-slot.selected').removeClass('selected');
    // Select this slot
    $(element).addClass('selected');
    // Update hidden input
    $('#appointment_time').val(time);
};

// Save appointment
window.saveAppointment = function() {
    var formData = $('#appointment-form').serialize();

    // Validate required fields
    var errors = [];
    if (!$('#drawer_patient').val()) errors.push('{{ __("appointment.patient_required") }}');
    if (!$('#drawer_doctor').val()) errors.push('{{ __("appointment.doctor_required") }}');
    if (!$('#appointment_date').val()) errors.push('{{ __("appointment.date_required") }}');
    if (!$('#appointment_time').val()) errors.push('{{ __("appointment.time_required") }}');

    if (errors.length > 0) {
        var errorHtml = '';
        errors.forEach(function(e) { errorHtml += '<li>' + e + '</li>'; });
        $('#appointment-errors').show().find('ul').html(errorHtml);
        return;
    }

    $.LoadingOverlay("show");
    $('#btn-save-appointment').attr('disabled', true);

    var appointmentId = $('#appointment_id').val();
    var url = appointmentId ? '/appointments/' + appointmentId : '/appointments';
    var method = appointmentId ? 'PUT' : 'POST';

    $.ajax({
        type: method,
        url: url,
        data: formData,
        success: function(response) {
            $.LoadingOverlay("hide");
            $('#btn-save-appointment').attr('disabled', false);

            if (response.status) {
                closeAppointmentDrawer();
                toastr.success(response.message);
                // Refresh calendar or table if exists
                if (typeof refreshAppointments === 'function') {
                    refreshAppointments();
                }
                if ($('#calendar').length && typeof $('#calendar').fullCalendar === 'function') {
                    $('#calendar').fullCalendar('refetchEvents');
                }
                if ($('#appointments-table').length) {
                    $('#appointments-table').DataTable().draw(false);
                }
            } else {
                toastr.error(response.message);
            }
        },
        error: function(xhr) {
            $.LoadingOverlay("hide");
            $('#btn-save-appointment').attr('disabled', false);
            var json = xhr.responseJSON;
            if (json && json.errors) {
                var errorHtml = '';
                $.each(json.errors, function(key, value) {
                    errorHtml += '<li>' + value + '</li>';
                });
                $('#appointment-errors').show().find('ul').html(errorHtml);
            } else {
                toastr.error('{{ __("messages.error_occurred") }}');
            }
        }
    });
};

// Helper to load patient by ID (for prefill)
window.loadPatientById = function(patientId) {
    $.ajax({
        url: '/patients/' + patientId + '/edit',
        success: function(data) {
            var patient = data.patient;
            var option = new Option(patient.surname + ' ' + patient.othername, patient.id, true, true);
            $('#drawer_patient').append(option).trigger('change');
            showPatientInfoCard(patient);
        }
    });
};

// Helper to load doctor by ID (for prefill)
window.loadDoctorById = function(doctorId) {
    $.ajax({
        url: '/users/' + doctorId,
        success: function(user) {
            var option = new Option(user.surname + ' ' + user.othername, user.id, true, true);
            $('#drawer_doctor').append(option).trigger('change');
        }
    });
}

// Initialize on document ready
$(document).ready(function() {
    // Patient select2 with custom template
    $('#drawer_patient').select2({
        language: '{{ app()->getLocale() }}',
        placeholder: '{{ __("appointment.search_patient_placeholder") }}',
        allowClear: true,
        minimumInputLength: 2,
        dropdownParent: $('#appointment-drawer'),
        ajax: {
            url: '/search-patient',
            dataType: 'json',
            delay: 300,
            data: function(params) {
                return { q: params.term };
            },
            processResults: function(data) {
                return {
                    results: data.map(function(item) {
                        var phone = item.phone_no ? item.phone_no.slice(-4) : '';
                        return {
                            id: item.id,
                            text: item.surname + ' ' + item.othername + (phone ? ' ***' + phone : ''),
                            patient: item
                        };
                    })
                };
            }
        }
    }).on('select2:select', function(e) {
        var patient = e.params.data.patient;
        showPatientInfoCard(patient);
    }).on('select2:clear', function() {
        $('#patient-info-card').removeClass('show');
    });

    // Doctor select2
    $('#drawer_doctor').select2({
        language: '{{ app()->getLocale() }}',
        placeholder: '{{ __("appointment.choose_doctor") }}',
        allowClear: true,
        dropdownParent: $('#appointment-drawer'),
        ajax: {
            url: '/search-doctor',
            dataType: 'json',
            data: function(params) {
                return {
                    q: params.term,
                    date: $('#appointment_date').val()
                };
            },
            processResults: function(data) {
                return { results: data };
            }
        }
    }).on('change', function() {
        loadTimeSlots();
    });

    // Chair select2
    $('#drawer_chair').select2({
        language: '{{ app()->getLocale() }}',
        placeholder: '{{ __("appointment.auto_assign") }}',
        allowClear: true,
        dropdownParent: $('#appointment-drawer'),
        ajax: {
            url: '/api/chairs',
            dataType: 'json',
            processResults: function(data) {
                return { results: data };
            }
        }
    });

    // Service select2
    $('#drawer_service').select2({
        language: '{{ app()->getLocale() }}',
        placeholder: '{{ __("appointment.select_procedure") }}',
        allowClear: true,
        dropdownParent: $('#appointment-drawer'),
        ajax: {
            url: '/search-medical-service',
            dataType: 'json',
            data: function(params) {
                return { q: params.term };
            },
            processResults: function(data) {
                return { results: data };
            }
        }
    }).on('select2:select', function(e) {
        // Auto-fill duration based on service
        var service = e.params.data;
        if (service.duration) {
            $('#duration_minutes').val(service.duration);
        }
    });

    // Date picker
    $('#appointment_date').datepicker({
        language: '{{ app()->getLocale() }}',
        format: 'yyyy-mm-dd',
        autoclose: true,
        startDate: new Date(),
        todayHighlight: true
    }).on('changeDate', function(e) {
        updateWeekday(e.format('yyyy-mm-dd'));
        loadTimeSlots();
    });

    // Duration change - future enhancement: multi-slot selection
    $('#duration_minutes').on('change', function() {
        // TODO: Handle multi-slot selection for duration > 30 min
    });
});

// Backward compatibility: createRecord function inside drawer context
window.createRecord = function() {
    openAppointmentDrawer();
};

} // End of initAppointmentDrawer()
</script>
