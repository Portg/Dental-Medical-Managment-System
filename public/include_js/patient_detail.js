$(document).ready(function() {
    loadPatientAppointments();
    loadPatientCases();
    loadPatientImages();
    // loadPatientInvoices() — now lazy-loaded via BillingModule in billing_tab
    loadPatientFollowups();
    initImageUploadZone();
});

// Load Patient Appointments
function loadPatientAppointments() {
    $('#patient_appointments_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/appointments',
            type: 'GET',
            data: function(d) {
                d.patient_id = global_patient_id;
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'appointment_no', name: 'appointment_no'},
            {data: 'sort_by', name: 'sort_by'},
            {data: 'doctor_name', name: 'doctor_name', defaultContent: '-'},
            {data: 'status', name: 'status'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        order: [[2, 'desc']],
        language: LanguageManager.getDataTableLang()
    });
}

// Load Patient Medical Cases
function loadPatientCases() {
    $('#patient_cases_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/patient-medical-cases/' + global_patient_id,
            type: 'GET'
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'case_no', name: 'case_no'},
            {data: 'title', name: 'title'},
            {data: 'case_date', name: 'case_date'},
            {data: 'statusBadge', name: 'statusBadge'},
            {data: 'viewBtn', name: 'viewBtn', orderable: false, searchable: false}
        ],
        order: [[3, 'desc']],
        language: LanguageManager.getDataTableLang()
    });
}

// Load Patient Images
function loadPatientImages() {
    $('#patient_images_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/patient-images/' + global_patient_id + '/list',
            type: 'GET'
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'title', name: 'title'},
            {data: 'typeBadge', name: 'typeBadge'},
            {data: 'image_date', name: 'image_date'},
            {data: 'viewBtn', name: 'viewBtn', orderable: false, searchable: false},
            {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
        ],
        order: [[3, 'desc']],
        language: LanguageManager.getDataTableLang()
    });
}

// loadPatientInvoices — removed, now lazy-loaded via BillingModule.initInvoicesTable()


// Load Patient Followups
function loadPatientFollowups() {
    $('#patient_followups_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/patient-followups/' + global_patient_id + '/list',
            type: 'GET'
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'scheduled_date', name: 'scheduled_date'},
            {data: 'typeBadge', name: 'typeBadge'},
            {data: 'purpose', name: 'purpose'},
            {data: 'statusBadge', name: 'statusBadge'},
            {data: 'viewBtn', name: 'viewBtn', orderable: false, searchable: false},
            {data: 'deleteBtn', name: 'deleteBtn', orderable: false, searchable: false}
        ],
        order: [[1, 'desc']],
        language: LanguageManager.getDataTableLang()
    });
}

// Add Patient Image
function addPatientImage() {
    $('#patientImageForm')[0].reset();
    $('#patientImageForm .alert-danger').hide();
    resetImagePreview();
    $('#addImageModal').modal('show');
}

// Image upload preview
function initImageUploadZone() {
    var zone = document.getElementById('imageUploadZone');
    var input = document.getElementById('image_file');
    if (!zone || !input) return;

    // Drag-drop visual feedback
    ['dragenter', 'dragover'].forEach(function(evt) {
        zone.addEventListener(evt, function(e) {
            e.preventDefault();
            zone.classList.add('dragover');
        });
    });
    ['dragleave', 'drop'].forEach(function(evt) {
        zone.addEventListener(evt, function(e) {
            e.preventDefault();
            zone.classList.remove('dragover');
        });
    });

    // Handle drop
    zone.addEventListener('drop', function(e) {
        var files = e.dataTransfer.files;
        if (files.length > 0) {
            input.files = files;
            showImagePreview(files[0]);
        }
    });

    // Handle file input change
    input.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            showImagePreview(this.files[0]);
        }
    });

    // Remove button
    var removeBtn = document.getElementById('imagePreviewRemove');
    if (removeBtn) {
        removeBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            input.value = '';
            resetImagePreview();
        });
    }
}

function showImagePreview(file) {
    var placeholder = document.getElementById('imageUploadPlaceholder');
    var preview = document.getElementById('imageUploadPreview');
    var img = document.getElementById('imagePreviewImg');
    var nameEl = document.getElementById('imagePreviewName');
    var metaEl = document.getElementById('imagePreviewMeta');

    if (!placeholder || !preview) return;

    // Show thumbnail
    var reader = new FileReader();
    reader.onload = function(e) {
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);

    // File info
    nameEl.textContent = file.name;
    var sizeMB = (file.size / 1024 / 1024).toFixed(2);
    metaEl.textContent = sizeMB + ' MB';

    placeholder.style.display = 'none';
    preview.style.display = 'flex';
}

function resetImagePreview() {
    var placeholder = document.getElementById('imageUploadPlaceholder');
    var preview = document.getElementById('imageUploadPreview');
    if (placeholder) placeholder.style.display = '';
    if (preview) preview.style.display = 'none';
}


function savePatientImage() {
    var formData = new FormData($('#patientImageForm')[0]);

    $('.loading').show();

    $.ajax({
        url: '/patient-images',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            $('.loading').hide();
            if (response.status) {
                $('#addImageModal').modal('hide');
                $('#patient_images_table').DataTable().ajax.reload();
                swal({
                    title: LanguageManager.trans('messages.success'),
                    text: response.message,
                    type: 'success'
                });
            } else {
                swal({
                    title: LanguageManager.trans('messages.error'),
                    text: response.message,
                    type: 'error'
                });
            }
        },
        error: function(xhr) {
            $('.loading').hide();
            if (xhr.status === 422) {
                var errors = xhr.responseJSON.errors;
                var errorList = $('#patientImageForm .alert-danger ul');
                errorList.empty();
                $.each(errors, function(key, value) {
                    errorList.append('<li>' + value[0] + '</li>');
                });
                $('#patientImageForm .alert-danger').show();
            } else {
                swal({
                    title: LanguageManager.trans('messages.error'),
                    text: LanguageManager.trans('messages.error_occurred'),
                    type: 'error'
                });
            }
        }
    });
}

function viewImage(id) {
    $('.loading').show();
    $.ajax({
        url: '/patient-images/' + id,
        type: 'GET',
        success: function(response) {
            $('.loading').hide();

            $('#view_image_title').text(response.title);
            $('#view_image_type').text(response.image_type);
            $('#view_image_date').text(response.image_date);
            $('#view_tooth_number').text(response.tooth_number || '-');
            $('#view_image_description').text(response.description || '-');

            $('#view_image_src').attr('src', '/' + response.file_path);
            $('#download_image_btn').attr('href', '/' + response.file_path);

            $('#viewImageModal').modal('show');
        },
        error: function() {
            $('.loading').hide();
            swal({
                title: LanguageManager.trans('messages.error'),
                text: LanguageManager.trans('messages.error_occurred'),
                type: 'error'
            });
        }
    });
}

function deleteImage(id) {
    swal({
        title: LanguageManager.trans('messages.are_you_sure'),
        text: LanguageManager.trans('patient_images.delete_confirmation'),
        type: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: LanguageManager.trans('common.delete'),
        cancelButtonText: LanguageManager.trans('common.cancel')
    }, function(isConfirm) {
        if (isConfirm) {
            $('.loading').show();
            $.ajax({
                url: '/patient-images/' + id,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $('.loading').hide();
                    if (response.status) {
                        $('#patient_images_table').DataTable().ajax.reload();
                        swal({
                            title: LanguageManager.trans('messages.success'),
                            text: response.message,
                            type: 'success'
                        });
                    } else {
                        swal({
                            title: LanguageManager.trans('messages.error'),
                            text: response.message,
                            type: 'error'
                        });
                    }
                },
                error: function() {
                    $('.loading').hide();
                    swal({
                        title: LanguageManager.trans('messages.error'),
                        text: LanguageManager.trans('messages.error_occurred'),
                        type: 'error'
                    });
                }
            });
        }
    });
}

// Add Patient Followup
function addPatientFollowup() {
    $('#patientFollowupForm')[0].reset();
    $('#patientFollowupForm .alert-danger').hide();
    $('#addFollowupModal').modal('show');
}

function savePatientFollowup() {
    var formData = $('#patientFollowupForm').serialize();

    $('.loading').show();

    $.ajax({
        url: '/patient-followups',
        type: 'POST',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            $('.loading').hide();
            if (response.status) {
                $('#addFollowupModal').modal('hide');
                $('#patient_followups_table').DataTable().ajax.reload();
                swal({
                    title: LanguageManager.trans('messages.success'),
                    text: response.message,
                    type: 'success'
                });
            } else {
                swal({
                    title: LanguageManager.trans('messages.error'),
                    text: response.message,
                    type: 'error'
                });
            }
        },
        error: function(xhr) {
            $('.loading').hide();
            if (xhr.status === 422) {
                var errors = xhr.responseJSON.errors;
                var errorList = $('#patientFollowupForm .alert-danger ul');
                errorList.empty();
                $.each(errors, function(key, value) {
                    errorList.append('<li>' + value[0] + '</li>');
                });
                $('#patientFollowupForm .alert-danger').show();
            } else {
                swal({
                    title: LanguageManager.trans('messages.error'),
                    text: LanguageManager.trans('messages.error_occurred'),
                    type: 'error'
                });
            }
        }
    });
}

function viewFollowup(id) {
    $('.loading').show();
    $.ajax({
        url: '/patient-followups/' + id,
        type: 'GET',
        success: function(response) {
            $('.loading').hide();

            $('#view_followup_no').text(response.followup_no);
            var typeKey = 'patient_followups.type_' + response.followup_type.toLowerCase();
            $('#view_followup_type').text(LanguageManager.trans(typeKey) || response.followup_type);
            $('#view_scheduled_date').text(response.scheduled_date);
            $('#view_followup_purpose').text(response.purpose);
            $('#view_followup_notes').text(response.notes || '-');

            var statusClass = 'default';
            if (response.status == 'Pending') statusClass = 'warning';
            else if (response.status == 'Completed') statusClass = 'success';
            else if (response.status == 'Cancelled') statusClass = 'danger';
            else if (response.status == 'No Response') statusClass = 'info';

            var statusKey = 'patient_followups.status_' + response.status.toLowerCase().replace(/ /g, '_');
            var statusText = LanguageManager.trans(statusKey) || response.status;
            $('#view_followup_status').html('<span class="label label-' + statusClass + '">' + statusText + '</span>');

            if (response.status == 'Completed' && response.outcome) {
                $('#view_outcome_row').show();
                $('#view_followup_outcome').text(response.outcome);
            } else {
                $('#view_outcome_row').hide();
            }

            $('#viewFollowupModal').modal('show');
        },
        error: function() {
            $('.loading').hide();
            swal({
                title: LanguageManager.trans('messages.error'),
                text: LanguageManager.trans('messages.error_occurred'),
                type: 'error'
            });
        }
    });
}

function deleteFollowup(id) {
    swal({
        title: LanguageManager.trans('messages.are_you_sure'),
        text: LanguageManager.trans('patient_followups.delete_confirmation'),
        type: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: LanguageManager.trans('common.delete'),
        cancelButtonText: LanguageManager.trans('common.cancel')
    }, function(isConfirm) {
        if (isConfirm) {
            $('.loading').show();
            $.ajax({
                url: '/patient-followups/' + id,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $('.loading').hide();
                    if (response.status) {
                        $('#patient_followups_table').DataTable().ajax.reload();
                        swal({
                            title: LanguageManager.trans('messages.success'),
                            text: response.message,
                            type: 'success'
                        });
                    } else {
                        swal({
                            title: LanguageManager.trans('messages.error'),
                            text: response.message,
                            type: 'error'
                        });
                    }
                },
                error: function() {
                    $('.loading').hide();
                    swal({
                        title: LanguageManager.trans('messages.error'),
                        text: LanguageManager.trans('messages.error_occurred'),
                        type: 'error'
                    });
                }
            });
        }
    });
}

// ═══════════════════════════════════════════════════════════════
// Lab Cases (Patient Detail)
// ═══════════════════════════════════════════════════════════════

var patientLcItemIndex = 0;

function loadPatientLabCases() {
    $('#patient_lab_cases_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/patient-lab-cases/' + global_patient_id,
            type: 'GET'
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'lab_case_no', name: 'lab_case_no'},
            {data: 'lab_name', name: 'lab_name', orderable: false, searchable: false},
            {data: 'items_summary', name: 'items_summary', orderable: false, searchable: false},
            {data: 'status_label', name: 'status_label', orderable: false},
            {data: 'expected_return_date', name: 'expected_return_date'}
        ],
        order: [[1, 'desc']],
        language: LanguageManager.getDataTableLang()
    });
}

function createPatientLabCase() {
    $('#patientLabCaseForm')[0].reset();
    $('#patientLabCaseForm .alert-danger').hide();
    $('#patient_lc_lab_info_box').hide();
    $('#patient-lc-item-rows').empty();
    patientLcItemIndex = 0;
    addPatientItemRow();
    $('#addPatientLabCaseModal').modal('show');
}

function savePatientLabCase() {
    $('.loading').show();
    $('#btn-patient-lc-create').attr('disabled', true).text(LanguageManager.trans('common.processing'));

    $.ajax({
        url: '/lab-cases',
        type: 'POST',
        data: $('#patientLabCaseForm').serialize(),
        success: function(response) {
            $('.loading').hide();
            $('#btn-patient-lc-create').attr('disabled', false).text(LanguageManager.trans('common.save_changes'));
            if (response.status) {
                $('#addPatientLabCaseModal').modal('hide');
                $('#patient_lab_cases_table').DataTable().ajax.reload();
                swal({
                    title: LanguageManager.trans('messages.success'),
                    text: response.message,
                    type: 'success'
                });
            } else {
                swal({
                    title: LanguageManager.trans('messages.error'),
                    text: response.message,
                    type: 'error'
                });
            }
        },
        error: function(xhr) {
            $('.loading').hide();
            $('#btn-patient-lc-create').attr('disabled', false).text(LanguageManager.trans('common.save_changes'));
            if (xhr.status === 422) {
                var errors = xhr.responseJSON.errors;
                var errorList = $('#patientLabCaseForm .alert-danger ul');
                errorList.empty();
                $.each(errors, function(key, value) {
                    errorList.append('<li>' + value + '</li>');
                });
                $('#patientLabCaseForm .alert-danger').show();
            } else {
                swal({
                    title: LanguageManager.trans('messages.error'),
                    text: LanguageManager.trans('messages.error_occurred'),
                    type: 'error'
                });
            }
        }
    });
}

function addPatientItemRow() {
    var container = $('#patient-lc-item-rows');
    var count = container.find('.item-row').length;
    if (count >= 4) {
        swal(LanguageManager.trans('common.alert'), LanguageManager.trans('lab_cases.max_items_reached'), 'warning');
        return;
    }

    var idx = patientLcItemIndex++;
    var rowNum = count + 1;
    var html = buildPatientItemRowHtml(idx, rowNum);
    container.append(html);
}

function removePatientItemRow(btn) {
    var container = $(btn).closest('.item-rows-container');
    $(btn).closest('.item-row').remove();
    // Re-number rows
    container.find('.item-row').each(function(i) {
        $(this).find('.item-row-num').text(i + 1);
    });
    if (container.find('.item-row').length === 0) {
        addPatientItemRow();
    }
}

function buildPatientItemRowHtml(idx, rowNum) {
    var data = (typeof patientLabCaseData !== 'undefined') ? patientLabCaseData : {prosthesisTypes: {}, materials: {}};

    var prosthesisOptions = '<option value="">--</option>';
    $.each(data.prosthesisTypes, function(key, label) {
        prosthesisOptions += '<option value="' + key + '">' + label + '</option>';
    });

    var materialOptions = '<option value="">--</option>';
    $.each(data.materials, function(key, label) {
        materialOptions += '<option value="' + key + '">' + label + '</option>';
    });

    return '<tr class="item-row">' +
        '<td class="item-row-num">' + rowNum + '</td>' +
        '<td><select name="items[' + idx + '][prosthesis_type]" class="form-control input-sm">' + prosthesisOptions + '</select></td>' +
        '<td><select name="items[' + idx + '][material]" class="form-control input-sm">' + materialOptions + '</select></td>' +
        '<td><input type="text" name="items[' + idx + '][color_shade]" class="form-control input-sm" placeholder="A2"></td>' +
        '<td><input type="text" name="items[' + idx + '][teeth_positions]" class="form-control input-sm" placeholder="11, 12"></td>' +
        '<td><input type="number" name="items[' + idx + '][qty]" class="form-control input-sm input-qty" value="1" min="1" max="99"></td>' +
        '<td><span class="btn-remove-row" onclick="removePatientItemRow(this)"><i class="fa fa-times-circle"></i></span></td>' +
    '</tr>';
}

// Lab change → auto-fill processing days + show lab info + calc expected date (patient modal)
$(document).on('change', '#patient_lc_lab_id', function() {
    var option = $(this).find('option:selected');
    var turnaround = option.data('turnaround');
    if (turnaround) {
        $('#patient_lc_processing_days').val(turnaround);
        calcPatientLcExpectedDate();
    }
    // Show lab info reference
    var infoBox = $('#patient_lc_lab_info_box');
    if (option.val()) {
        $('#patient_lc_lab_info_contact').text(option.data('contact') || '-');
        $('#patient_lc_lab_info_phone').text(option.data('phone') || '-');
        $('#patient_lc_lab_info_specialties').text(option.data('specialties') || '-');
        $('#patient_lc_lab_info_turnaround').text(turnaround || '-');
        infoBox.show();
    } else {
        infoBox.hide();
    }
});

$(document).on('change', '#patient_lc_processing_days, #patient_lc_sent_date', function() {
    calcPatientLcExpectedDate();
});

function calcPatientLcExpectedDate() {
    var sentDate = $('#patient_lc_sent_date').val();
    var days = parseInt($('#patient_lc_processing_days').val());
    if (sentDate && days > 0) {
        var d = new Date(sentDate);
        d.setDate(d.getDate() + days);
        var yyyy = d.getFullYear();
        var mm = String(d.getMonth() + 1).padStart(2, '0');
        var dd = String(d.getDate()).padStart(2, '0');
        $('#patient_lc_expected_return_date').val(yyyy + '-' + mm + '-' + dd);
    }
}

// ═══════════════════════════════════════════════════════════════
// Prescriptions (Patient Detail)
// ═══════════════════════════════════════════════════════════════

var rxItemIndex = 0;
var rxServicesCache = null;

function escapeHtml(str) {
    if (!str) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

function loadPatientPrescriptions() {
    $('#patient_prescriptions_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/prescriptions/patient/' + global_patient_id,
            type: 'GET'
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'prescription_no', name: 'prescription_no'},
            {data: 'prescription_date', name: 'prescription_date'},
            {data: 'doctor_name', name: 'doctor_name', defaultContent: '-'},
            {data: 'status_label', name: 'status_label', orderable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        order: [[2, 'desc']],
        language: LanguageManager.getDataTableLang()
    });
}

// Load prescription services (cached)
function loadRxServices(callback) {
    if (rxServicesCache) {
        callback(rxServicesCache);
        return;
    }
    $.get('/prescriptions/services', function(resp) {
        if (resp.status && resp.data) {
            rxServicesCache = resp.data;
            callback(rxServicesCache);
        }
    });
}

// Create new prescription
function createPatientPrescription() {
    loadRxServices(function() {
        $('#patientPrescriptionForm')[0].reset();
        $('#patientPrescriptionForm .alert-danger').hide();
        $('#rx_edit_id').val('');
        $('#rx_date').val(new Date().toISOString().slice(0, 10));
        $('#prescriptionModalTitle').text(LanguageManager.trans('prescriptions.create_prescription'));
        $('#btn-rx-settle').show();
        $('#rxItemRows').empty();
        rxItemIndex = 0;
        addRxItemRow();
        $('#addPrescriptionModal').modal('show');
    });
}

// Add item row
function addRxItemRow(itemData) {
    var idx = rxItemIndex++;
    var rowNum = $('#rxItemRows .rx-item-row').length + 1;

    var serviceOptions = '<option value="">--</option>';
    if (rxServicesCache) {
        $.each(rxServicesCache, function(i, svc) {
            var selected = (itemData && itemData.medical_service_id == svc.id) ? ' selected' : '';
            serviceOptions += '<option value="' + svc.id + '" data-price="' + svc.price + '" data-unit="' + escapeHtml(svc.unit || '') + '"' + selected + '>' + escapeHtml(svc.name) + '</option>';
        });
    }

    var qty = (itemData && itemData.quantity) ? itemData.quantity : 1;
    var price = (itemData && itemData.unit_price) ? parseFloat(itemData.unit_price).toFixed(2) : '0.00';
    var amount = (itemData && itemData.unit_price && itemData.quantity)
        ? (parseFloat(itemData.unit_price) * parseInt(itemData.quantity)).toFixed(2) : '0.00';
    var dosage = (itemData && itemData.dosage) ? itemData.dosage : '';
    var frequency = (itemData && itemData.frequency) ? itemData.frequency : '';

    var html = '<tr class="rx-item-row" data-idx="' + idx + '">' +
        '<td class="rx-row-num">' + rowNum + '</td>' +
        '<td><select name="items[' + idx + '][medical_service_id]" class="form-control input-sm rx-service-select" data-idx="' + idx + '">' + serviceOptions + '</select></td>' +
        '<td><input type="number" name="items[' + idx + '][quantity]" class="form-control input-sm rx-qty-input" data-idx="' + idx + '" value="' + qty + '" min="1"></td>' +
        '<td class="rx-unit-price">' + price + '</td>' +
        '<td class="rx-line-amount">' + amount + '</td>' +
        '<td><input type="text" name="items[' + idx + '][dosage]" class="form-control input-sm" value="' + dosage + '" placeholder="' + LanguageManager.trans('prescriptions.dosage') + '"></td>' +
        '<td><input type="text" name="items[' + idx + '][frequency]" class="form-control input-sm" value="' + frequency + '" placeholder="' + LanguageManager.trans('prescriptions.frequency') + '"></td>' +
        '<td><span class="btn-remove-row" onclick="removeRxItemRow(this)"><i class="fa fa-times-circle"></i></span></td>' +
        '</tr>';

    $('#rxItemRows').append(html);
    calcRxTotal();
}

// Remove item row
function removeRxItemRow(btn) {
    $(btn).closest('.rx-item-row').remove();
    // Re-number
    $('#rxItemRows .rx-item-row').each(function(i) {
        $(this).find('.rx-row-num').text(i + 1);
    });
    if ($('#rxItemRows .rx-item-row').length === 0) {
        addRxItemRow();
    }
    calcRxTotal();
}

// Service selection change → update price
$(document).on('change', '.rx-service-select', function() {
    var row = $(this).closest('.rx-item-row');
    var price = $(this).find(':selected').data('price') || 0;
    row.find('.rx-unit-price').text(parseFloat(price).toFixed(2));
    calcRxLineAmount(row, price);
});

// Quantity change → update amount
$(document).on('change input', '.rx-qty-input', function() {
    var row = $(this).closest('.rx-item-row');
    var price = row.find('.rx-service-select :selected').data('price') || 0;
    calcRxLineAmount(row, price);
});

function calcRxLineAmount(row, price) {
    var qty = parseInt(row.find('.rx-qty-input').val()) || 1;
    var amount = (parseFloat(price) * qty).toFixed(2);
    row.find('.rx-line-amount').text(amount);
    calcRxTotal();
}

function calcRxTotal() {
    var total = 0;
    $('#rxItemRows .rx-line-amount').each(function() {
        total += parseFloat($(this).text()) || 0;
    });
    $('#rxTotalAmount').text(total.toFixed(2));
}

// Save prescription (create or update)
function savePatientPrescription(settle) {
    var editId = $('#rx_edit_id').val();
    var formData = $('#patientPrescriptionForm').serializeArray();
    if (settle) {
        formData.push({name: 'settle', value: '1'});
    }

    var url = editId ? '/prescriptions/' + editId : '/prescriptions';
    var method = editId ? 'PUT' : 'POST';

    $('.loading').show();
    $('#btn-rx-save, #btn-rx-settle').attr('disabled', true);

    $.ajax({
        url: url,
        type: method,
        data: formData,
        success: function(response) {
            $('.loading').hide();
            $('#btn-rx-save, #btn-rx-settle').attr('disabled', false);
            if (response.status) {
                $('#addPrescriptionModal').modal('hide');
                if ($('#patient_prescriptions_table').DataTable()) {
                    $('#patient_prescriptions_table').DataTable().ajax.reload();
                }
                swal({
                    title: LanguageManager.trans('messages.success'),
                    text: response.message,
                    type: 'success'
                });
            } else {
                swal({
                    title: LanguageManager.trans('messages.error'),
                    text: response.message,
                    type: 'error'
                });
            }
        },
        error: function(xhr) {
            $('.loading').hide();
            $('#btn-rx-save, #btn-rx-settle').attr('disabled', false);
            if (xhr.status === 422) {
                var errors = xhr.responseJSON.errors || {};
                var errorList = $('#patientPrescriptionForm .alert-danger ul');
                errorList.empty();
                $.each(errors, function(key, value) {
                    errorList.append('<li>' + (Array.isArray(value) ? value[0] : value) + '</li>');
                });
                $('#patientPrescriptionForm .alert-danger').show();
            } else {
                swal({
                    title: LanguageManager.trans('messages.error'),
                    text: LanguageManager.trans('messages.error_occurred'),
                    type: 'error'
                });
            }
        }
    });
}

// View prescription detail
function viewPrescription(id) {
    $('.loading').show();
    $.get('/prescriptions/' + id, function(resp) {
        $('.loading').hide();
        if (!resp.status || !resp.data) {
            swal(LanguageManager.trans('messages.error'), resp.message || LanguageManager.trans('prescriptions.prescription_not_found'), 'error');
            return;
        }
        var rx = resp.data;

        $('#view_rx_no').text(rx.prescription_no || '-');
        $('#view_rx_date').text(rx.prescription_date || '-');
        $('#view_rx_notes').text(rx.notes || '-');
        $('#view_rx_doctor').text(rx.doctor ? (rx.doctor.surname + (rx.doctor.othername || '')) : '-');
        $('#view_rx_print_btn').attr('href', '/print-prescription/' + rx.id);

        // Status badge
        var statusMap = {
            'pending': 'label-warning',
            'filled': 'label-info',
            'completed': 'label-success',
            'discontinued': 'label-default',
            'on_hold': 'label-default'
        };
        var statusClass = statusMap[rx.status] || 'label-default';
        var statusText = LanguageManager.trans('prescriptions.' + rx.status) || rx.status;
        $('#view_rx_status').html('<span class="label ' + statusClass + '">' + statusText + '</span>');

        // Invoice link
        if (rx.invoice) {
            $('#view_rx_invoice_row').show();
            $('#view_rx_invoice_no').text(rx.invoice.invoice_no || '-');
        } else {
            $('#view_rx_invoice_row').hide();
        }

        // Items
        var tbody = $('#view_rx_items');
        tbody.empty();
        var total = 0;
        if (rx.items && rx.items.length) {
            $.each(rx.items, function(i, item) {
                var name = escapeHtml(item.drug_name || (item.medical_service ? item.medical_service.name : '-'));
                var unitPrice = parseFloat(item.unit_price) || 0;
                var qty = parseInt(item.quantity) || 0;
                var lineAmount = unitPrice * qty;
                total += lineAmount;
                tbody.append(
                    '<tr>' +
                    '<td>' + (i + 1) + '</td>' +
                    '<td>' + name + '</td>' +
                    '<td>' + (item.dosage || '-') + '</td>' +
                    '<td>' + qty + '</td>' +
                    '<td>' + unitPrice.toFixed(2) + '</td>' +
                    '<td>' + lineAmount.toFixed(2) + '</td>' +
                    '<td>' + (item.frequency || '-') + '</td>' +
                    '</tr>'
                );
            });
        }
        $('#view_rx_total').text(total.toFixed(2));

        $('#viewPrescriptionModal').modal('show');
    }).fail(function() {
        $('.loading').hide();
        swal(LanguageManager.trans('messages.error'), LanguageManager.trans('messages.error_occurred'), 'error');
    });
}

// Edit prescription
function editPrescription(id) {
    loadRxServices(function() {
        $('.loading').show();
        $.get('/prescriptions/' + id + '/edit', function(rx) {
            $('.loading').hide();
            if (!rx) {
                swal(LanguageManager.trans('messages.error'), LanguageManager.trans('prescriptions.prescription_not_found'), 'error');
                return;
            }

            $('#patientPrescriptionForm')[0].reset();
            $('#patientPrescriptionForm .alert-danger').hide();
            $('#rx_edit_id').val(rx.id);
            $('#rx_doctor_id').val(rx.doctor_id);
            $('#rx_date').val(rx.prescription_date ? rx.prescription_date.slice(0, 10) : '');
            $('#rx_notes').val(rx.notes || '');
            $('#prescriptionModalTitle').text(LanguageManager.trans('prescriptions.edit_prescription'));

            // Hide settle button in edit mode if already settled
            if (rx.invoice_id) {
                $('#btn-rx-settle').hide();
            } else {
                $('#btn-rx-settle').show();
            }

            // Populate items
            $('#rxItemRows').empty();
            rxItemIndex = 0;
            if (rx.items && rx.items.length) {
                $.each(rx.items, function(i, item) {
                    addRxItemRow(item);
                });
            } else {
                addRxItemRow();
            }

            $('#addPrescriptionModal').modal('show');
        }).fail(function() {
            $('.loading').hide();
            swal(LanguageManager.trans('messages.error'), LanguageManager.trans('messages.error_occurred'), 'error');
        });
    });
}

// Settle prescription
function settlePrescription(id) {
    swal({
        title: LanguageManager.trans('prescriptions.settle'),
        text: LanguageManager.trans('prescriptions.settle') + '?',
        type: 'info',
        showCancelButton: true,
        confirmButtonText: LanguageManager.trans('prescriptions.settle'),
        cancelButtonText: LanguageManager.trans('common.cancel')
    }, function(isConfirm) {
        if (isConfirm) {
            $('.loading').show();
            $.ajax({
                url: '/prescriptions/' + id + '/settle',
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function(response) {
                    $('.loading').hide();
                    if (response.status) {
                        $('#patient_prescriptions_table').DataTable().ajax.reload();
                        swal(LanguageManager.trans('messages.success'), response.message, 'success');
                    } else {
                        swal(LanguageManager.trans('messages.error'), response.message, 'error');
                    }
                },
                error: function() {
                    $('.loading').hide();
                    swal(LanguageManager.trans('messages.error'), LanguageManager.trans('messages.error_occurred'), 'error');
                }
            });
        }
    });
}

// Delete prescription
function deletePrescription(id) {
    swal({
        title: LanguageManager.trans('messages.are_you_sure'),
        text: LanguageManager.trans('prescriptions.confirm_delete_prescription'),
        type: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: LanguageManager.trans('common.delete'),
        cancelButtonText: LanguageManager.trans('common.cancel')
    }, function(isConfirm) {
        if (isConfirm) {
            $('.loading').show();
            $.ajax({
                url: '/prescriptions/' + id,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function(response) {
                    $('.loading').hide();
                    if (response.status) {
                        $('#patient_prescriptions_table').DataTable().ajax.reload();
                        swal(LanguageManager.trans('messages.success'), response.message, 'success');
                    } else {
                        swal(LanguageManager.trans('messages.error'), response.message, 'error');
                    }
                },
                error: function() {
                    $('.loading').hide();
                    swal(LanguageManager.trans('messages.error'), LanguageManager.trans('messages.error_occurred'), 'error');
                }
            });
        }
    });
}

// Print prescription
function printPrescription(id) {
    window.open('/print-prescription/' + id, '_blank');
}
