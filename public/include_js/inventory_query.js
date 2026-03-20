/**
 * Inventory Query Page – 4-Tab DataTables with lazy initialization.
 * Tabs: stock-summary | batch-detail | movement-summary | movement-detail
 */
$(function () {
    // Initialize datepickers
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true
    });

    // Track which tabs have been initialized
    var initialized = {
        'tab-stock-summary': false,
        'tab-batch-detail': false,
        'tab-movement-summary': false,
        'tab-movement-detail': false
    };

    var tables = {};

    // -------------------------------------------------------
    // Lazy-load: initialize DataTable on first tab shown
    // -------------------------------------------------------
    $('#inventoryQueryTabs a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        var tabId = $(e.target).attr('href').replace('#', '');

        if (initialized[tabId]) {
            return;
        }

        switch (tabId) {
            case 'tab-stock-summary':
                initStockSummary();
                break;
            case 'tab-batch-detail':
                initBatchDetail();
                break;
            case 'tab-movement-summary':
                initMovementSummary();
                break;
            case 'tab-movement-detail':
                initMovementDetail();
                break;
        }
        initialized[tabId] = true;
    });

    // Initialize the first (active) tab immediately
    initStockSummary();
    initialized['tab-stock-summary'] = true;

    // -------------------------------------------------------
    // Tab 1: 库存汇总
    // -------------------------------------------------------
    function initStockSummary() {
        tables['stock-summary'] = $('#stock-summary-table').DataTable({
            processing: true,
            serverSide: true,
            language: LanguageManager.getDataTableLang(),
            ajax: {
                url: '/inventory-query/stock-summary',
                data: function (d) {
                    d.category_id = $('#ss-category').val();
                }
            },
            dom: 'rtip',
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'item_code', name: 'item_code'},
                {data: 'item_name_display', name: 'name'},
                {data: 'specification', name: 'specification'},
                {data: 'unit', name: 'unit'},
                {data: 'stock_level', name: 'stock_level', orderable: false, searchable: false},
                {data: 'average_cost_display', name: 'average_cost_display', orderable: false, searchable: false,
                 className: 'col-avg-cost'},
                {data: 'category_name', name: 'category_name', orderable: false, searchable: false}
            ],
            rowCallback: function (row, data) {
                if (data.DT_RowClass) {
                    $(row).addClass(data.DT_RowClass);
                }
            }
        });

        $('#ss-category').on('change', function () {
            tables['stock-summary'].ajax.reload();
        });
    }

    // -------------------------------------------------------
    // Tab 2: 批次明细
    // -------------------------------------------------------
    function initBatchDetail() {
        tables['batch-detail'] = $('#batch-detail-table').DataTable({
            processing: true,
            serverSide: true,
            language: LanguageManager.getDataTableLang(),
            ajax: {
                url: '/inventory-query/batch-detail',
                data: function (d) {
                    d.category_id    = $('#bd-category').val();
                    d.expiry_status  = $('#bd-expiry-status').val();
                }
            },
            dom: 'rtip',
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'item_name_display', name: 'item_name_display', orderable: false, searchable: false},
                {data: 'category_name', name: 'category_name', orderable: false, searchable: false},
                {data: 'batch_no', name: 'batch_no'},
                {data: 'qty_display', name: 'qty', orderable: false, searchable: false},
                {data: 'expiry_date_display', name: 'expiry_date'},
                {data: 'expiry_badge', name: 'expiry_badge', orderable: false, searchable: false},
                {data: 'created_at_display', name: 'created_at'}
            ]
        });

        $('#bd-category, #bd-expiry-status').on('change', function () {
            tables['batch-detail'].ajax.reload();
        });
    }

    // -------------------------------------------------------
    // Tab 3: 出入库查询（按物品聚合）
    // -------------------------------------------------------
    function initMovementSummary() {
        tables['movement-summary'] = $('#movement-summary-table').DataTable({
            processing: true,
            serverSide: true,
            language: LanguageManager.getDataTableLang(),
            ajax: {
                url: '/inventory-query/movement-summary',
                data: function (d) {
                    d.start_date = $('#ms-start-date').val();
                    d.end_date   = $('#ms-end-date').val();
                    d.keyword    = $('#ms-keyword').val();
                }
            },
            dom: 'rtip',
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'item_code', name: 'item_code'},
                {data: 'item_name_display', name: 'name'},
                {data: 'category_name', name: 'category_name', orderable: false, searchable: false},
                {data: 'total_stock_in_qty', name: 'total_stock_in_qty', orderable: false, searchable: false},
                {data: 'total_stock_out_qty', name: 'total_stock_out_qty', orderable: false, searchable: false},
                {data: 'net_change', name: 'net_change', orderable: false, searchable: false}
            ]
        });

        $('#ms-search-btn').on('click', function () {
            tables['movement-summary'].ajax.reload();
        });

        $('#ms-start-date, #ms-end-date').on('change', function () {
            tables['movement-summary'].ajax.reload();
        });
    }

    // -------------------------------------------------------
    // Tab 4: 出入库明细（流水）
    // -------------------------------------------------------
    function initMovementDetail() {
        tables['movement-detail'] = $('#movement-detail-table').DataTable({
            processing: true,
            serverSide: true,
            language: LanguageManager.getDataTableLang(),
            ajax: {
                url: '/inventory-query/movement-detail',
                data: function (d) {
                    d.start_date = $('#md-start-date').val();
                    d.end_date   = $('#md-end-date').val();
                    d.out_type   = $('#md-out-type').val();
                    d.status     = $('#md-status').val();
                    d.keyword    = $('#md-keyword').val();
                }
            },
            dom: 'rtip',
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'type_badge', name: 'movement_type', orderable: false, searchable: false},
                {data: 'record_no', name: 'record_no'},
                {data: 'movement_date', name: 'movement_date'},
                {data: 'item_name', name: 'item_name'},
                {data: 'qty_display', name: 'qty', orderable: false, searchable: false},
                {data: 'out_type_label', name: 'out_type_label', orderable: false, searchable: false},
                {data: 'status_badge', name: 'status', orderable: false, searchable: false}
            ]
        });

        $('#md-search-btn').on('click', function () {
            tables['movement-detail'].ajax.reload();
        });

        $('#md-start-date, #md-end-date, #md-out-type, #md-status').on('change', function () {
            tables['movement-detail'].ajax.reload();
        });
    }
});
