/**
 * Dict Items Management
 * Requires: jQuery, toastr, sweetalert, LanguageManager
 */
(function () {
    var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');

    function T(key) {
        return LanguageManager.trans(key);
    }

    // ── Type navigation switch ──
    $(document).on('click', '.dict-type-nav .nav-item', function () {
        var type = $(this).data('type');
        $('.dict-type-nav .nav-item').removeClass('active');
        $(this).addClass('active');
        $('.dict-type-panel').removeClass('active');
        $('.dict-type-panel[data-type="' + type + '"]').addClass('active');
    });

    // ── Type search filter ──
    $('#type-search').on('input', function () {
        var kw = $(this).val().toLowerCase();
        $('.dict-type-nav .nav-item').each(function () {
            var text = $(this).text().toLowerCase();
            var type = $(this).data('type').toLowerCase();
            $(this).toggle(text.indexOf(kw) !== -1 || type.indexOf(kw) !== -1);
        });

        var visibleItems = $('.dict-type-nav .nav-item:visible');
        if (visibleItems.length === 0) {
            // No match — hide right panel, show empty hint
            $('.dict-type-panel').removeClass('active');
            $('.dict-no-match').show();
        } else {
            $('.dict-no-match').hide();
            // Restore active item's panel, or select first visible
            var activeItem = $('.dict-type-nav .nav-item.active');
            if (activeItem.is(':visible')) {
                var activeType = activeItem.data('type');
                if (!$('.dict-type-panel[data-type="' + activeType + '"]').hasClass('active')) {
                    $('.dict-type-panel').removeClass('active');
                    $('.dict-type-panel[data-type="' + activeType + '"]').addClass('active');
                }
            } else {
                visibleItems.first().trigger('click');
            }
        }
    });

    // ── Inline edit ──
    $(document).on('click', '.btn-edit', function () {
        var row = $(this).closest('tr');
        row.find('.display-text').hide();
        row.find('.edit-field').show();
        row.find('.action-display').hide();
        row.find('.action-edit').show();
    });

    $(document).on('click', '.btn-cancel', function () {
        var row = $(this).closest('tr');
        row.find('.display-text').show();
        row.find('.edit-field').hide();
        row.find('.action-display').show();
        row.find('.action-edit').hide();
    });

    $(document).on('click', '.btn-save', function () {
        var row = $(this).closest('tr');
        var id = row.data('id');

        $.ajax({
            type: 'PUT',
            url: '/dict-items/' + id,
            data: {
                _token: CSRF_TOKEN,
                name: row.find('.edit-name').val(),
                sort_order: row.find('.edit-sort').val(),
                is_active: row.find('.edit-active').val()
            },
            success: function (resp) {
                if (resp.status) {
                    toastr.success(resp.message);
                    location.reload();
                } else {
                    toastr.error(resp.message);
                }
            }
        });
    });

    // ── Delete ──
    $(document).on('click', '.btn-delete', function () {
        var row = $(this).closest('tr');
        var id = row.data('id');

        swal({
            title: T('common.are_you_sure'),
            text: T('dict_items.delete_confirm'),
            type: 'warning',
            showCancelButton: true,
            confirmButtonClass: 'btn-danger',
            confirmButtonText: T('common.yes_delete_it'),
            cancelButtonText: T('common.cancel'),
            closeOnConfirm: false
        }, function () {
            $.ajax({
                type: 'DELETE',
                url: '/dict-items/' + id,
                data: { _token: CSRF_TOKEN },
                success: function (resp) {
                    if (resp.status) {
                        swal.close();
                        toastr.success(resp.message);
                        row.fadeOut(300, function () { $(this).remove(); });
                    } else {
                        toastr.error(resp.message);
                    }
                }
            });
        });
    });

    // ── Add item ──
    $(document).on('click', '.btn-add-item', function () {
        var type = $(this).data('type');
        var tbody = $(this).closest('.dict-type-panel').find('tbody');

        if (tbody.find('.add-row').length) return;

        var row = $('<tr class="add-row">' +
            '<td>-</td>' +
            '<td><input type="text" class="form-control new-code" placeholder="' + T('dict_items.code_placeholder') + '"></td>' +
            '<td><input type="text" class="form-control new-name" placeholder="' + T('dict_items.name_placeholder') + '"></td>' +
            '<td><input type="number" class="form-control new-sort" value="0"></td>' +
            '<td>-</td>' +
            '<td>' +
                '<button type="button" class="btn-action-save btn-save-new" data-type="' + type + '"><i class="fa fa-check"></i> ' + T('common.save') + '</button> ' +
                '<button type="button" class="btn-action-cancel btn-cancel-new"><i class="fa fa-times"></i></button>' +
            '</td></tr>');

        tbody.append(row);
        row.find('.new-code').focus();
    });

    $(document).on('click', '.btn-cancel-new', function () {
        $(this).closest('tr').remove();
    });

    $(document).on('click', '.btn-save-new', function () {
        var row = $(this).closest('tr');
        var type = $(this).data('type');
        var code = row.find('.new-code').val().trim();
        var name = row.find('.new-name').val().trim();
        var sort = row.find('.new-sort').val();

        if (!code || !name) {
            toastr.warning(T('dict_items.code') + ' / ' + T('dict_items.name') + ' ' + T('validation.required'));
            return;
        }

        $.ajax({
            type: 'POST',
            url: '/dict-items',
            data: { _token: CSRF_TOKEN, type: type, code: code, name: name, sort_order: sort },
            success: function (resp) {
                if (resp.status) {
                    toastr.success(resp.message);
                    location.reload();
                } else {
                    toastr.error(resp.message);
                }
            }
        });
    });

    // ── Add new type ──
    $(document).on('click', '#btn-add-type', function () {
        swal({
            title: T('dict_items.add_type'),
            text: T('dict_items.type_placeholder'),
            type: 'input',
            showCancelButton: true,
            confirmButtonText: T('common.confirm'),
            cancelButtonText: T('common.cancel'),
            closeOnConfirm: false,
            inputPlaceholder: T('dict_items.type_placeholder')
        }, function (inputValue) {
            if (inputValue === false) return false;
            inputValue = inputValue.trim();
            if (!inputValue) {
                swal.showInputError(T('dict_items.dict_type') + ' ' + T('validation.required'));
                return false;
            }
            if (!/^[a-z][a-z0-9_]*$/.test(inputValue)) {
                swal.showInputError(T('dict_items.type_placeholder'));
                return false;
            }
            $.ajax({
                type: 'POST',
                url: '/dict-items',
                data: { _token: CSRF_TOKEN, type: inputValue, code: 'default', name: inputValue },
                success: function (resp) {
                    if (resp.status) {
                        toastr.success(resp.message);
                        location.reload();
                    } else {
                        toastr.error(resp.message);
                    }
                }
            });
        });
    });
})();
