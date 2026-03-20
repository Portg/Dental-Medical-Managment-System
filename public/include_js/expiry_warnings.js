/**
 * Inventory Expiry Warnings — page script
 * PHP values bridged via window.ExpiryWarningsConfig (set in Blade).
 */
var table;

$(function () {
    var cfg = window.ExpiryWarningsConfig || {};

    LanguageManager.loadAllFromPHP(cfg.i18n || {});
    loadTable();
});

function loadTable() {
    var cfg = window.ExpiryWarningsConfig || {};
    table = $('#expiry-table').DataTable({
        processing: true,
        serverSide: true,
        language: LanguageManager.getDataTableLang(),
        ajax: {
            url: cfg.ajaxUrl || '',
            data: function (d) {
                d.warning_days = $('#warning-days').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex',   name: 'DT_RowIndex' },
            { data: 'item_code',     name: 'item_code' },
            { data: 'item_name',     name: 'item_name' },
            { data: 'category_name', name: 'category_name' },
            { data: 'batch_no',      name: 'batch_no' },
            { data: 'expiry_date',   name: 'expiry_date' },
            { data: 'days_to_expiry',name: 'days_to_expiry' },
            { data: 'qty',           name: 'qty' },
            { data: 'expiry_status', name: 'expiry_status', orderable: false, searchable: false }
        ]
    });
}

function filterTable() {
    table.ajax.reload();
}
