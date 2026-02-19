/**
 * DataTableManager - 列表页 DataTable + CRUD 公共管理器
 *
 * 用法：在 @section('page_js') 中初始化
 *
 *   var dtm = new DataTableManager({
 *       tableId: '#roles_table',
 *       ajaxUrl: '/roles',
 *       columns: [ {data:'DT_RowIndex', name:'DT_RowIndex'}, ... ],
 *       // 可选：弹窗 CRUD 配置
 *       modal: {
 *           formId: '#roles-form',
 *           modalId: '#roles-modal',
 *           btnId: '#btn-save',
 *           resourceUrl: '/roles',
 *       },
 *       // 可选：筛选参数
 *       filterParams: function(d) {
 *           d.start_date = $('#filter_start_date').val();
 *       },
 *       // 可选：编辑时填充表单
 *       onEditLoad: function(data) { ... },
 *       // 可选：创建弹窗打开前的额外重置
 *       onCreateReset: function() { ... },
 *   });
 *
 * 依赖：
 *   - jQuery, DataTables, SweetAlert, LoadingOverlay
 *   - LanguageManager（国际化）
 *   - list-page.blade.php 提供的全局函数（doSearch, alert_dialog, setupEmptyStateHandler）
 */
(function(window) {
    'use strict';

    /**
     * @param {Object} config
     */
    function DataTableManager(config) {
        this.config = $.extend({
            tableId: '',
            ajaxUrl: '',
            columns: [],
            dom: 'rtip',
            order: [],
            destroy: true,
            filterParams: null,   // function(d) { d.xxx = ... }
            modal: null,          // { formId, modalId, btnId, resourceUrl }
            onEditLoad: null,     // function(data) - 填充编辑表单
            onCreateReset: null,  // function() - 创建弹窗额外重置
            navigateEdit: false,  // true = 编辑跳转页面而非弹窗
            editUrl: '',          // navigateEdit 时的 URL 模板，如 '/medical-cases/{id}/edit'
            navigateCreate: false,
            createUrl: '',
        }, config);

        // i18n 标签（从 LanguageManager 获取）
        this._labels = {
            save: LanguageManager.trans('common.save'),
            update: LanguageManager.trans('common.update'),
            processing: LanguageManager.trans('common.processing') || '...',
            confirmTitle: LanguageManager.trans('common.are_you_sure'),
            confirmText: LanguageManager.trans('common.confirm_delete'),
            confirmBtn: LanguageManager.trans('common.yes_delete'),
            cancelBtn: LanguageManager.trans('common.cancel'),
        };

        this._init();
    }

    // =========================================================================
    // DataTable 初始化
    // =========================================================================

    DataTableManager.prototype._init = function() {
        var self = this;
        var cfg = this.config;

        var dtConfig = {
            processing: true,
            serverSide: true,
            language: LanguageManager.getDataTableLang(),
            destroy: cfg.destroy,
            dom: cfg.dom,
            ajax: {
                url: cfg.ajaxUrl,
                data: function(d) {
                    if (typeof cfg.filterParams === 'function') {
                        cfg.filterParams(d);
                    }
                }
            },
            columns: cfg.columns
        };

        if (cfg.order && cfg.order.length > 0) {
            dtConfig.order = cfg.order;
        }

        // 初始化 DataTable，赋值给全局变量
        dataTable = $(cfg.tableId).DataTable(dtConfig);
        setupEmptyStateHandler();

        // 覆盖全局 CRUD 占位函数
        if (cfg.modal || cfg.navigateCreate) {
            window.createRecord = function() { self.openCreate(); };
        }
        if (cfg.modal || cfg.navigateEdit) {
            window.editRecord = function(id) { self.openEdit(id); };
        }
        window.deleteRecord = function(id) { self.confirmDelete(id); };
    };

    // =========================================================================
    // 创建
    // =========================================================================

    DataTableManager.prototype.openCreate = function() {
        var cfg = this.config;

        if (cfg.navigateCreate) {
            window.location.href = cfg.createUrl;
            return;
        }

        var m = cfg.modal;
        if (!m) return;

        // 重置表单
        $(m.formId)[0].reset();
        $('#id').val('');
        $(m.btnId).attr('disabled', false).text(this._labels.save);

        // 清除验证错误
        $('.alert-danger').hide().html('');

        // 重置 select2
        $(m.formId).find('select.select2').val(null).trigger('change');

        // 页面自定义重置
        if (typeof cfg.onCreateReset === 'function') {
            cfg.onCreateReset();
        }

        $(m.modalId).modal('show');
    };

    // =========================================================================
    // 编辑
    // =========================================================================

    DataTableManager.prototype.openEdit = function(id) {
        var cfg = this.config;
        var self = this;

        if (cfg.navigateEdit) {
            window.location.href = cfg.editUrl.replace('{id}', id);
            return;
        }

        var m = cfg.modal;
        if (!m) return;

        $.LoadingOverlay("show");
        $(m.formId)[0].reset();
        $('#id').val('');
        $(m.btnId).attr('disabled', false);
        $('.alert-danger').hide().html('');

        $.ajax({
            type: 'GET',
            url: m.resourceUrl + '/' + id + '/edit',
            success: function(data) {
                $('#id').val(id);
                if (typeof cfg.onEditLoad === 'function') {
                    cfg.onEditLoad(data);
                }
                $.LoadingOverlay("hide");
                $(m.btnId).text(self._labels.update);
                $(m.modalId).modal('show');
            },
            error: function() {
                $.LoadingOverlay("hide");
            }
        });
    };

    // =========================================================================
    // 保存（创建或更新分派）
    // =========================================================================

    DataTableManager.prototype.saveOrUpdate = function() {
        var id = $('#id').val();
        if (id === '' || id === undefined) {
            this._postRecord();
        } else {
            this._putRecord();
        }
    };

    DataTableManager.prototype._postRecord = function() {
        var m = this.config.modal;
        var self = this;
        if (!m) return;

        $.LoadingOverlay("show");
        $(m.btnId).attr('disabled', true).text(self._labels.processing);
        $('.alert-danger').hide().html('');

        $.ajax({
            type: 'POST',
            data: $(m.formId).serialize(),
            url: m.resourceUrl,
            success: function(data) {
                $(m.modalId).modal('hide');
                $.LoadingOverlay("hide");
                self._handleResponse(data);
            },
            error: function(request) {
                $.LoadingOverlay("hide");
                $(m.btnId).attr('disabled', false).text(self._labels.save);
                self._handleValidationError(request);
            }
        });
    };

    DataTableManager.prototype._putRecord = function() {
        var m = this.config.modal;
        var self = this;
        if (!m) return;

        $.LoadingOverlay("show");
        $(m.btnId).attr('disabled', true).text(self._labels.processing);
        $('.alert-danger').hide().html('');

        $.ajax({
            type: 'PUT',
            data: $(m.formId).serialize(),
            url: m.resourceUrl + '/' + $('#id').val(),
            success: function(data) {
                $(m.modalId).modal('hide');
                $.LoadingOverlay("hide");
                self._handleResponse(data);
            },
            error: function(request) {
                $.LoadingOverlay("hide");
                $(m.btnId).attr('disabled', false).text(self._labels.update);
                self._handleValidationError(request);
            }
        });
    };

    // =========================================================================
    // 删除
    // =========================================================================

    DataTableManager.prototype.confirmDelete = function(id) {
        var m = this.config.modal;
        var self = this;
        var resourceUrl = m ? m.resourceUrl : this.config.ajaxUrl;

        swal({
            title: self._labels.confirmTitle,
            text: self._labels.confirmText,
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: self._labels.confirmBtn,
            cancelButtonText: self._labels.cancelBtn,
            closeOnConfirm: false
        }, function() {
            var CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
            $.LoadingOverlay("show");
            $.ajax({
                type: 'DELETE',
                data: { _token: CSRF_TOKEN },
                url: resourceUrl + '/' + id,
                success: function(data) {
                    $.LoadingOverlay("hide");
                    self._handleResponse(data);
                },
                error: function() {
                    $.LoadingOverlay("hide");
                }
            });
        });
    };

    // =========================================================================
    // 响应处理
    // =========================================================================

    DataTableManager.prototype._handleResponse = function(data) {
        if (data.status) {
            alert_dialog(data.message, "success");
        } else {
            alert_dialog(data.message, "danger");
        }
    };

    DataTableManager.prototype._handleValidationError = function(request) {
        try {
            var json = $.parseJSON(request.responseText);
            if (json.errors) {
                var $alert = $('.alert-danger');
                $alert.show().html('');
                $.each(json.errors, function(key, value) {
                    $alert.append('<p>' + value + '</p>');
                });
            } else if (json.message) {
                alert_dialog(json.message, "danger");
            }
        } catch (e) {
            // 非 JSON 响应，忽略
        }
    };

    // =========================================================================
    // 时段选择器
    // =========================================================================

    DataTableManager.prototype.initPeriodSelector = function(selectorId, startId, endId) {
        var $selector = $(selectorId);
        var $start = $(startId);
        var $end = $(endId);

        $selector.on('change', function() {
            var today = todaysDate();
            switch (this.value) {
                case 'Today':
                    $start.val(today);
                    $end.val(today);
                    break;
                case 'Yesterday':
                    var y = YesterdaysDate();
                    $start.val(y);
                    $end.val(y);
                    break;
                case 'This week':
                    $start.val(thisWeek());
                    $end.val(today);
                    break;
                case 'Last week':
                    lastWeek();
                    break;
                case 'This Month':
                    $start.val(formatDate(thisMonth()));
                    $end.val(today);
                    break;
                case 'Last Month':
                    lastMonth();
                    break;
            }
            doSearch();
        });
    };

    // =========================================================================
    // 快速搜索
    // =========================================================================

    DataTableManager.prototype.initQuickSearch = function(inputId, delay) {
        delay = delay || 300;
        $(inputId).on('keyup', debounce(function() {
            doSearch();
        }, delay));
    };

    // 导出到全局
    window.DataTableManager = DataTableManager;

})(window);
