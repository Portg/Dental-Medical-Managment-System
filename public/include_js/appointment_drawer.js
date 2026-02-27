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

        window.openAppointmentDrawer = function(prefillData) {
            document.getElementById('appointment-drawer').classList.add('open');
            document.getElementById('appointment-drawer-overlay').classList.add('open');
            document.body.style.overflow = 'hidden';

            resetAppointmentForm();

            if (prefillData) {
                if (prefillData.patient_id) {
                    loadPatientById(prefillData.patient_id);
                }
                if (prefillData.doctor_id) {
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
            $('#duration_minutes').val(30);
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

            var defaultSlots = [
                { time: '08:30', period: 'morning' },
                { time: '09:00', period: 'morning' },
                { time: '09:30', period: 'morning' },
                { time: '10:00', period: 'morning' },
                { time: '10:30', period: 'morning' },
                { time: '11:00', period: 'morning' },
                { time: '11:30', period: 'morning' },
                { time: '12:00', period: 'afternoon' },
                { time: '12:30', period: 'afternoon' },
                { time: '13:00', period: 'afternoon' },
                { time: '13:30', period: 'afternoon' },
                { time: '14:00', period: 'afternoon' },
                { time: '14:30', period: 'afternoon' },
                { time: '15:00', period: 'afternoon' },
                { time: '15:30', period: 'afternoon' },
                { time: '16:00', period: 'afternoon' },
                { time: '16:30', period: 'afternoon' },
                { time: '17:00', period: 'afternoon' },
                { time: '17:30', period: 'afternoon' },
                { time: '18:00', period: 'afternoon' }
            ];

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
                url: '/users/' + doctorId,
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

            // Date picker
            $('#appointment_date').datepicker({
                language: locale,
                format: 'yyyy-mm-dd',
                autoclose: true,
                startDate: new Date(),
                todayHighlight: true
            }).on('changeDate', function(e) {
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
