/**
 * 申领单创建/编辑页 JS
 * PHP 值通过 window.RequisitionFormConfig 桥接（在 Blade 中设置）。
 * 遵循：Blade 无内联 script，JS 放 public/include_js/
 */

var itemRows = [];   // [{inventory_item_id, name, specification, unit, qty}]
var rowIndex = 0;

/**
 * 从 window.RequisitionFormConfig 读取，兼容旧全局变量。
 */
function _getRequisitionId() {
    var cfg = window.RequisitionFormConfig || {};
    return cfg.requisitionId || (typeof requisitionId !== 'undefined' ? requisitionId : '');
}
function _getRequisitionCsrf() {
    var cfg = window.RequisitionFormConfig || {};
    return cfg.csrfToken || (typeof csrfToken !== 'undefined' ? csrfToken : '');
}

function initRequisitionForm() {
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true
    });

    // Select2 物品搜索
    $('#add-item-select').select2({
        ajax: {
            url: '/inventory-items-search',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term };
            },
            processResults: function (data) {
                return { results: data };
            },
            cache: true
        },
        minimumInputLength: 1,
        placeholder: LanguageManager.trans('inventory.select_item')
    });

    $('#add-item-select').on('select2:select', function (e) {
        var d = e.params.data;
        $('#add-item-stock').val(d.current_stock || 0);
    });

    // 若已有数据行（编辑模式），初始化 rowIndex
    rowIndex = $('#items-tbody tr').length;
}

function addItemRow() {
    var selectEl = $('#add-item-select');
    var data = selectEl.select2('data');
    if (!data || !data.length || !data[0].id) {
        swal(LanguageManager.trans('common.alert'), LanguageManager.trans('inventory.item_required'), 'warning');
        return;
    }

    var item = data[0];
    var qty  = parseInt($('#add-item-qty').val()) || 1;
    if (qty < 1) {
        swal(LanguageManager.trans('common.alert'), LanguageManager.trans('inventory.qty_min'), 'warning');
        return;
    }

    // 检查是否已存在
    var exists = false;
    $('#items-tbody tr').each(function () {
        if ($(this).data('item-id') == item.id) {
            exists = true;
        }
    });
    if (exists) {
        swal(LanguageManager.trans('common.alert'), LanguageManager.trans('inventory.item_already_added'), 'warning');
        return;
    }

    var idx = rowIndex++;
    var tr = '<tr data-item-id="' + item.id + '">'
           + '<td>' + (idx + 1) + '</td>'
           + '<td>' + escapeHtml(item.text || item.name || '')
           + '  <input type="hidden" name="items[' + idx + '][inventory_item_id]" value="' + item.id + '">'
           + '</td>'
           + '<td>' + escapeHtml(item.specification || '-') + '</td>'
           + '<td>' + escapeHtml(item.unit || '-') + '</td>'
           + '<td><input type="number" name="items[' + idx + '][qty]" class="form-control item-qty" step="1" min="1" value="' + qty + '" style="width:80px"></td>'
           + '<td><button type="button" class="btn btn-danger btn-xs" onclick="removeRow(this)"><i class="fa fa-trash"></i></button></td>'
           + '</tr>';

    $('#items-tbody').append(tr);
    reindexRows();

    // 清空选择器
    selectEl.val(null).trigger('change');
    $('#add-item-stock').val('');
    $('#add-item-qty').val(1);
}

function removeRow(btn) {
    $(btn).closest('tr').remove();
    reindexRows();
}

function reindexRows() {
    $('#items-tbody tr').each(function (index) {
        $(this).find('td:first').text(index + 1);
        $(this).find('input[name^="items["]').each(function () {
            var name = $(this).attr('name');
            $(this).attr('name', name.replace(/items\[\d+\]/, 'items[' + index + ']'));
        });
    });
    rowIndex = $('#items-tbody tr').length;
}

function collectFormData() {
    var items = [];
    $('#items-tbody tr').each(function () {
        var itemId = $(this).find('input[name$="[inventory_item_id]"]').val();
        var qty    = parseInt($(this).find('input[name$="[qty]"]').val()) || 0;
        if (itemId && qty > 0) {
            items.push({ inventory_item_id: itemId, qty: qty });
        }
    });

    return {
        _token:          _getRequisitionCsrf(),
        stock_out_date:  $('#stock_out_date').val(),
        recipient:       $('#recipient').val(),
        department:      $('#department').val(),
        notes:           $('#notes').val(),
        items:           items
    };
}

function saveDraft() {
    var formData = collectFormData();
    if (!formData.stock_out_date) {
        swal(LanguageManager.trans('common.alert'), LanguageManager.trans('inventory.stock_out_date_required'), 'warning');
        return;
    }
    if (!formData.items.length) {
        swal(LanguageManager.trans('common.alert'), LanguageManager.trans('inventory.no_items_to_confirm'), 'warning');
        return;
    }

    $.LoadingOverlay('show');

    if (_getRequisitionId()) {
        // 更新草稿
        $.ajax({
            type: 'PUT',
            url: '/requisitions/' + _getRequisitionId(),
            data: formData,
            success: function (data) {
                $.LoadingOverlay('hide');
                swal(LanguageManager.trans('common.alert'), data.message, data.status ? 'success' : 'error');
            },
            error: function (xhr) {
                $.LoadingOverlay('hide');
                handleFormErrors(xhr);
            }
        });
    } else {
        // 新建草稿
        $.ajax({
            type: 'POST',
            url: '/requisitions',
            data: formData,
            success: function (data) {
                $.LoadingOverlay('hide');
                if (data.status && data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    swal(LanguageManager.trans('common.alert'), data.message, data.status ? 'success' : 'error');
                }
            },
            error: function (xhr) {
                $.LoadingOverlay('hide');
                handleFormErrors(xhr);
            }
        });
    }
}

function saveAndSubmit() {
    var formData = collectFormData();
    if (!formData.stock_out_date) {
        swal(LanguageManager.trans('common.alert'), LanguageManager.trans('inventory.stock_out_date_required'), 'warning');
        return;
    }
    if (!formData.items.length) {
        swal(LanguageManager.trans('common.alert'), LanguageManager.trans('inventory.no_items_to_confirm'), 'warning');
        return;
    }

    $.LoadingOverlay('show');

    var _rId     = _getRequisitionId();
    var saveUrl  = _rId ? '/requisitions/' + _rId : '/requisitions';
    var saveType = _rId ? 'PUT' : 'POST';

    $.ajax({
        type: saveType,
        url:  saveUrl,
        data: formData,
        success: function (data) {
            if (!data.status) {
                $.LoadingOverlay('hide');
                swal(LanguageManager.trans('common.alert'), data.message, 'error');
                return;
            }
            // 提取实际 ID
            var actualId = _getRequisitionId();
            if (!actualId && data.redirect) {
                var parts = data.redirect.split('/');
                actualId = parts[parts.length - 1];
            }
            // 提交审批
            $.ajax({
                type: 'POST',
                url:  '/requisitions/' + actualId + '/submit',
                data: { _token: _getRequisitionCsrf() },
                success: function (submitData) {
                    $.LoadingOverlay('hide');
                    swal(LanguageManager.trans('common.alert'), submitData.message, submitData.status ? 'success' : 'error');
                    if (submitData.status) {
                        setTimeout(function () {
                            window.location.href = '/requisitions/' + actualId;
                        }, 1200);
                    }
                },
                error: function () {
                    $.LoadingOverlay('hide');
                    swal(LanguageManager.trans('common.error'), LanguageManager.trans('common.error_occurred'), 'error');
                }
            });
        },
        error: function (xhr) {
            $.LoadingOverlay('hide');
            handleFormErrors(xhr);
        }
    });
}

function handleFormErrors(xhr) {
    if (xhr.responseJSON && xhr.responseJSON.errors) {
        var errors = xhr.responseJSON.errors;
        $('#error-list').empty();
        $.each(errors, function (key, value) {
            $('#error-list').append('<li>' + value + '</li>');
        });
        $('#error-alert').show();
    } else if (xhr.responseJSON && xhr.responseJSON.message) {
        swal(LanguageManager.trans('common.alert'), xhr.responseJSON.message, 'error');
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
