'use strict';

/* ── Global Variables ──────────────────────────────────────── */
var servicesTable = null;
var packagesTable = null;
var currentCategoryId = 0; // 0 = all

/* ── Init ────────────────────────────────────────── */
$(document).ready(function () {
    initCategoryTree();
    initServicesTable();
    initPackagesTable();
    bindCategoryButtons();
    bindServiceModal();
    bindPackageModal();
    bindBatchPriceModal();
    bindImportModal();
});

/* ── Category Tree ───────────────────────────────── */
function initCategoryTree() {
    loadCategories();
}

function loadCategories() {
    $.get('/admin/service-categories', function (res) {
        if (!res || !res.data) return;
        var $list = $('#category-list');
        // Keep the "全部" item, remove any previously added items
        $list.find('li[data-id!="0"]').remove();

        $.each(res.data, function (i, cat) {
            var $li = $('<li></li>');
            $li.attr('data-id', cat.id);
            $li.attr('data-name', cat.name);
            $li.attr('data-sort-order', cat.sort_order || 0);
            $li.attr('data-is-active', cat.is_active ? 1 : 0);

            var $a = $('<a href="#"></a>').text(cat.name);

            // Inline edit/delete icons (visible on hover via CSS)
            var $icons = $('<span class="cat-pill-actions"></span>');
            $icons.append(
                $('<i class="fa fa-pencil" title="编辑"></i>')
                    .on('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        editCategory(cat.id, cat.name, cat.sort_order || 0, cat.is_active ? 1 : 0);
                    })
            );
            $icons.append(
                $('<i class="fa fa-trash" title="删除"></i>')
                    .on('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        deleteCategory(cat.id);
                    })
            );
            $a.append($icons);
            $li.append($a);
            $list.append($li);
        });

        // Click handler — select category on pill click
        $list.off('click', 'li').on('click', 'li', function () {
            $list.find('li').removeClass('active');
            $(this).addClass('active');
            currentCategoryId = parseInt($(this).data('id')) || 0;
            if (servicesTable) {
                servicesTable.ajax.reload();
            }
        });
    });
}

/* ── Services DataTable ──────────────────────────── */
function initServicesTable() {
    servicesTable = $('#services-datatable').DataTable({
        processing: true,
        serverSide: true,
        language: LanguageManager.getDataTableLang(),
        ajax: {
            url: '/clinic-services',
            data: function (d) {
                d.category_id = currentCategoryId > 0 ? currentCategoryId : '';
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'name', name: 'name'},
            {data: 'category_name', name: 'category_name', orderable: false},
            {data: 'unit', name: 'unit', orderable: false},
            {data: 'price', name: 'price'},
            {
                data: 'is_discountable', name: 'is_discountable', orderable: false,
                render: function (val) {
                    return val
                        ? '<span class="badge badge-success label label-success">是</span>'
                        : '<span class="badge badge-default label label-default">否</span>';
                }
            },
            {
                data: 'is_favorite', name: 'is_favorite', orderable: false,
                render: function (val) {
                    return val
                        ? '<i class="fa fa-star text-warning"></i>'
                        : '<i class="fa fa-star-o text-muted"></i>';
                }
            },
            {
                data: 'is_active', name: 'is_active', orderable: false,
                render: function (val) {
                    return val
                        ? '<span class="badge badge-success label label-success">' + LanguageManager.trans('common.active') + '</span>'
                        : '<span class="badge badge-danger label label-danger">' + LanguageManager.trans('common.inactive') + '</span>';
                }
            },
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });
}

/* ── Packages DataTable ──────────────────────────── */
function initPackagesTable() {
    packagesTable = $('#packages-datatable').DataTable({
        processing: true,
        serverSide: false,
        language: LanguageManager.getDataTableLang(),
        ajax: {
            url: '/admin/service-packages',
            dataSrc: function (json) {
                return json.data || [];
            }
        },
        columns: [
            {
                data: null, orderable: false, searchable: false,
                render: function (data, type, row, meta) {
                    return meta.row + 1;
                }
            },
            {data: 'name', name: 'name'},
            {
                data: 'total_price', name: 'total_price',
                render: function (val) {
                    return parseFloat(val).toFixed(2);
                }
            },
            {
                data: 'description', name: 'description', orderable: false,
                render: function (val) {
                    return val ? val : '-';
                }
            },
            {
                data: 'is_active', name: 'is_active', orderable: false,
                render: function (val) {
                    return val
                        ? '<span class="badge badge-success label label-success">' + LanguageManager.trans('common.active') + '</span>'
                        : '<span class="badge badge-danger label label-danger">' + LanguageManager.trans('common.inactive') + '</span>';
                }
            },
            {
                data: 'id', orderable: false, searchable: false,
                render: function (id) {
                    return '<div class="btn-group">' +
                        '<button class="btn blue dropdown-toggle btn-sm" type="button" data-toggle="dropdown">' +
                            LanguageManager.trans('common.action') +
                        '</button>' +
                        '<ul class="dropdown-menu" role="menu">' +
                            '<li><a href="#" onclick="editPackage(' + id + ')">' + LanguageManager.trans('common.edit') + '</a></li>' +
                            '<li><a href="#" onclick="deletePackage(' + id + ')">' + LanguageManager.trans('common.delete') + '</a></li>' +
                        '</ul>' +
                        '</div>';
                }
            }
        ]
    });
}

/* ── Service Modal ───────────────────────────────── */
function bindServiceModal() {
    // Init Select2 for category
    $('#service-category-id').select2({
        allowClear: true,
        placeholder: '-- ' + LanguageManager.trans('clinical_services.service_categories') + ' --',
        ajax: {
            url: '/admin/service-categories',
            dataType: 'json',
            delay: 250,
            processResults: function (data) {
                var items = [{id: '', text: '-- ' + LanguageManager.trans('clinical_services.service_categories') + ' --'}];
                if (data && data.data) {
                    $.each(data.data, function (i, cat) {
                        items.push({id: cat.id, text: cat.name});
                    });
                }
                return {results: items};
            },
            cache: true
        }
    });

    // Open modal for new service
    $('#btn-add-service').on('click', function () {
        resetServiceForm();
        $('#service-modal-title').text(LanguageManager.trans('common.add'));
        $('#serviceModal').modal('show');
    });

    // Save service
    $('#btn-save-service').on('click', function () {
        var id = $('#service-id').val();
        var url = id ? '/clinic-services/' + id : '/clinic-services';
        var method = id ? 'PUT' : 'POST';

        var data = {
            name:             $('#service-name').val(),
            price:            $('#service-price').val(),
            unit:             $('#service-unit').val(),
            description:      $('#service-description').val(),
            category_id:      $('#service-category-id').val() || null,
            is_discountable:  $('#service-is-discountable').is(':checked') ? 1 : 0,
            is_favorite:      $('#service-is-favorite').is(':checked') ? 1 : 0,
            is_active:        $('#service-is-active').is(':checked') ? 1 : 0
        };

        $.ajax({
            url: url,
            type: method,
            data: data,
            success: function (res) {
                if (res.status) {
                    toastr.success(res.message);
                    $('#serviceModal').modal('hide');
                    if (servicesTable) servicesTable.ajax.reload();
                } else {
                    toastr.error(res.message || LanguageManager.trans('common.error'));
                }
            },
            error: function (xhr) {
                var msg = LanguageManager.trans('common.error');
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    else if (xhr.responseJSON.errors) {
                        var errs = xhr.responseJSON.errors;
                        msg = errs[Object.keys(errs)[0]][0];
                    }
                }
                toastr.error(msg);
            }
        });
    });
}

function resetServiceForm() {
    $('#service-id').val('');
    $('#service-name').val('');
    $('#service-price').val('');
    $('#service-unit').val('');
    $('#service-description').val('');
    $('#service-category-id').val(null).trigger('change');
    $('#service-is-discountable').prop('checked', true);
    $('#service-is-favorite').prop('checked', false);
    $('#service-is-active').prop('checked', true);
}

window.editRecord = function (id) {
    $.get('/clinic-services/' + id + '/edit', function (res) {
        if (!res) {
            toastr.error(LanguageManager.trans('common.error'));
            return;
        }
        var data = res.data || res;
        $('#service-id').val(data.id);
        $('#service-name').val(data.name);
        $('#service-price').val(data.price);
        $('#service-unit').val(data.unit);
        $('#service-description').val(data.description);
        $('#service-is-discountable').prop('checked', !!data.is_discountable);
        $('#service-is-favorite').prop('checked', !!data.is_favorite);
        $('#service-is-active').prop('checked', data.is_active === undefined ? true : !!data.is_active);

        // Set Select2 value
        if (data.category_id) {
            var catName = data.category_name || String(data.category_id);
            var option = new Option(catName, data.category_id, true, true);
            $('#service-category-id').append(option).trigger('change');
        } else {
            $('#service-category-id').val(null).trigger('change');
        }

        $('#service-modal-title').text(LanguageManager.trans('common.edit'));
        $('#serviceModal').modal('show');
    }).fail(function () {
        toastr.error(LanguageManager.trans('common.error'));
    });
};

window.deleteRecord = function (id) {
    swal({
        title: LanguageManager.trans('common.confirm_delete'),
        text: LanguageManager.trans('common.cannot_undo'),
        type: 'warning',
        showCancelButton: true,
        confirmButtonClass: 'btn-danger',
        confirmButtonText: LanguageManager.trans('common.delete'),
        cancelButtonText: LanguageManager.trans('common.cancel'),
        closeOnConfirm: false
    }, function (confirmed) {
        if (confirmed) {
            $.ajax({
                url: '/clinic-services/' + id,
                type: 'DELETE',
                success: function (res) {
                    swal.close();
                    if (res.status) {
                        toastr.success(res.message);
                        if (servicesTable) servicesTable.ajax.reload();
                    } else {
                        toastr.error(res.message || LanguageManager.trans('common.error'));
                    }
                },
                error: function () {
                    swal.close();
                    toastr.error(LanguageManager.trans('common.error'));
                }
            });
        }
    });
};

/* ── Batch Price Modal ───────────────────────────── */
function bindBatchPriceModal() {
    $('#btn-batch-price').on('click', function () {
        $('#batch-price-value').val('');
        $('input[name="batch-mode"][value="percent"]').prop('checked', true);
        $('#batch-unit-label').text('%');
        $('#batchPriceModal').modal('show');
    });

    // Toggle unit label based on mode
    $('input[name="batch-mode"]').on('change', function () {
        $('#batch-unit-label').text($(this).val() === 'percent' ? '%' : '元');
    });

    $('#btn-confirm-batch-price').on('click', function () {
        var mode = $('input[name="batch-mode"]:checked').val();
        var value = $('#batch-price-value').val();

        if (!value || isNaN(parseFloat(value))) {
            toastr.error(LanguageManager.trans('clinical_services.batch_value_required') || '请输入数值');
            return;
        }

        var data = {
            mode: mode,
            value: value,
            category_id: currentCategoryId > 0 ? currentCategoryId : ''
        };

        $.ajax({
            url: '/clinic-services/batch-update-price',
            type: 'POST',
            data: data,
            success: function (res) {
                if (res.status) {
                    toastr.success(res.message);
                    $('#batchPriceModal').modal('hide');
                    if (servicesTable) servicesTable.ajax.reload();
                } else {
                    toastr.error(res.message || LanguageManager.trans('common.error'));
                }
            },
            error: function () {
                toastr.error(LanguageManager.trans('common.error'));
            }
        });
    });
}

/* ── Import Modal ────────────────────────────────── */
function bindImportModal() {
    $('#btn-import').on('click', function () {
        $('#import-file').val('');
        $('#importModal').modal('show');
    });

    $('#btn-confirm-import').on('click', function () {
        var file = $('#import-file')[0].files[0];
        if (!file) {
            toastr.error(LanguageManager.trans('clinical_services.select_file') || '请选择文件');
            return;
        }

        var formData = new FormData($('#import-form')[0]);
        formData.set('file', file);

        $.ajax({
            url: '/clinic-services/import',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.status) {
                    toastr.success(res.message || LanguageManager.trans('common.imported_successfully') || '导入成功');
                    $('#importModal').modal('hide');
                    if (servicesTable) servicesTable.ajax.reload();
                    loadCategories();
                } else {
                    toastr.error(res.message || LanguageManager.trans('common.error'));
                }
            },
            error: function (xhr) {
                var msg = LanguageManager.trans('common.error');
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    else if (xhr.responseJSON.errors && xhr.responseJSON.errors.file) {
                        msg = xhr.responseJSON.errors.file[0];
                    }
                }
                toastr.error(msg);
            }
        });
    });
}

/* ── Package Modal ───────────────────────────────── */
function bindPackageModal() {
    $('#btn-add-package').on('click', function () {
        resetPackageForm();
        $('#package-modal-title').text(LanguageManager.trans('common.add'));
        $('#packageModal').modal('show');
    });

    $('#btn-add-package-item').on('click', function () {
        addPackageItem();
    });

    $('#btn-save-package').on('click', function () {
        var id = $('#package-id').val();
        var url = id ? '/admin/service-packages/' + id : '/admin/service-packages';
        var method = id ? 'PUT' : 'POST';

        var items = [];
        $('#package-items-body tr').each(function () {
            var $row = $(this);
            items.push({
                service_id: $row.find('.pkg-service-id').val(),
                qty:        $row.find('.pkg-qty').val(),
                price:      $row.find('.pkg-price').val()
            });
        });

        var data = {
            name:        $('#package-name').val(),
            total_price: $('#package-price').val(),
            description: $('#package-description').val(),
            items:       items
        };

        $.ajax({
            url: url,
            type: method,
            data: JSON.stringify(data),
            contentType: 'application/json',
            success: function (res) {
                if (res.status) {
                    toastr.success(res.message);
                    $('#packageModal').modal('hide');
                    if (packagesTable) packagesTable.ajax.reload();
                } else {
                    toastr.error(res.message || LanguageManager.trans('common.error'));
                }
            },
            error: function (xhr) {
                var msg = LanguageManager.trans('common.error');
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    else if (xhr.responseJSON.errors) {
                        var errs = xhr.responseJSON.errors;
                        msg = errs[Object.keys(errs)[0]][0];
                    }
                }
                toastr.error(msg);
            }
        });
    });
}

function resetPackageForm() {
    $('#package-id').val('');
    $('#package-name').val('');
    $('#package-price').val('');
    $('#package-description').val('');
    $('#package-items-body').empty();
}

window.editPackage = function (id) {
    // Fetch all packages and find the one with matching id
    $.get('/admin/service-packages', function (res) {
        if (!res || !res.data) {
            toastr.error(LanguageManager.trans('common.error'));
            return;
        }
        var pkg = null;
        $.each(res.data, function (i, p) {
            if (parseInt(p.id) === parseInt(id)) {
                pkg = p;
                return false;
            }
        });
        if (!pkg) {
            toastr.error(LanguageManager.trans('common.error'));
            return;
        }

        resetPackageForm();
        $('#package-id').val(pkg.id);
        $('#package-name').val(pkg.name);
        $('#package-price').val(pkg.total_price);
        $('#package-description').val(pkg.description || '');

        // Populate items
        if (pkg.items && pkg.items.length > 0) {
            $.each(pkg.items, function (i, item) {
                var serviceName = (item.service && item.service.name) ? item.service.name : '';
                appendPackageItemRow(item.service_id, serviceName, item.qty, item.price);
            });
        }

        $('#package-modal-title').text(LanguageManager.trans('common.edit'));
        $('#packageModal').modal('show');
    }).fail(function () {
        toastr.error(LanguageManager.trans('common.error'));
    });
};

window.deletePackage = function (id) {
    swal({
        title: LanguageManager.trans('common.confirm_delete'),
        text: LanguageManager.trans('common.cannot_undo'),
        type: 'warning',
        showCancelButton: true,
        confirmButtonClass: 'btn-danger',
        confirmButtonText: LanguageManager.trans('common.delete'),
        cancelButtonText: LanguageManager.trans('common.cancel'),
        closeOnConfirm: false
    }, function (confirmed) {
        if (confirmed) {
            $.ajax({
                url: '/admin/service-packages/' + id,
                type: 'DELETE',
                success: function (res) {
                    swal.close();
                    if (res.status) {
                        toastr.success(res.message);
                        if (packagesTable) packagesTable.ajax.reload();
                    } else {
                        toastr.error(res.message || LanguageManager.trans('common.error'));
                    }
                },
                error: function () {
                    swal.close();
                    toastr.error(LanguageManager.trans('common.error'));
                }
            });
        }
    });
};

window.addPackageItem = function () {
    appendPackageItemRow('', '', 1, '');
};

function appendPackageItemRow(serviceId, serviceName, qty, price) {
    var rowHtml =
        '<tr>' +
            '<td>' +
                '<select class="form-control pkg-service-id select2" style="width:100%;">' +
                    (serviceId
                        ? '<option value="' + serviceId + '" selected>' + $('<span>').text(serviceName).html() + '</option>'
                        : '<option value="">-- 选择项目 --</option>') +
                '</select>' +
            '</td>' +
            '<td>' +
                '<input type="number" class="form-control pkg-qty" min="1" value="' + (qty || 1) + '" style="width:70px;">' +
            '</td>' +
            '<td>' +
                '<input type="number" step="0.01" class="form-control pkg-price" min="0" value="' + (price || '') + '" style="width:100px;">' +
            '</td>' +
            '<td>' +
                '<button type="button" class="btn btn-sm btn-danger" onclick="removePackageItem(this)">' +
                    '<i class="fa fa-times"></i>' +
                '</button>' +
            '</td>' +
        '</tr>';

    var $row = $(rowHtml);

    // Init Select2 with AJAX search on the service dropdown
    $row.find('.pkg-service-id').select2({
        placeholder: '-- 选择项目 --',
        allowClear: true,
        ajax: {
            url: '/search-medical-service',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {q: params.term};
            },
            processResults: function (data) {
                // filterServices returns a plain array of {id, text, price}
                return {results: Array.isArray(data) ? data : []};
            },
            cache: true
        }
    });

    $('#package-items-body').append($row);
}

window.removePackageItem = function (btn) {
    $(btn).closest('tr').remove();
};

/* ── Category Management ─────────────────────────── */
function bindCategoryButtons() {
    $('#btn-add-category').on('click', function () {
        swal({
            title: LanguageManager.trans('common.add') + ' ' + LanguageManager.trans('clinical_services.service_categories'),
            type: 'input',
            inputPlaceholder: '分类名称',
            showCancelButton: true,
            confirmButtonText: LanguageManager.trans('common.save'),
            cancelButtonText: LanguageManager.trans('common.cancel'),
            closeOnConfirm: false,
            showLoaderOnConfirm: true
        }, function (value) {
            if (value === false) { return; }
            if (!value || !value.trim()) {
                swal.showInputError('请输入分类名称');
                return false;
            }
            $.ajax({
                url: '/admin/service-categories',
                type: 'POST',
                data: {name: value.trim()},
                success: function (res) {
                    swal.close();
                    if (res.status) {
                        toastr.success(res.message);
                        loadCategories();
                    } else {
                        toastr.error(res.message || LanguageManager.trans('common.error'));
                    }
                },
                error: function (xhr) {
                    swal.close();
                    var msg = LanguageManager.trans('common.error');
                    if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    toastr.error(msg);
                }
            });
        });
    });
}

window.editCategory = function (id, name, sortOrder, isActive) {
    swal({
        title: '编辑分类',
        type: 'input',
        inputValue: name,
        showCancelButton: true,
        confirmButtonText: LanguageManager.trans('common.save'),
        cancelButtonText: LanguageManager.trans('common.cancel'),
        closeOnConfirm: false,
        showLoaderOnConfirm: true
    }, function (value) {
        if (value === false) { return; }
        if (!value || !value.trim()) {
            swal.showInputError('请输入分类名称');
            return false;
        }
        $.ajax({
            url: '/admin/service-categories/' + id,
            type: 'PUT',
            data: {
                name:       value.trim(),
                sort_order: sortOrder,
                is_active:  isActive
            },
            success: function (res) {
                swal.close();
                if (res.status) {
                    toastr.success(res.message);
                    loadCategories();
                } else {
                    toastr.error(res.message || LanguageManager.trans('common.error'));
                }
            },
            error: function (xhr) {
                swal.close();
                var msg = LanguageManager.trans('common.error');
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                toastr.error(msg);
            }
        });
    });
};

window.deleteCategory = function (id) {
    swal({
        title: LanguageManager.trans('common.confirm_delete'),
        text: LanguageManager.trans('common.cannot_undo'),
        type: 'warning',
        showCancelButton: true,
        confirmButtonClass: 'btn-danger',
        confirmButtonText: LanguageManager.trans('common.delete'),
        cancelButtonText: LanguageManager.trans('common.cancel'),
        closeOnConfirm: false
    }, function (confirmed) {
        if (confirmed) {
            $.ajax({
                url: '/admin/service-categories/' + id,
                type: 'DELETE',
                success: function (res) {
                    swal.close();
                    if (res.status) {
                        toastr.success(res.message);
                        currentCategoryId = 0;
                        loadCategories();
                        if (servicesTable) servicesTable.ajax.reload();
                    } else {
                        toastr.error(res.message || LanguageManager.trans('common.error'));
                    }
                },
                error: function () {
                    swal.close();
                    toastr.error(LanguageManager.trans('common.error'));
                }
            });
        }
    });
};
