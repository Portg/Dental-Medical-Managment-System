/**
 * Inventory Stock Warnings — page script
 * PHP values bridged via window.StockWarningsConfig (set in Blade).
 */
$(function () {
    var cfg = window.StockWarningsConfig || {};

    LanguageManager.loadAllFromPHP(cfg.i18n || {});

    $('#warnings-table').DataTable({
        processing: true,
        serverSide: true,
        language: LanguageManager.getDataTableLang(),
        ajax: {
            url: cfg.ajaxUrl || '',
            data: function (d) {}
        },
        columns: [
            { data: 'DT_RowIndex',         name: 'DT_RowIndex' },
            { data: 'item_code',           name: 'item_code' },
            { data: 'name',                name: 'name' },
            { data: 'category_name',       name: 'category_name' },
            { data: 'unit',                name: 'unit' },
            { data: 'current_stock',       name: 'current_stock' },
            { data: 'stock_warning_level', name: 'stock_warning_level' },
            { data: 'shortage',            name: 'shortage' }
        ]
    });
});
