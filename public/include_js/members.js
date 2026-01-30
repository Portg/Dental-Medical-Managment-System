/**
 * Members Management JavaScript
 */

$(document).ready(function() {
    loadMembersTable();
});

function loadMembersTable() {
    dataTable = $(getTableSelector()).DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/members',
            data: function(d) {
                d.level_id = $('#filter_level').val();
                d.status = $('#filter_status').val();
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'member_no', name: 'member_no'},
            {data: 'patient_name', name: 'patient_name'},
            {data: 'levelBadge', name: 'levelBadge'},
            {data: 'balance', name: 'balance'},
            {data: 'member_points', name: 'member_points'},
            {data: 'member_since', name: 'member_since'},
            {data: 'statusBadge', name: 'statusBadge'},
            {data: 'viewBtn', name: 'viewBtn', orderable: false, searchable: false},
            {data: 'depositBtn', name: 'depositBtn', orderable: false, searchable: false},
            {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false}
        ],
        order: [[6, 'desc']],
        language: LanguageManager.getDataTableLang()
    });

    setupEmptyStateHandler();
}

function reloadTable() {
    if (dataTable) {
        dataTable.ajax.reload();
    }
}

function addMember() {
    $('#memberForm')[0].reset();
    $('#memberForm .alert').hide();
    $('#payment_method_group').hide();
    $('#patient_id').val('').trigger('change');
    $('#memberModal').modal('show');
}

function saveMember() {
    var formData = new FormData($('#memberForm')[0]);

    $.ajax({
        url: '/members',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.status) {
                $('#memberModal').modal('hide');
                swal({
                    title: LanguageManager.trans('messages.success'),
                    text: response.message,
                    type: 'success'
                });
                reloadTable();
            } else {
                swal({
                    title: LanguageManager.trans('messages.error'),
                    text: response.message,
                    type: 'error'
                });
            }
        },
        error: function(xhr) {
            var errors = xhr.responseJSON.errors;
            var errorList = '';
            $.each(errors, function(key, value) {
                errorList += '<li>' + value[0] + '</li>';
            });
            $('#memberForm .alert ul').html(errorList);
            $('#memberForm .alert').show();
        }
    });
}

function editMember(id) {
    $.ajax({
        url: '/patients/' + id,
        type: 'GET',
        dataType: 'json',
        success: function(patient) {
            $('#edit_member_id').val(patient.id);
            $('#edit_member_no').val(patient.member_no);
            $('#edit_patient_name').val(patient.surname + ' ' + patient.othername);
            $('#edit_member_level_id').val(patient.member_level_id);
            $('#edit_member_expiry').val(patient.member_expiry);
            $('#edit_member_status').val(patient.member_status);
            $('#editMemberForm .alert').hide();
            $('#editMemberModal').modal('show');
        }
    });
}

function updateMember() {
    var id = $('#edit_member_id').val();
    var formData = new FormData($('#editMemberForm')[0]);
    formData.append('_method', 'PUT');

    $.ajax({
        url: '/members/' + id,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.status) {
                $('#editMemberModal').modal('hide');
                swal({
                    title: LanguageManager.trans('messages.success'),
                    text: response.message,
                    type: 'success'
                });
                reloadTable();
            } else {
                swal({
                    title: LanguageManager.trans('messages.error'),
                    text: response.message,
                    type: 'error'
                });
            }
        },
        error: function(xhr) {
            var errors = xhr.responseJSON.errors;
            var errorList = '';
            $.each(errors, function(key, value) {
                errorList += '<li>' + value[0] + '</li>';
            });
            $('#editMemberForm .alert ul').html(errorList);
            $('#editMemberForm .alert').show();
        }
    });
}

function depositMember(id) {
    $.ajax({
        url: '/patients/' + id,
        type: 'GET',
        dataType: 'json',
        success: function(patient) {
            $('#deposit_member_id').val(patient.id);
            $('#deposit_member_no').val(patient.member_no);
            $('#deposit_patient_name').val(patient.surname + ' ' + patient.othername);
            $('#deposit_current_balance').val(parseFloat(patient.member_balance).toFixed(2));
            $('#deposit_amount').val('');
            $('#deposit_description').val('');
            $('#depositForm .alert').hide();
            $('#depositModal').modal('show');
        }
    });
}

function submitDeposit() {
    var id = $('#deposit_member_id').val();
    var formData = new FormData($('#depositForm')[0]);

    $.ajax({
        url: '/members/' + id + '/deposit',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.status) {
                $('#depositModal').modal('hide');
                swal({
                    title: LanguageManager.trans('messages.success'),
                    text: response.message,
                    type: 'success'
                });
                reloadTable();
                if (typeof loadTransactions === 'function') {
                    loadTransactions();
                }
            } else {
                swal({
                    title: LanguageManager.trans('messages.error'),
                    text: response.message,
                    type: 'error'
                });
            }
        },
        error: function(xhr) {
            var errors = xhr.responseJSON.errors;
            var errorList = '';
            $.each(errors, function(key, value) {
                errorList += '<li>' + value[0] + '</li>';
            });
            $('#depositForm .alert ul').html(errorList);
            $('#depositForm .alert').show();
        }
    });
}

// Transaction table for member detail page
function loadTransactions() {
    if (typeof memberId === 'undefined') return;

    $('#transactions_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '/members/' + memberId + '/transactions',
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'transaction_no', name: 'transaction_no'},
            {data: 'typeBadge', name: 'typeBadge'},
            {data: 'amountFormatted', name: 'amountFormatted'},
            {data: 'balance_after', name: 'balance_after'},
            {data: 'payment_method', name: 'payment_method'},
            {data: 'created_at', name: 'created_at'}
        ],
        order: [[6, 'desc']],
        language: LanguageManager.getDataTableLang()
    });
}

// Show/hide payment method when initial balance is entered
$(document).on('input', '#initial_balance', function() {
    var balance = parseFloat($(this).val()) || 0;
    if (balance > 0) {
        $('#payment_method_group').show();
    } else {
        $('#payment_method_group').hide();
    }
});

// Initialize select2 for patient selection
$(document).ready(function() {
    if ($('#patient_id').length) {
        $('#patient_id').select2({
            dropdownParent: $('#memberModal'),
            placeholder: LanguageManager.trans('common.select'),
            allowClear: true
        });
    }
});
