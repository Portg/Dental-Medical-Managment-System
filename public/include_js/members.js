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
            {data: 'phone', name: 'phone'},
            {data: 'levelBadge', name: 'levelBadge'},
            {data: 'discountDisplay', name: 'discountDisplay'},
            {data: 'balance', name: 'balance'},
            {data: 'member_points', name: 'member_points'},
            {data: 'totalConsumption', name: 'totalConsumption'},
            {data: 'member_since', name: 'member_since'},
            {data: 'expiryDate', name: 'expiryDate'},
            {data: 'statusBadge', name: 'statusBadge'},
            {data: 'viewBtn', name: 'viewBtn', orderable: false, searchable: false},
            {data: 'depositBtn', name: 'depositBtn', orderable: false, searchable: false},
            {data: 'editBtn', name: 'editBtn', orderable: false, searchable: false}
        ],
        order: [[9, 'desc']],
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
    $('#member_level_id').val('').trigger('change');
    $('#level_hints').hide();
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
            $('#edit_patient_name').val(LanguageManager.joinName(patient.surname, patient.othername));
            $('#edit_member_level_id').val(patient.member_level_id).trigger('change');
            $('#edit_member_expiry').val(patient.member_expiry);
            $('#edit_member_status').val(patient.member_status).trigger('change');
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

var currentMemberLevel = null;

function depositMember(id) {
    $.ajax({
        url: '/patients/' + id,
        type: 'GET',
        dataType: 'json',
        success: function(patient) {
            $('#deposit_member_id').val(patient.id);
            $('#deposit_member_no').val(patient.member_no);
            $('#deposit_patient_name').val(LanguageManager.joinName(patient.surname, patient.othername));
            $('#deposit_current_balance').val(parseFloat(patient.member_balance).toFixed(2));
            $('#deposit_amount').val('');
            $('#deposit_description').val('');
            $('#deposit_bonus_group').hide();
            $('#depositForm .alert').hide();

            // Load member level for bonus preview
            currentMemberLevel = null;
            if (patient.member_level_id && typeof levels !== 'undefined') {
                for (var i = 0; i < levels.length; i++) {
                    if (levels[i].id == patient.member_level_id) {
                        currentMemberLevel = levels[i];
                        break;
                    }
                }
            }

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

// ─── Refund ─────────────────────────────────────────────────────

function refundMember(id) {
    $.ajax({
        url: '/patients/' + id,
        type: 'GET',
        dataType: 'json',
        success: function(patient) {
            $('#refund_member_id').val(patient.id);
            $('#refund_member_no').val(patient.member_no);
            $('#refund_patient_name').val(LanguageManager.joinName(patient.surname, patient.othername));
            $('#refund_current_balance').val(parseFloat(patient.member_balance).toFixed(2));
            $('#refund_amount').val('');
            $('#refund_description').val('');
            $('#refundForm .alert').hide();
            $('#refundModal').modal('show');
        }
    });
}

function submitRefund() {
    var id = $('#refund_member_id').val();
    var formData = new FormData($('#refundForm')[0]);

    $.ajax({
        url: '/members/' + id + '/refund',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.status) {
                $('#refundModal').modal('hide');
                swal({
                    title: LanguageManager.trans('messages.success'),
                    text: response.message,
                    type: 'success'
                });
                reloadTable();
                if (typeof loadTransactions === 'function') {
                    loadTransactions();
                }
                if (typeof memberId !== 'undefined') {
                    setTimeout(function() { location.reload(); }, 1500);
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
            $('#refundForm .alert ul').html(errorList);
            $('#refundForm .alert').show();
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

// Show/hide payment method when initial balance is entered + bonus preview
$(document).on('input', '#initial_balance', function() {
    var balance = parseFloat($(this).val()) || 0;
    if (balance > 0) {
        $('#payment_method_group').show();
    } else {
        $('#payment_method_group').hide();
    }
    updateBonusPreview();
});

// Update hints when level is selected
$(document).on('change', '#member_level_id', function() {
    var levelId = $(this).val();
    if (!levelId || typeof levels === 'undefined') {
        $('#level_hints').hide();
        return;
    }

    var level = null;
    for (var i = 0; i < levels.length; i++) {
        if (levels[i].id == levelId) {
            level = levels[i];
            break;
        }
    }

    if (!level) {
        $('#level_hints').hide();
        return;
    }

    $('#level_hints').show();

    // Opening fee hint
    if (parseFloat(level.opening_fee) > 0) {
        $('#opening_fee_display').text(LanguageManager.trans('members.opening_fee_label') + ': ' + parseFloat(level.opening_fee).toFixed(2));
        $('#opening_fee_hint_group').show();
    } else {
        $('#opening_fee_hint_group').hide();
    }

    // Min deposit hint
    if (parseFloat(level.min_initial_deposit) > 0) {
        $('#min_deposit_display').text(LanguageManager.trans('members.min_deposit_required', { amount: parseFloat(level.min_initial_deposit).toFixed(2) }));
        $('#min_deposit_hint_group').show();
    } else {
        $('#min_deposit_hint_group').hide();
    }

    // Show/hide manual card number field
    if (typeof memberSettings !== 'undefined' && memberSettings.card_number_mode === 'manual') {
        $('#manual_card_group').show();
    } else {
        $('#manual_card_group').hide();
    }

    updateBonusPreview();
});

function updateBonusPreview() {
    var levelId = $('#member_level_id').val();
    var amount = parseFloat($('#initial_balance').val()) || 0;

    if (!levelId || amount <= 0 || typeof levels === 'undefined') {
        $('#bonus_preview_group').hide();
        return;
    }

    var level = null;
    for (var i = 0; i < levels.length; i++) {
        if (levels[i].id == levelId) {
            level = levels[i];
            break;
        }
    }

    if (!level || !level.deposit_bonus_rules || level.deposit_bonus_rules.length === 0) {
        $('#bonus_preview_group').hide();
        return;
    }

    var bonus = calculateBonus(level.deposit_bonus_rules, amount);
    if (bonus > 0) {
        var openingFee = parseFloat(level.opening_fee) || 0;
        var actual = amount - openingFee + bonus;
        var text = LanguageManager.trans('members.bonus_amount_label') + ': +' + bonus.toFixed(2) +
                   ' | ' + LanguageManager.trans('members.actual_credit') + ': ' + actual.toFixed(2);
        if (openingFee > 0) {
            text += ' ' + LanguageManager.trans('members.opening_fee_deducted', { fee: openingFee.toFixed(2) });
        }
        $('#bonus_preview_display').text(text);
        $('#bonus_preview_group').show();
    } else {
        $('#bonus_preview_group').hide();
    }
}

function calculateBonus(rules, amount) {
    if (!rules || rules.length === 0) return 0;
    var sorted = rules.slice().sort(function(a, b) { return (b.min_amount || 0) - (a.min_amount || 0); });
    for (var i = 0; i < sorted.length; i++) {
        if (amount >= (sorted[i].min_amount || 0)) {
            return parseFloat(sorted[i].bonus) || 0;
        }
    }
    return 0;
}

// Deposit bonus preview
$(document).on('input', '#deposit_amount', function() {
    updateDepositBonusPreview();
});

function updateDepositBonusPreview() {
    var amount = parseFloat($('#deposit_amount').val()) || 0;
    if (amount <= 0 || typeof currentMemberLevel === 'undefined' || !currentMemberLevel || !currentMemberLevel.deposit_bonus_rules) {
        $('#deposit_bonus_group').hide();
        return;
    }

    var bonus = calculateBonus(currentMemberLevel.deposit_bonus_rules, amount);
    if (bonus > 0) {
        $('#deposit_bonus_display').text(LanguageManager.trans('members.bonus_amount_label') + ': +' + bonus.toFixed(2));
        $('#deposit_actual_display').text(LanguageManager.trans('members.actual_credit') + ': ' + (amount + bonus).toFixed(2));
        $('#deposit_bonus_group').show();
    } else {
        $('#deposit_bonus_group').hide();
    }
}

// Initialize select2 for patient and level selection
$(document).ready(function() {
    if ($('#patient_id').length) {
        $('#patient_id').select2({
            dropdownParent: $('#memberModal'),
            placeholder: LanguageManager.trans('common.select'),
            allowClear: true,
            width: '100%'
        });
    }

    if ($('#member_level_id').length) {
        $('#member_level_id').select2({
            dropdownParent: $('#memberModal'),
            placeholder: LanguageManager.trans('common.select'),
            allowClear: true,
            width: '100%'
        });
    }

    if ($('#payment_method').length) {
        $('#payment_method').select2({
            dropdownParent: $('#memberModal'),
            minimumResultsForSearch: Infinity,
            width: '100%'
        });
    }

    // Referrer select2 with AJAX search (active members only)
    if ($('#referred_by').length) {
        $('#referred_by').select2({
            dropdownParent: $('#memberModal'),
            placeholder: LanguageManager.trans('common.select'),
            allowClear: true,
            width: '100%',
            ajax: {
                url: '/members',
                dataType: 'json',
                delay: 300,
                data: function(params) {
                    return { search: { value: params.term }, status: 'Active', length: 20 };
                },
                processResults: function(data) {
                    var results = [];
                    if (data && data.data) {
                        data.data.forEach(function(row) {
                            results.push({ id: row.id, text: row.patient_name + ' (' + row.member_no + ')' });
                        });
                    }
                    return { results: results };
                }
            }
        });
    }

    // Edit modal: level & status select2
    if ($('#edit_member_level_id').length) {
        $('#edit_member_level_id').select2({
            dropdownParent: $('#editMemberModal'),
            placeholder: LanguageManager.trans('common.select'),
            allowClear: true,
            width: '100%'
        });
    }
    if ($('#edit_member_status').length) {
        $('#edit_member_status').select2({
            dropdownParent: $('#editMemberModal'),
            minimumResultsForSearch: Infinity,
            width: '100%'
        });
    }

    // Show referrer group when referral bonus is enabled
    if (typeof memberSettings !== 'undefined' && memberSettings.referral_bonus_enabled) {
        $('#referrer_group').show();
    }
});

// ─── Points Exchange ────────────────────────────────────────────

function showExchangePoints(patientId, currentPoints) {
    var exchangeRate = (typeof memberSettings !== 'undefined' && memberSettings.points_exchange_rate)
        ? memberSettings.points_exchange_rate : 100;

    swal({
        title: LanguageManager.trans('members.exchange_points_title'),
        text: LanguageManager.trans('members.available_points') + ': ' + currentPoints +
              '\n' + LanguageManager.trans('members.exchange_rate_display', { rate: exchangeRate }),
        type: 'input',
        showCancelButton: true,
        confirmButtonText: LanguageManager.trans('members.confirm_exchange'),
        cancelButtonText: LanguageManager.trans('common.cancel'),
        inputPlaceholder: LanguageManager.trans('members.points_to_exchange'),
        closeOnConfirm: false
    }, function(inputValue) {
        if (inputValue === false) return;

        var points = parseInt(inputValue);
        if (isNaN(points) || points <= 0) {
            swal.showInputError(LanguageManager.trans('members.points_to_exchange'));
            return false;
        }

        if (points > currentPoints) {
            swal.showInputError(LanguageManager.trans('members.insufficient_points'));
            return false;
        }

        $.ajax({
            url: '/members/' + patientId + '/exchange-points',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                points: points
            },
            success: function(response) {
                if (response.status) {
                    swal({
                        title: LanguageManager.trans('messages.success'),
                        text: response.message,
                        type: 'success'
                    });
                    // Reload page to reflect new balance/points
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    swal({
                        title: LanguageManager.trans('messages.error'),
                        text: response.message,
                        type: 'error'
                    });
                }
            },
            error: function(xhr) {
                swal({
                    title: LanguageManager.trans('messages.error'),
                    text: xhr.responseJSON ? xhr.responseJSON.message : 'Error',
                    type: 'error'
                });
            }
        });
    });
}
