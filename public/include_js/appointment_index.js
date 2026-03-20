// Debounce helper function (design spec: 300ms debounce for auto-search)
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function default_todays_data() {
    // initially load today's date filtered data
    $('.start_date').val(todaysDate());
    $('.end_date').val(todaysDate());
    $("#period_selector").val('Today');
}

// Period selector with auto-apply
$('#period_selector').on('change', function () {
    switch (this.value) {
        case'Today':
            $('.start_date').val(todaysDate());
            $('.end_date').val(todaysDate());
            break;
        case'Yesterday':
            $('.start_date').val(YesterdaysDate());
            $('.end_date').val(YesterdaysDate());
            break;
        case'This week':
            $('.start_date').val(thisWeek());
            $('.end_date').val(todaysDate());
            break;
        case'Last week':
            lastWeek();
            break;
        case'This Month':
            $('.start_date').val(formatDate(thisMonth()));
            $('.end_date').val(todaysDate());
            break;
        case'Last Month':
            lastMonth();
            break;
        default:
            // All - clear dates
            $('.start_date').val('');
            $('.end_date').val('');
            break;
    }
    // Auto-apply filter on change
    $('#appointments-table').DataTable().draw(true);
});

// Toggle advanced filters
$('#toggleAdvancedFilter').on('click', function() {
    $('#advancedFilters').slideToggle(200);
    $(this).toggleClass('active');
});

// Reset filters to initial defaults (today's date)
$('#clearFilters').on('click', function() {
    $('#quickSearch').val('');
    $('#appointment_no_filter').val('');
    $('#filter_doctor').val('');
    $('#filter_invoice_status').val('');
    default_todays_data();
    $('#appointments-table').DataTable().draw(true);
});


let services_arry = [];
var table; // Declare table variable in outer scope

$(function () {
    //hide appointment date time inputs
    $('.appointment_section').hide();

    default_todays_data();  //filter  date
    table = $('#appointments-table').DataTable({
        processing: true,
        serverSide: true,
        language: LanguageManager.getDataTableLang(),  // 使用当前语言配置
        ajax: {
            url: window._appRoutes.appointmentsList,
            data: function (d) {
                d.start_date = $('.start_date').val();
                d.end_date = $('.end_date').val();
                d.appointment_no = $('#appointment_no_filter').val();
                d.quick_search = $('#quickSearch').val();
                d.filter_doctor = $('#filter_doctor').val();
                d.filter_invoice_status = $('#filter_invoice_status').val();
                d.search = $('input[type="search"]').val();
            }
        },
        dom: 'Brtip', // Removed 'f' (default search box) since we have custom search
        buttons: {
            buttons: []
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'start_date', name: 'start_date'},
            {data: 'start_time', name: 'start_time'},
            {data: 'patient', name: 'patient'},
            {data: 'doctor', name: 'doctor'},
            {data: 'visit_information', name: 'visit_information'},
            {data: 'invoice_status', name: 'invoice_status'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ]
    });

    // Quick search with 300ms debounce (design spec: INT-001)
    var debouncedSearch = debounce(function() {
        table.draw(true);
    }, 300);
    $('#quickSearch').on('keyup', debouncedSearch);

    // Appointment No filter with debounce
    $('#appointment_no_filter').on('keyup', debouncedSearch);

    // Auto-apply other filters on change
    $('#filter_doctor, #filter_invoice_status').on('change', function() {
        table.draw(true);
    });

    // Date change also triggers filter
    $('.start_date, .end_date').on('change', function() {
        table.draw(true);
    });
});

function createRecord() {
    // Use new drawer-based appointment form (design spec F-APT-001)
    if (typeof openAppointmentDrawer === 'function') {
        openAppointmentDrawer({
            date: todaysDate()
        });
    } else {
        // Fallback to old modal if drawer not available
        $("#appointment-form")[0].reset();
        $('#id').val('');
        $('#appointment-modal').modal('show');
        $('.appointment_date').val(todaysDate());
        $('#appointment_time').val(currentTimeSelect());
        $('#patient').val([]).trigger('change');
        $('#doctor').val([]).trigger('change');
        $('#btn-save').attr('disabled', false);
        $('#btn-save').text(LanguageManager.trans('common.save_record'));
    }
}


//filter patients
$('#patient').select2({
    language: window._appLocale,
    placeholder: LanguageManager.trans('appointment.choose_patient'),
    minimumInputLength: 2,
    ajax: {
        url: '/search-patient',
        dataType: 'json',
        delay: 300,
        data: function (params) {
            return {
                q: $.trim(params.term)
            };
        },
        processResults: function (data) {
            return {
                results: data
            };
        },
        cache: true
    }
});

//filter doctor
$('#doctor').select2({
    language: window._appLocale,
    placeholder: LanguageManager.trans('appointment.choose_doctor'),
    minimumInputLength: 2,
    ajax: {
        url: '/search-doctor',
        dataType: 'json',
        delay: 300,
        data: function (params) {
            return {
                q: $.trim(params.term)
            };
        },
        processResults: function (data) {
            return {
                results: data
            };
        },
        cache: true
    }
});


//invoice filter doctors

$('#doctor_id').select2({
    language: window._appLocale,
    placeholder: LanguageManager.trans('appointment.procedure_done_by'),
    minimumInputLength: 2,
    ajax: {
        url: '/search-doctor',
        dataType: 'json',
        delay: 300,
        data: function (params) {
            return {
                q: $.trim(params.term)
            };
        },
        processResults: function (data) {
            return {
                results: data
            };
        },
        cache: true
    }
});


function save_data() {
    //check save method
    var id = $('#id').val();
    if (id === "") {
        save_new_record();
    } else {
        update_record();
    }
}

function save_new_record() {
    $.LoadingOverlay("show");
    $('#btn-save').attr('disabled', true);
    $('#btn-save').text(LanguageManager.trans('common.processing'));
    $.ajax({
        type: 'POST',
        data: $('#appointment-form').serialize(),
        url: "/appointments",
        success: function (data) {
            $('#appointment-modal').modal('hide');
            $.LoadingOverlay("hide");
            if (data.status) {
                alert_dialog(data.message, "success");
            } else {
                alert_dialog(data.message, "danger");
            }
        },
        error: function (request, status, error) {
            $.LoadingOverlay("hide");
            $('#btn-save').attr('disabled', false);
            $('#btn-save').text(LanguageManager.trans('common.save_record'));
            $('#appointment-modal').modal('show');
            json = $.parseJSON(request.responseText);
            $.each(json.errors, function (key, value) {
                $('.alert-danger').show();
                $('.alert-danger').append('<p>' + value + '</p>');
            });
        }
    });
}

function editRecord(id) {
    // Use new drawer-based appointment form (design spec F-APT-001)
    if (typeof openAppointmentDrawer === 'function') {
        $.LoadingOverlay("show");
        $.ajax({
            type: 'get',
            url: "appointments/" + id + "/edit",
            success: function (data) {
                $.LoadingOverlay("hide");
                // Open drawer and populate with edit data
                openAppointmentDrawer();

                // Set appointment ID for update
                $('#appointment_id').val(id);
                $('#drawer-title').text(LanguageManager.trans('appointment.edit_appointment'));

                // Populate patient
                let patient_data = {
                    id: data.patient_id,
                    text: LanguageManager.joinName(data.surname, data.othername)
                };
                let patientOption = new Option(patient_data.text, patient_data.id, true, true);
                $('#drawer_patient').append(patientOption).trigger('change');

                // Show patient info if available
                if (typeof showPatientInfoCard === 'function' && data.patient) {
                    showPatientInfoCard(data.patient);
                }

                // Populate doctor
                let doctor_data = {
                    id: data.doctor_id,
                    text: LanguageManager.joinName(data.d_surname, data.d_othername)
                };
                let doctorOption = new Option(doctor_data.text, doctor_data.id, true, true);
                $('#drawer_doctor').append(doctorOption).trigger('change');

                // Populate date and time
                $('#appointment_date').val(data.start_date);
                if (typeof updateWeekday === 'function') {
                    updateWeekday(data.start_date);
                }
                $('#appointment_time').val(data.start_time);

                // Populate other fields
                $('[name="notes"]').val(data.notes);
                if (data.appointment_type) {
                    $('input[name="appointment_type"][value="' + data.appointment_type + '"]').prop('checked', true);
                }
                if (data.duration_minutes) {
                    $('#duration_minutes').val(data.duration_minutes);
                }
                if (data.chair_id) {
                    // Load chair option
                    $.ajax({
                        url: '/api/chairs',
                        success: function(chairs) {
                            chairs.forEach(function(chair) {
                                if (chair.id == data.chair_id) {
                                    let chairOption = new Option(chair.text, chair.id, true, true);
                                    $('#drawer_chair').append(chairOption).trigger('change');
                                }
                            });
                        }
                    });
                }
                if (data.service_id) {
                    // Load service option
                    $.ajax({
                        url: '/search-medical-service?id=' + data.service_id,
                        success: function(services) {
                            if (services && services.length > 0) {
                                let serviceOption = new Option(services[0].text, services[0].id, true, true);
                                $('#drawer_service').append(serviceOption).trigger('change');
                            }
                        }
                    });
                }

                // Load time slots after doctor is set
                setTimeout(function() {
                    if (typeof loadTimeSlots === 'function') {
                        loadTimeSlots();
                        // Highlight selected time after slots load
                        setTimeout(function() {
                            var timeStr = data.start_time;
                            if (timeStr) {
                                // Convert to HH:mm format
                                var timeParts = timeStr.match(/(\d{1,2}):(\d{2})/);
                                if (timeParts) {
                                    var formattedTime = timeParts[1].padStart(2, '0') + ':' + timeParts[2];
                                    $('.time-slot').each(function() {
                                        if ($(this).text().trim().startsWith(formattedTime)) {
                                            $(this).addClass('selected');
                                        }
                                    });
                                }
                            }
                        }, 500);
                    }
                }, 100);
            },
            error: function (request, status, error) {
                $.LoadingOverlay("hide");
                toastr.error(LanguageManager.trans('messages.error_occurred'));
            }
        });
    } else {
        // Fallback to old modal
        $("#appointment-form")[0].reset();
        $('#id').val('');
        $('#btn-save').attr('disabled', false);
        $('#btn-save').text(LanguageManager.trans('common.update_record'));
        $.LoadingOverlay("show");
        $.ajax({
            type: 'get',
            url: "appointments/" + id + "/edit",
            success: function (data) {
                $('#id').val(id);
                let patient_data = { id: data.patient_id, text: LanguageManager.joinName(data.surname, data.othername) };
                $('#patient').append(new Option(patient_data.text, patient_data.id, true, true)).trigger('change');
                let doctor_data = { id: data.doctor_id, text: LanguageManager.joinName(data.d_surname, data.d_othername) };
                $('#doctor').append(new Option(doctor_data.text, doctor_data.id, true, true)).trigger('change');
                $('input[name^="visit_information"][value="' + data.visit_information + '"').prop('checked', true);
                $('[name="notes"]').val(data.notes);
                $('#appointment_status').val(data.status);
                $('.appointment_date').val(data.start_date);
                $('#appointment_time').val(data.start_time);
                $.LoadingOverlay("hide");
                $('#btn-save').text(LanguageManager.trans('common.update_record'));
                $('#appointment-modal').modal('show');
            },
            error: function () { $.LoadingOverlay("hide"); }
        });
    }
}

function update_record() {
    $.LoadingOverlay("show");
    $('#btn-save').attr('disabled', true);
    $('#btn-save').text(LanguageManager.trans('common.updating'));
    $.ajax({
        type: 'PUT',
        data: $('#appointment-form').serialize(),
        url: "/appointments/" + $('#id').val(),
        success: function (data) {
            $('#appointment-modal').modal('hide');
            if (data.status) {
                alert_dialog(data.message, "success");
            } else {
                alert_dialog(data.message, "danger");
            }
            $.LoadingOverlay("hide");
        },
        error: function (request, status, error) {
            $.LoadingOverlay("hide");
            $('#btn-save').attr('disabled', false);
            $('#btn-save').text(LanguageManager.trans('common.save_record'));
            $('#appointment-modal').modal('show');
            json = $.parseJSON(request.responseText);
            $.each(json.errors, function (key, value) {
                $('.alert-danger').show();
                $('.alert-danger').append('<p>' + value + '</p>');
            });
        }
    });
}

//reactivate treatment of the patient
function ReactivateAppointment(id) {
    $("#appointment-form")[0].reset()
    $('#id').val(''); ///always reset hidden form fields
    $('#btn-save').attr('disabled', false);
    $('.modal-title').text(LanguageManager.trans('appointment.re_activate_appointment'));
    $.LoadingOverlay("show");
    $.ajax({
        type: 'get',
        url: "appointments/" + id + "/edit",
        success: function (data) {
            $('#id').val(id);
            let patient_data = {
                id: data.patient_id,
                text: LanguageManager.joinName(data.surname, data.othername)
            };
            let newOption = new Option(patient_data.text, patient_data.id, true, true);
            $('#patient').append(newOption).trigger('change');

            let doctor_data = {
                id: data.doctor_id,
                text: LanguageManager.joinName(data.d_surname, data.d_othername)
            };
            let newOption2 = new Option(doctor_data.text, doctor_data.id, true, true);
            $('#doctor').append(newOption2).trigger('change');
            $('input[name^="visit_information"][value="' + data.visit_information + '"').prop('checked', true);
            $('[name="notes"]').val(data.notes);
            $('#appointment_status').val(data.status);

            // $('#visit_info_section').hide();
            $('#reactivated_appointment').val("yes")
            $.LoadingOverlay("hide");
            $('#btn-save').text(LanguageManager.trans('appointment.reactivate_appointment'))
            $('#appointment-modal').modal('show');

        },
        error: function (request, status, error) {
            $.LoadingOverlay("hide");
        }
    });
}


function deleteRecord(id) {
    var sweetAlertLang = LanguageManager.getSweetAlertLang();
    swal({
            title: LanguageManager.trans('common.are_you_sure'),
            text: LanguageManager.trans('appointment.delete_appointment_warning'),
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: LanguageManager.trans('common.yes_delete_it'),
            closeOnConfirm: false
        },
        function () {

            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
            $.LoadingOverlay("show");
            $.ajax({
                type: 'delete',
                data: {
                    _token: CSRF_TOKEN
                },
                url: "appointments/" + id,
                success: function (data) {
                    if (data.status) {
                        alert_dialog(data.message, "success");
                        if (window._appointmentCalendar) window._appointmentCalendar.refetchEvents();
                        if (window._drgInstance) window._drgInstance._load();
                        if ($('#appointments-table').length) $('#appointments-table').DataTable().draw(false);
                    } else {
                        alert_dialog(data.message, "danger");
                    }
                    $.LoadingOverlay("hide");
                },
                error: function (request, status, error) {
                    $.LoadingOverlay("hide");

                }
            });

        });

}

// system admin and receptionists Invoice generation section

function RecordPayment(id) {
    $("#New-invoice-form")[0].reset();
    $('#btnSave').attr('disabled', false);
    $('#btnSave').text(LanguageManager.trans('appointment.generate_invoice'));

    $('#invoicing_appointment_id').val(id);
    $('#New-invoice-modal').modal('show');
}

$(document).on('click', '.remove-tr', function () {

    $(this).parents('tr').remove();

});
//filter Procedures
$('#service').select2({
    language: window._appLocale,
    placeholder: LanguageManager.trans('appointment.select_procedure'),
    minimumInputLength: 2,
    ajax: {
        url: '/search-medical-service',
        dataType: 'json',
        delay: 300,
        data: function (params) {
            return {
                q: $.trim(params.term)
            };
        },
        processResults: function (data) {
            return {
                results: data
            };
        },
        cache: true
    }
}).on("select2:select", function (e) {
    let price = e.params.data.price;
    if (price != "" || price != 0) {
        $('#procedure_price').val(price);
        $('#procedure_qty').val(1);
        let amount = ($('#procedure_price').val().replace(/,/g, "")) * $('#procedure_qty').val();
        $('#total_amount').val(structureMoney("" + amount));
    } else {
        $('#procedure_price').val('');
        $('#procedure_qty').val('');
    }

});


//get all the services in an array
$(document).ready(function () {

    $('#procedure_qty').on('keyup change', function () {
        if ($(this).val() && $('#procedure_price').val()) {
            $('#total_amount').val(structureMoney("" + $(this).val() * ($('#procedure_price').val().replace(/,/g, ""))))
        } else if (!$(this).val()) {
            $('#total_amount').val("")
        }

    });

    $('#procedure_price').on('keyup change', function () {
        if ($(this).val() && $('#procedure_qty').val()) {
            $('#total_amount').val(structureMoney("" + ($(this).val().replace(/,/g, "")) * $('#procedure_qty').val()))
        } else if (!$(this).val()) {
            $('#total_amount').val("")
        }
    });


    //show appointment date and time section

    $("input[type=radio][name=visit_information]").on("change", function () {
        let action = $("input[type=radio][name=visit_information]:checked").val();

        if (action == "walk_in") {
            //hide appointment date time inputs
            $('.appointment_section').hide();
        } else {
            $('.appointment_date').val(todaysDate());
            $('#appointment_time').val(currentTimeSelect());

            //show appointment date time inputs
            $('.appointment_section').show();
        }

    });


});


let i = 0;
$("#addInvoiceItem").click(function () {
    ++i;

    $("#InvoicesTable").append('<tr>' +
        '<td><select id="service_append' + i + '" name="addmore[' + i + '][medical_service_id]" class="form-control"\n' +
        '                                        style="width: 100%;border: 1px solid #a29e9e;"></select></td>' +
        '<td> <input type="text" name="addmore[' + i + '][tooth_no]" placeholder="' + LanguageManager.trans('appointment.enter_tooth_number') + '"\n' +
        '                                       class="form-control"/></td>' +
        '<td> <input type="number" onkeyup="QTYKeyChange(' + i + ')" id="procedure_qty' + i + '" name="addmore[' + i + '][qty]" placeholder="' + LanguageManager.trans('appointment.enter_qty') + '"\n' +
        '                                       class="form-control"/></td>' +
        '<td> <input type="number" onkeyup="PriceKeyChange(' + i + ')"  id="procedure_price' + i + '" name="addmore[' + i + '][price]" placeholder="' + LanguageManager.trans('appointment.enter_unit_price') + '"\n' +
        '                                       class="form-control"/></td>' +
        '<td> <input type="text"  id="total_amount' + i + '"  class="form-control" readonly/></td>' +
        '<td><select id="doctor_id_append' + i + '" name="addmore[' + i + '][doctor_id]" class="form-control"\n' +
        '                                        style="width: 100%;border: 1px solid #a29e9e;"></select></td>' +
        '<td><button type="button" class="btn btn-danger remove-tr">' + LanguageManager.trans('common.delete') + '</button></td></tr>');

    //append procedure doctor
    $('#doctor_id_append' + i).select2({
        language: window._appLocale,
        placeholder: LanguageManager.trans('appointment.procedure_done_by'),
        minimumInputLength: 2,
        ajax: {
            url: '/search-doctor',
            dataType: 'json',
            delay: 300,
            data: function (params) {
                return {
                    q: $.trim(params.term)
                };
            },
            processResults: function (data) {
                return {
                    results: data
                };
            },
            cache: true
        }
    });


    $('#service_append' + i).select2({
        language: window._appLocale,
        placeholder: LanguageManager.trans('appointment.select_procedure'),
        minimumInputLength: 2,
        ajax: {
            url: '/search-medical-service',
            dataType: 'json',
            delay: 300,
            data: function (params) {
                return {
                    q: $.trim(params.term)
                };
            },
            processResults: function (data) {
                return {
                    results: data
                };
            },
            cache: true
        }
    }).on("select2:select", function (e) {
        let price = e.params.data.price;
        if (price != "" || price != 0) {
            $('#procedure_price' + i).val(price);
            $('#procedure_qty' + i).val(1);
            let amount = ($('#procedure_price' + i).val().replace(/,/g, "")) * $('#procedure_qty' + i).val();
            $('#total_amount' + i).val(structureMoney("" + amount));
        } else {
            $('#procedure_price' + i).val('');
            $('#procedure_qty' + i).val('')
        }

    });


});


function QTYKeyChange(position) {
    if ($('#procedure_qty' + position).val() && $('#procedure_price' + position).val()) {
        $('#total_amount' + position).val(structureMoney("" + $('#procedure_qty' + position).val() * ($('#procedure_price' + position).val().replace(/,/g, ""))))
    } else if (!$('#procedure_qty' + position).val()) {
        $('#total_amount' + position).val("")
    }
}

function PriceKeyChange(position) {
    if ($('#procedure_price' + position).val() && $('#procedure_qty' + position).val()) {
        $('#total_amount' + position).val(structureMoney("" + $('#procedure_price' + position).val() * ($('#procedure_qty' + position).val().replace(/,/g, ""))))
    } else if (!$('#procedure_price' + position).val()) {
        $('#total_amount' + position).val("")
    }
}


function save_invoice() {
    $.LoadingOverlay("show");
    $('#btnSave').attr('disabled', true);
    $('#btnSave').text(LanguageManager.trans('common.processing'));
    $.ajax({
        type: 'POST',
        data: $('#New-invoice-form').serialize(),
        url: "/invoices",
        success: function (data) {
            $('#New-invoice-modal').modal('hide');
            $.LoadingOverlay("hide");
            if (data.status) {
                alert_dialog(data.message, "success");
            } else {
                alert_dialog(data.message, "danger");
            }
        },
        error: function (request, status, error) {
            $.LoadingOverlay("hide");
            $('#New-invoice-modal').modal('show');
            $('#btnSave').attr('disabled', false);
            $('#btnSave').text(LanguageManager.trans('appointment.generate_invoice'));

            json = $.parseJSON(request.responseText);
            $.each(json.errors, function (key, value) {
                $('.alert-danger').show();
                $('.alert-danger').append('<p>' + value + '</p>');
            });
        }
    });
}

function structureMoney(value) {
    return value.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}


function alert_dialog(message, status) {
    swal(LanguageManager.trans('common.alert'), message, status);
    if (status === 'success' && dataTable) {
        dataTable.draw(false);
    }
}
