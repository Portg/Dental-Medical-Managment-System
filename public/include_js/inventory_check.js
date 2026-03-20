/**
 * 盘点管理 JS
 *
 * 包含：
 * - 列表页 DataTable（loadCheckTable / filterTable / clearFilters / deleteCheck）
 * - 创建页（initCheckCreate / submitCheck）
 * - 详情页（initCheckShow / saveActualQty / confirmCheck）
 *
 * PHP 值通过 window.InventoryCheckConfig 桥接（在 Blade 中设置）。
 * 翻译均通过 LanguageManager.trans() 获取。
 */

/**
 * 从 window.InventoryCheckConfig 读取 csrfToken，兼容旧全局变量。
 */
function _getCsrfToken() {
    var cfg = window.InventoryCheckConfig || {};
    return cfg.csrfToken || (typeof csrfToken !== 'undefined' ? csrfToken : '');
}

/* ============================================================
   列表页
   ============================================================ */

var checksTable = null;

/**
 * 初始化/刷新列表 DataTable。
 */
function loadCheckTable() {
    if (checksTable) {
        checksTable.destroy();
        checksTable = null;
    }

    var status     = $('#filter-status').val() || '';
    var categoryId = $('#filter-category').val() || '';

    checksTable = dataTable = $('#checks-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/inventory-checks/datatable',
            data: function (d) {
                d.status      = status;
                d.category_id = categoryId;
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'check_no',      orderable: false },
            { data: 'category_name', orderable: false },
            { data: 'check_date',    orderable: false },
            { data: 'items_count',   orderable: false },
            { data: 'status_badge',  orderable: false },
            { data: 'added_by_name', orderable: false },
            { data: 'created_at',    orderable: false },
            { data: 'actions',       orderable: false, searchable: false }
        ],
        language: { url: '/backend/assets/global/plugins/datatables/datatables/lang/zh-CN.json' },
        order: []
    });
}

function filterTable() {
    if (checksTable) {
        checksTable.destroy();
        checksTable = null;
    }
    loadCheckTable();
}

function clearFilters() {
    $('#filter-status').val('');
    $('#filter-category').val('');
    filterTable();
}

/**
 * 删除草稿盘点单。
 */
function deleteCheck(id) {
    swal({
        title: LanguageManager.trans('common.are_you_sure'),
        text: LanguageManager.trans('common.cannot_be_undone'),
        type: 'warning',
        showCancelButton: true,
        confirmButtonClass: 'btn-danger',
        confirmButtonText: LanguageManager.trans('common.delete'),
        closeOnConfirm: false
    }, function () {
        $.LoadingOverlay('show');
        $.ajax({
            type: 'DELETE',
            url: '/inventory-checks/' + id,
            data: { _token: _getCsrfToken() },
            success: function (data) {
                $.LoadingOverlay('hide');
                swal(LanguageManager.trans('common.alert'), data.message, data.status ? 'success' : 'error');
                if (data.status) {
                    setTimeout(function () { loadCheckTable(); }, 1000);
                }
            },
            error: function () {
                $.LoadingOverlay('hide');
                swal(LanguageManager.trans('common.error'), LanguageManager.trans('common.error_occurred'), 'error');
            }
        });
    });
}

/* ============================================================
   创建页
   ============================================================ */

function initCheckCreate() {
    // 初始化 select2
    if ($.fn.select2) {
        $('#category_id').select2({ placeholder: LanguageManager.trans('inventory.select_category') });
    }

    // 初始化日期选择器
    if ($.fn.datepicker) {
        $('#check_date').datepicker({
            autoclose: true,
            format: 'yyyy-mm-dd',
            language: 'zh-CN',
            todayHighlight: true
        });
    }
}

function submitCheck() {
    var categoryId = $('#category_id').val();
    var checkDate  = $('#check_date').val();
    var notes      = $('#notes').val();

    if (!categoryId) {
        swal(LanguageManager.trans('common.alert'), LanguageManager.trans('inventory.category_required'), 'warning');
        return;
    }
    if (!checkDate) {
        swal(LanguageManager.trans('common.alert'), LanguageManager.trans('inventory.check_date_required'), 'warning');
        return;
    }

    $.LoadingOverlay('show');
    $.ajax({
        type: 'POST',
        url: '/inventory-checks',
        data: {
            _token:      _getCsrfToken(),
            category_id: categoryId,
            check_date:  checkDate,
            notes:       notes
        },
        success: function (data) {
            $.LoadingOverlay('hide');
            if (data.status) {
                swal(LanguageManager.trans('common.alert'), data.message, 'success');
                setTimeout(function () { window.location.href = data.redirect; }, 1000);
            } else {
                swal(LanguageManager.trans('common.alert'), data.message, 'error');
            }
        },
        error: function () {
            $.LoadingOverlay('hide');
            swal(LanguageManager.trans('common.error'), LanguageManager.trans('common.error_occurred'), 'error');
        }
    });
}

/* ============================================================
   详情页
   ============================================================ */

function initCheckShow() {
    // actual_qty 输入框 change 事件 → 实时计算 diff_qty 并更新显示
    $(document).on('input change', '.actual-qty-input', function () {
        var $row     = $(this).closest('tr');
        var systemQty = parseFloat($row.data('system-qty')) || 0;
        var actualQty = parseFloat($(this).val());
        var $diffCell = $row.find('.diff-cell');

        if (isNaN(actualQty) || $(this).val() === '') {
            $diffCell.html('<span class="text-no-diff">-</span>');
            return;
        }

        var diff = (actualQty - systemQty).toFixed(2);
        var cls  = diff < 0 ? 'text-loss' : (diff > 0 ? 'text-surplus' : 'text-no-diff');
        var prefix = diff > 0 ? '+' : '';
        $diffCell.html('<span class="' + cls + '">' + prefix + diff + '</span>');
    });
}

/**
 * 批量保存 actual_qty（AJAX）。
 */
function saveActualQty(id) {
    var items = [];
    var valid  = true;

    $('tr[data-check-item-id]').each(function () {
        var checkItemId = $(this).data('check-item-id');
        var $input      = $(this).find('.actual-qty-input');
        if ($input.length === 0) return;  // confirmed 状态无 input

        var val = $.trim($input.val());
        if (val === '') {
            // 允许部分保存（未填写的跳过）
            return;
        }
        if (isNaN(parseFloat(val)) || parseFloat(val) < 0) {
            swal(LanguageManager.trans('common.alert'), LanguageManager.trans('inventory.actual_qty_invalid'), 'warning');
            valid = false;
            return false;
        }
        items.push({ id: checkItemId, actual_qty: parseFloat(val).toFixed(2) });
    });

    if (!valid) return;
    if (items.length === 0) {
        swal(LanguageManager.trans('common.alert'), LanguageManager.trans('inventory.no_qty_to_save'), 'warning');
        return;
    }

    $.LoadingOverlay('show');
    $.ajax({
        type: 'POST',
        url: '/inventory-checks/' + id + '/update-qty',
        data: {
            _token: csrfToken,
            items:  items
        },
        success: function (data) {
            $.LoadingOverlay('hide');
            swal(LanguageManager.trans('common.alert'), data.message, data.status ? 'success' : 'error');
        },
        error: function () {
            $.LoadingOverlay('hide');
            swal(LanguageManager.trans('common.error'), LanguageManager.trans('common.error_occurred'), 'error');
        }
    });
}

/**
 * 确认盘点单（AJAX）。
 * AG-060：若返回 needs_confirm=true，展示偏差物品列表后询问用户是否强制确认。
 */
function confirmCheck(id, forceConfirm) {
    forceConfirm = forceConfirm || 0;

    if (!forceConfirm) {
        swal({
            title: LanguageManager.trans('common.are_you_sure'),
            text: LanguageManager.trans('inventory.confirm_check_hint'),
            type: 'warning',
            showCancelButton: true,
            confirmButtonClass: 'btn-success',
            confirmButtonText: LanguageManager.trans('common.confirm'),
            closeOnConfirm: false
        }, function () {
            doConfirmCheck(id, 0);
        });
    } else {
        doConfirmCheck(id, 1);
    }
}

function doConfirmCheck(id, forceConfirm) {
    $.LoadingOverlay('show');
    $.ajax({
        type: 'POST',
        url: '/inventory-checks/' + id + '/confirm',
        data: {
            _token:        _getCsrfToken(),
            force_confirm: forceConfirm
        },
        success: function (data) {
            $.LoadingOverlay('hide');
            swal.close();

            if (data.status) {
                swal(LanguageManager.trans('common.alert'), data.message, 'success');
                setTimeout(function () { location.reload(); }, 1200);
                return;
            }

            if (data.needs_confirm && data.items && data.items.length > 0) {
                // AG-060：展示偏差物品列表，询问强制确认
                var listHtml = '<table class="table table-sm table-bordered" style="font-size:12px;margin-top:10px">'
                    + '<thead><tr><th>物品</th><th>系统库存</th><th>实际库存</th><th>差异</th><th>偏差率</th></tr></thead><tbody>';
                $.each(data.items, function (i, item) {
                    listHtml += '<tr>'
                        + '<td>' + (item.item_name || '-') + '</td>'
                        + '<td>' + item.system_qty + '</td>'
                        + '<td>' + item.actual_qty + '</td>'
                        + '<td class="' + (item.diff_qty < 0 ? 'text-danger' : 'text-success') + '">'
                        + (item.diff_qty > 0 ? '+' : '') + item.diff_qty + '</td>'
                        + '<td>' + item.deviation_rate + '%</td>'
                        + '</tr>';
                });
                listHtml += '</tbody></table>';

                swal({
                    title: LanguageManager.trans('inventory.deviation_warning_title'),
                    text: data.message,
                    type: 'warning',
                    html: true,
                    showCancelButton: true,
                    confirmButtonClass: 'btn-warning',
                    confirmButtonText: LanguageManager.trans('inventory.force_confirm'),
                    cancelButtonText: LanguageManager.trans('common.cancel'),
                    closeOnConfirm: false
                }, function () {
                    confirmCheck(id, 1);
                });

                // 将表格插入到 swal body
                setTimeout(function () {
                    $('.sweet-alert p').after(listHtml);
                }, 100);
            } else {
                swal(LanguageManager.trans('common.alert'), data.message, 'error');
            }
        },
        error: function () {
            $.LoadingOverlay('hide');
            swal.close();
            swal(LanguageManager.trans('common.error'), LanguageManager.trans('common.error_occurred'), 'error');
        }
    });
}
