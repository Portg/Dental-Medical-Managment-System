'use strict';

window.ClinicAffairs = (function () {
    var config = window.clinicAffairsConfig;
    var table = null;

    function init() {
        table = $('#clinic-affairs-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: config.dataUrl,
                data: function (data) {
                    $.extend(data, currentFilters());
                }
            },
            columns: columnsForTab(),
            dom: 'rtip',
            order: [],
            language: LanguageManager.getDataTableLang()
        });

        $('#btn-apply-clinic-filter').on('click', function () { table.ajax.reload(); });
        $('#btn-reset-clinic-filter').on('click', function () {
            $('#filter-primary, #filter-secondary').val('');
            table.ajax.reload();
        });
        // 筛选一变就把导出链接的 query 同步上去（而不是拦 click 再跳转），
        // 这样右键「在新标签页打开」拿到的也是筛过的那份
        $('#filter-primary, #filter-secondary').on('change', syncExportHref);
        syncExportHref();
        $('#btn-add-clinic-record').on('click', openCreate);
        $('#clinic-record-form').on('submit', save);

    }

    /*
     * 导出必须带上当前筛选，否则页面筛了「合格」、导出来却是全部 ——
     * 用户拿到的表和屏幕上看到的对不上。
     */
    function syncExportHref() {
        var query = $.param(currentFilters());
        $('#btn-export-clinic').attr('href', config.exportUrl + (query ? '?' + query : ''));
    }

    /** 表格与导出共用同一份筛选条件，避免两边各写一套后走偏 */
    function currentFilters() {
        var primary = $('#filter-primary').val() || '';
        var secondary = $('#filter-secondary').val() || '';

        if (config.tab === 'disinfection') {
            return { check_type: primary, result: secondary };
        }
        if (config.tab === 'equipment') {
            return { category: primary, due: secondary };
        }
        return { waste_type: primary };
    }

    function columnsForTab() {
        if (config.tab === 'disinfection') {
            return [
                { data: 'DT_RowIndex', orderable: false }, { data: 'performed_at' },
                { data: 'area' }, { data: 'check_type_label' }, { data: 'result_badge', orderable: false },
                { data: 'operator_name', orderable: false }, { data: 'review_status', orderable: false },
                { data: 'action', orderable: false, searchable: false }
            ];
        }
        if (config.tab === 'equipment') {
            return [
                { data: 'DT_RowIndex', orderable: false }, { data: 'performed_at' },
                { data: 'equipment_code' }, { data: 'equipment_name' }, { data: 'category_label' },
                { data: 'maintenance_type_label' }, { data: 'result_badge', orderable: false },
                { data: 'due_badge', orderable: false }, { data: 'action', orderable: false, searchable: false }
            ];
        }
        return [
            { data: 'DT_RowIndex', orderable: false }, { data: 'handed_over_at' },
            { data: 'waste_type_label' }, { data: 'weight_kg' }, { data: 'package_count' },
            { data: 'handler_name', orderable: false }, { data: 'receiver_name' }, { data: 'manifest_no', defaultContent: '-' },
            { data: 'action', orderable: false, searchable: false }
        ];
    }

    function openCreate() {
        var form = document.getElementById('clinic-record-form');
        if (!form) return;
        form.reset();
        $('#clinic-record-id').val('');
        setModalTitle('add_record');
        // form.reset() 清不掉组合控件的 hidden，得手动清一遍
        DateTimePicker.clear(form);
        // 时间基准取服务端渲染时刻，别用 new Date()：客户端时区/时钟未必和诊所一致
        DateTimePicker.set(form, dateFieldName(), window._serverNow ? window._serverNow.datetime : '');
        $('#clinicRecordModal').modal('show');
    }

    function dateFieldName() {
        return config.tab === 'waste' ? 'handed_over_at' : 'performed_at';
    }

    function setModalTitle(key) {
        $('#clinicRecordModal .modal-title').text(LanguageManager.trans('clinic_affairs.' + key));
    }

    function edit(type, id) {
        var row = findRow(id);
        if (!row) return;
        var form = document.getElementById('clinic-record-form');
        form.reset();
        DateTimePicker.clear(form);
        $('#clinic-record-id').val(id);
        setModalTitle('edit_record');
        Object.keys(row).forEach(function (key) {
            if (row[key] === null) return;
            var value = row[key];
            // 日期时间由组合控件接管：它没有同名的可见 input，form.elements 拿不到
            if (key === 'performed_at' || key === 'handed_over_at') {
                DateTimePicker.set(form, key, value);
                return;
            }
            var field = form.elements[key];
            if (!field) return;
            if (key === 'next_due_at' && typeof value === 'string') value = value.slice(0, 10);
            $(field).val(value);
        });
        $('#clinicRecordModal').modal('show');
    }

    function save(event) {
        event.preventDefault();
        var id = $('#clinic-record-id').val();
        DateTimePicker.sync(document.getElementById('clinic-record-form'));
        $.ajax({
            url: id ? config.storeUrl + '/' + id : config.storeUrl,
            method: id ? 'PUT' : 'POST',
            data: $('#clinic-record-form').serialize(),
            success: function (response) {
                if (response.status) {
                    $('#clinicRecordModal').modal('hide');
                    table.ajax.reload(null, false);
                    updateStats(response.stats);
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                }
            },
            error: showAjaxError
        });
    }

    function remove(type, id) {
        swal({
            title: LanguageManager.trans('clinic_affairs.confirm_delete'),
            type: 'warning',
            showCancelButton: true,
            confirmButtonClass: 'btn-danger',
            confirmButtonText: LanguageManager.trans('clinic_affairs.confirm'),
            cancelButtonText: LanguageManager.trans('clinic_affairs.cancel'),
            closeOnConfirm: false
        }, function () {
            $.ajax({
                url: config.deleteBase + '/' + type + '/' + id,
                method: 'DELETE',
                success: function (response) {
                    swal.close();
                    if (response.status) {
                        table.ajax.reload(null, false);
                        updateStats(response.stats);
                        toastr.success(response.message);
                    } else toastr.error(response.message);
                },
                error: showAjaxError
            });
        });
    }

    function review(id) {
        swal({
            title: LanguageManager.trans('clinic_affairs.confirm_review'),
            type: 'warning',
            showCancelButton: true,
            confirmButtonText: LanguageManager.trans('clinic_affairs.confirm'),
            cancelButtonText: LanguageManager.trans('clinic_affairs.cancel'),
            closeOnConfirm: false
        }, function () {
            $.post(config.reviewBase + '/' + id + '/review')
                .done(function (response) {
                    swal.close();
                    table.ajax.reload(null, false);
                    updateStats(response.stats);
                    toastr.success(response.message);
                })
                .fail(function (xhr) {
                    swal.close();
                    showAjaxError(xhr);
                });
        });
    }

    function findRow(id) {
        var rows = table.rows().data().toArray();
        for (var i = 0; i < rows.length; i += 1) {
            if (Number(rows[i].id) === Number(id)) return rows[i];
        }
        return null;
    }

    function showAjaxError(xhr) {
        var message = xhr.responseJSON && (xhr.responseJSON.message || firstError(xhr.responseJSON.errors));
        toastr.error(message || LanguageManager.trans('messages.error_occurred'));
    }

    function firstError(errors) {
        if (!errors) return null;
        var keys = Object.keys(errors);
        return keys.length ? errors[keys[0]][0] : null;
    }

    function updateStats(stats) {
        if (!stats) return;
        Object.keys(stats).forEach(function (key) {
            var value = stats[key];
            if (key === 'monthly_waste_kg') value = Number(value || 0).toFixed(2) + ' kg';
            $('[data-clinic-stat="' + key + '"]').text(value);
        });
    }

    $(init);

    return { edit: edit, remove: remove, review: review };
})();
