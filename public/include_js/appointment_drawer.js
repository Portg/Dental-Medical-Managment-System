/**
 * Appointment Drawer - 预约抽屉表单逻辑
 * ========================================
 * Design spec F-APT-001: 500px right-side drawer
 *
 * 依赖：
 *   - jQuery, Select2, Datepicker, LoadingOverlay, toastr
 *   - LanguageManager（用于 joinName、getCurrentLanguage 等通用方法）
 *   - #appointment-drawer[data-trans] 提供预约相关翻译（由 Blade 服务端渲染）
 *
 * 提供的全局函数：
 *   - openAppointmentDrawer(prefillData)
 *   - closeAppointmentDrawer()
 *   - resetAppointmentForm()
 *   - saveAppointment()
 *   - loadTimeSlots()
 *   - selectTimeSlot(element, time)
 *   - showPatientInfoCard(patient)
 *   - loadPatientById(patientId)
 *   - loadDoctorById(doctorId)
 */
(function(window) {
    'use strict';

    initAppointmentDrawer();

    function initAppointmentDrawer() {
        var $ = jQuery;
        var locale = LanguageManager.getCurrentLanguage();

        // Read translations from data-trans attribute (server-side rendered, no JS timing issues)
        var drawerEl = document.getElementById('appointment-drawer');
        var T = {};
        if (drawerEl && drawerEl.getAttribute('data-trans')) {
            try { T = JSON.parse(drawerEl.getAttribute('data-trans')); } catch(e) { T = {}; }
        }

        // Helper: get translation with fallback
        function t(key) {
            return T[key] || LanguageManager.trans('appointment.' + key);
        }

        // =====================================================================
        // Drawer open/close
        // =====================================================================

        // Pending prefill time — set before async loadTimeSlots so
        // renderTimeSlots can auto-highlight the matching slot.
        var _pendingPrefillTime = null;

        window.openAppointmentDrawer = function(prefillData) {
            document.getElementById('appointment-drawer').classList.add('open');
            document.getElementById('appointment-drawer-overlay').classList.add('open');
            document.body.style.overflow = 'hidden';

            _pendingPrefillTime = null;
            resetAppointmentForm();

            if (prefillData) {
                if (prefillData.patient_id) {
                    loadPatientById(prefillData.patient_id);
                }
                if (prefillData.date) {
                    $('#appointment_date').val(prefillData.date);
                    updateWeekday(prefillData.date);
                }
                if (prefillData.time) {
                    _pendingPrefillTime = prefillData.time;
                    $('#appointment_time').val(prefillData.time);
                }
                if (prefillData.duration) {
                    $('#duration_minutes').val(prefillData.duration);
                }
                // Load doctor last so change → loadTimeSlots sees date already set
                if (prefillData.doctor_id) {
                    loadDoctorById(prefillData.doctor_id);
                }
            }
        };

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
            $('input[name="appointment_type"][value="revisit"]').prop('checked', true);
            var cs = window._clinicSettings || {};
            $('#duration_minutes').val(cs.default_duration || 30);
        };

        window.clearTimeSlots = function() {
            $('#morning-slots-grid').html(
                '<div class="text-muted" style="font-size: 12px;">' +
                t('choose_doctor') + '</div>'
            );
            $('#afternoon-slots-grid').html('');
            $('#appointment_time').val('');
        };

        // =====================================================================
        // Weekday display
        // =====================================================================

        window.updateWeekday = function(dateStr) {
            if (!dateStr) {
                $('#date-weekday').text('');
                return;
            }
            var date = new Date(dateStr);
            var weekdays = [
                t('weekday_sun'),
                t('weekday_mon'),
                t('weekday_tue'),
                t('weekday_wed'),
                t('weekday_thu'),
                t('weekday_fri'),
                t('weekday_sat')
            ];
            $('#date-weekday').text(weekdays[date.getDay()]);
        };

        // =====================================================================
        // Patient info card
        // =====================================================================

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

        window.showPatientInfoCard = function(patient) {
            if (!patient) {
                $('#patient-info-card').removeClass('show');
                return;
            }

            // Avatar
            var firstName = patient.surname || patient.othername || '?';
            $('#patient-avatar').text(firstName.charAt(0));

            // Name
            $('#patient-name').text(LanguageManager.joinName(patient.surname, patient.othername));

            // VIP badge
            if (patient.member_status === 'Active') {
                $('#patient-vip').show();
            } else {
                $('#patient-vip').hide();
            }

            // Meta (gender + age)
            var gender = patient.gender === 'Male'
                ? LanguageManager.trans('patient.male')
                : LanguageManager.trans('patient.female');
            var age = calculateAge(patient.dob);
            $('#patient-meta').text(gender + (age ? ' ' + age + LanguageManager.trans('common.years_old') : ''));

            // Allergy warning
            var hasAllergy = (patient.drug_allergies && patient.drug_allergies.length > 0) ||
                             patient.drug_allergies_other;
            if (hasAllergy) {
                var allergyText = patient.allergies_display || patient.drug_allergies_other || '';
                $('#patient-allergy-text').text(t('allergy_label') + '：' + allergyText);
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

        // =====================================================================
        // Time slots
        // =====================================================================

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
                dataType: 'json',
                success: function(response) {
                    renderTimeSlots(response);
                },
                error: function() {
                    clearTimeSlots();
                }
            });
        };

        window.renderTimeSlots = function(data) {
            var morningHtml = '';
            var afternoonHtml = '';

            var cs = window._clinicSettings || {};
            var defaultSlots = (function() {
                var startParts = (cs.start_time || '08:30').split(':');
                var endParts   = (cs.end_time   || '18:30').split(':');
                var interval   = parseInt(cs.slot_interval, 10) || 30;
                var startMin   = parseInt(startParts[0], 10) * 60 + parseInt(startParts[1], 10);
                var endMin     = parseInt(endParts[0], 10) * 60 + parseInt(endParts[1], 10);
                var result = [];
                for (var m = startMin; m < endMin; m += interval) {
                    var hh = ('0' + Math.floor(m / 60)).slice(-2);
                    var mm = ('0' + (m % 60)).slice(-2);
                    result.push({ time: hh + ':' + mm, period: m < 720 ? 'morning' : 'afternoon' });
                }
                return result;
            })();

            var slots = data && data.slots ? data.slots : defaultSlots;
            var booked = data && data.booked ? data.booked : {};

            // Check if selected date is today for past-slot detection
            var selectedDate = $('#appointment_date').val();
            var today = new Date();
            var todayStr = today.getFullYear() + '-' +
                String(today.getMonth() + 1).padStart(2, '0') + '-' +
                String(today.getDate()).padStart(2, '0');
            var isToday = (selectedDate === todayStr);
            var nowMinutes = today.getHours() * 60 + today.getMinutes();
            var allPast = true;

            slots.forEach(function(slot) {
                var slotTime = slot.time;
                var isBooked = booked[slotTime];
                var isRest = slot.is_rest;
                var slotClass = 'time-slot';
                var slotContent = slotTime;
                var onclick = '';

                // Check if this slot is in the past (today only)
                var isPast = false;
                if (isToday) {
                    var parts = slotTime.split(':');
                    var slotMinutes = parseInt(parts[0]) * 60 + parseInt(parts[1]);
                    isPast = slotMinutes < nowMinutes;
                }

                if (isRest) {
                    slotClass += ' rest';
                } else if (isPast) {
                    slotClass += ' past';
                } else if (isBooked) {
                    slotClass += ' booked';
                    slotContent += '<span class="slot-patient">' + isBooked.patient_name + '</span>';
                } else {
                    allPast = false;
                    onclick = "selectTimeSlot(this, '" + slotTime + "')";
                }

                if (!isPast && !isRest) { allPast = false; }

                var html = '<div class="' + slotClass + '" onclick="' + onclick + '">' + slotContent + '</div>';

                if (slot.period === 'morning') {
                    morningHtml += html;
                } else {
                    afternoonHtml += html;
                }
            });

            // Show warning if all slots are past
            var warningHtml = '';
            if (isToday && allPast) {
                warningHtml = '<div class="time-slots-past-warning">' + t('all_slots_past') + '</div>';
            }

            $('#morning-slots-grid').closest('.time-slot-container').find('.time-slots-past-warning').remove();
            if (warningHtml) {
                $('#morning-slots-grid').closest('.time-slot-container').prepend(warningHtml);
            }

            $('#morning-slots-grid').html(morningHtml ||
                '<div class="text-muted" style="font-size: 12px;">' +
                t('no_available_slots') + '</div>'
            );
            $('#afternoon-slots-grid').html(afternoonHtml);

            // Show schedule warning if doctor has no schedule for this date
            $('#morning-slots-grid').closest('.time-slot-container')
                .find('.no-schedule-warning').remove();
            if (data && data.has_schedule === false) {
                var noSchedHtml = '<div class="no-schedule-warning">' +
                    t('doctor_no_schedule_warning') + '</div>';
                $('#morning-slots-grid').closest('.time-slot-container')
                    .prepend(noSchedHtml);
            }

            // Auto-highlight prefilled time slot
            if (_pendingPrefillTime) {
                var prefillTime = _pendingPrefillTime;
                _pendingPrefillTime = null;
                $('.time-slot').each(function() {
                    var $slot = $(this);
                    var slotText = $slot.text().trim().substring(0, 5);
                    if (slotText === prefillTime && !$slot.hasClass('booked') &&
                        !$slot.hasClass('rest') && !$slot.hasClass('past')) {
                        $slot.addClass('selected');
                        $('#appointment_time').val(prefillTime);
                    }
                });
            }
        };

        window.selectTimeSlot = function(element, time) {
            $('.time-slot.selected').removeClass('selected');
            $(element).addClass('selected');
            $('#appointment_time').val(time);
        };

        // =====================================================================
        // Save appointment
        // =====================================================================

        window.saveAppointment = function() {
            var formData = $('#appointment-form').serialize();

            // Validate
            var errors = [];
            if (!$('#drawer_patient').val()) errors.push(t('patient_required'));
            if (!$('#drawer_doctor').val()) errors.push(t('doctor_required'));
            if (!$('#appointment_date').val()) errors.push(t('date_required'));
            if (!$('#appointment_time').val()) errors.push(t('time_required'));

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
                        if (typeof refreshAppointments === 'function') {
                            refreshAppointments();
                        }
                        if (window._appointmentCalendar) {
                            window._appointmentCalendar.refetchEvents();
                        }
                        if (window._drgInstance) {
                            window._drgInstance._load();
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
                        toastr.error(LanguageManager.trans('messages.error_occurred'));
                    }
                }
            });
        };

        // =====================================================================
        // Helpers: load by ID (for prefill / edit)
        // =====================================================================

        window.loadPatientById = function(patientId) {
            $.ajax({
                url: '/patients/' + patientId + '/edit',
                success: function(data) {
                    var patient = data.patient;
                    var option = new Option(
                        LanguageManager.joinName(patient.surname, patient.othername),
                        patient.id, true, true
                    );
                    $('#drawer_patient').append(option).trigger('change');
                    showPatientInfoCard(patient);
                }
            });
        };

        window.loadDoctorById = function(doctorId) {
            $.ajax({
                url: '/appointments/doctor-info/' + doctorId,
                dataType: 'json',
                success: function(user) {
                    var option = new Option(
                        LanguageManager.joinName(user.surname, user.othername),
                        user.id, true, true
                    );
                    $('#drawer_doctor').append(option).trigger('change');
                }
            });
        };

        // =====================================================================
        // Select2 & Datepicker initialization
        // =====================================================================

        $(document).ready(function() {
            // Patient select2
            $('#drawer_patient').select2({
                language: locale,
                placeholder: t('search_patient_placeholder'),
                allowClear: true,
                minimumInputLength: 2,
                dropdownParent: $('#appointment-drawer'),
                ajax: {
                    url: '/search-patient',
                    dataType: 'json',
                    delay: 300,
                    data: function(params) {
                        return { q: params.term, full: 1 };
                    },
                    processResults: function(data) {
                        return {
                            results: data.map(function(item) {
                                var phone = item.phone_no ? item.phone_no.slice(-4) : '';
                                return {
                                    id: item.id,
                                    text: LanguageManager.joinName(item.surname, item.othername) + (phone ? ' ***' + phone : ''),
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
                language: locale,
                placeholder: t('choose_doctor'),
                allowClear: true,
                dropdownParent: $('#appointment-drawer'),
                ajax: {
                    url: '/search-doctor',
                    dataType: 'json',
                    delay: 300,
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
                language: locale,
                placeholder: t('auto_assign'),
                allowClear: true,
                dropdownParent: $('#appointment-drawer'),
                ajax: {
                    url: '/api/chairs',
                    dataType: 'json',
                    delay: 300,
                    processResults: function(data) {
                        return { results: data };
                    }
                }
            });

            // Service select2
            $('#drawer_service').select2({
                language: locale,
                placeholder: t('select_procedure'),
                allowClear: true,
                dropdownParent: $('#appointment-drawer'),
                ajax: {
                    url: '/search-medical-service',
                    dataType: 'json',
                    delay: 300,
                    data: function(params) {
                        return { q: params.term };
                    },
                    processResults: function(data) {
                        return { results: data };
                    }
                }
            }).on('select2:select', function(e) {
                var service = e.params.data;
                if (service.duration) {
                    $('#duration_minutes').val(service.duration);
                }
            });

            // Date picker with advance booking limits
            var dpOptions = {
                language: locale,
                format: 'yyyy-mm-dd',
                autoclose: true,
                startDate: new Date(),
                todayHighlight: true
            };
            var cs = window._clinicSettings || {};
            var maxDays = parseInt(cs.max_advance_days) || 0;
            if (maxDays > 0) {
                var maxDate = new Date();
                maxDate.setDate(maxDate.getDate() + maxDays);
                dpOptions.endDate = maxDate;
            }
            $('#appointment_date').datepicker(dpOptions).on('changeDate', function(e) {
                updateWeekday(e.format('yyyy-mm-dd'));
                loadTimeSlots();
            });
        });

        // Backward compatibility
        window.createRecord = function() {
            openAppointmentDrawer();
        };

    } // End of initAppointmentDrawer()

})(window);
