// ========== Backup ==========
function triggerBackup() {
    swal({
        title: LanguageManager.trans('system_maintenance.confirm_backup'),
        text: LanguageManager.trans('system_maintenance.confirm_backup_desc'),
        type: "info",
        showCancelButton: true,
        confirmButtonText: LanguageManager.trans('system_maintenance.run_backup'),
        cancelButtonText: LanguageManager.trans('common.cancel'),
        closeOnConfirm: false
    }, function() {
        $.LoadingOverlay("show");
        $.post(window.SystemMaintenanceConfig.backupRunUrl, {_token: window.SystemMaintenanceConfig.csrfToken}, function(data) {
            $.LoadingOverlay("hide");
            if (data.status) {
                swal(LanguageManager.trans('common.success'), data.message, "success");
                setTimeout(function() { location.reload(); }, 1500);
            } else {
                swal(LanguageManager.trans('common.error'), data.message, "error");
            }
        }).fail(function(xhr) {
            $.LoadingOverlay("hide");
            swal(LanguageManager.trans('common.error'), LanguageManager.trans('system_maintenance.backup_failed'), "error");
        });
    });
}

function downloadBackup(filename) {
    window.location.href = window.SystemMaintenanceConfig.backupDownloadUrl + '/' + encodeURIComponent(filename);
}

function deleteBackup(filename) {
    swal({
        title: LanguageManager.trans('common.are_you_sure'),
        text: LanguageManager.trans('system_maintenance.confirm_delete_backup'),
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn-danger",
        confirmButtonText: LanguageManager.trans('common.yes_delete_it'),
        cancelButtonText: LanguageManager.trans('common.cancel'),
        closeOnConfirm: false
    }, function() {
        $.ajax({
            type: 'DELETE',
            url: window.SystemMaintenanceConfig.backupBaseUrl + '/' + encodeURIComponent(filename),
            data: {_token: window.SystemMaintenanceConfig.csrfToken},
            success: function(data) {
                if (data.status) {
                    swal(LanguageManager.trans('common.deleted'), data.message, "success");
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    swal(LanguageManager.trans('common.error'), data.message, "error");
                }
            }
        });
    });
}

// ========== Retention ==========
function triggerRetention(dryRun) {
    var confirmTitle = dryRun
        ? LanguageManager.trans('system_maintenance.confirm_dry_run')
        : LanguageManager.trans('system_maintenance.confirm_retention_execute');
    swal({
        title: confirmTitle,
        type: dryRun ? "info" : "warning",
        showCancelButton: true,
        confirmButtonClass: dryRun ? "" : "btn-danger",
        confirmButtonText: LanguageManager.trans('common.confirm'),
        cancelButtonText: LanguageManager.trans('common.cancel'),
        closeOnConfirm: true
    }, function() {
        $.LoadingOverlay("show");
        $.post(window.SystemMaintenanceConfig.retentionRunUrl, {
            _token: window.SystemMaintenanceConfig.csrfToken,
            dry_run: dryRun ? 1 : 0
        }, function(data) {
            $.LoadingOverlay("hide");
            $('#retention-output').text(data.output || data.message);
        }).fail(function() {
            $.LoadingOverlay("hide");
            swal(LanguageManager.trans('common.error'), LanguageManager.trans('system_maintenance.retention_failed'), "error");
        });
    });
}

// ========== Logs ==========
var operationLogsTable, accessLogsTable, auditLogsTable;

$(function() {
    operationLogsTable = $('#operation-logs-table').DataTable({
        processing: true,
        serverSide: true,
        language: typeof LanguageManager !== 'undefined' ? LanguageManager.getDataTableLang() : {},
        ajax: {
            url: window.SystemMaintenanceConfig.logsOperationsUrl,
            data: function(d) {
                d.user_id = $('#op-filter-user').val();
                d.module = $('#op-filter-module').val();
                d.start_date = $('#op-filter-start').val();
                d.end_date = $('#op-filter-end').val();
            }
        },
        dom: 'rtip',
        pageLength: 20,
        columns: [
            {data: 'id', name: 'id'},
            {data: 'user_name', name: 'user_name', orderable: false},
            {data: 'operation_type', name: 'operation_type'},
            {data: 'module', name: 'module'},
            {data: 'resource_type', name: 'resource_type'},
            {data: 'resource_id', name: 'resource_id'},
            {data: 'operation_time', name: 'operation_time'},
            {data: 'ip_address', name: 'ip_address'}
        ],
        order: [[6, 'desc']]
    });

    $('#logSubTabs a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
        var target = $(e.target).attr('href');
        if (target === '#subtab-access' && !accessLogsTable) {
            accessLogsTable = $('#access-logs-table').DataTable({
                processing: true,
                serverSide: true,
                language: typeof LanguageManager !== 'undefined' ? LanguageManager.getDataTableLang() : {},
                ajax: {
                    url: window.SystemMaintenanceConfig.logsAccessUrl,
                    data: function(d) {
                        d.user_id = $('#acc-filter-user').val();
                        d.resource_type = $('#acc-filter-type').val();
                        d.start_date = $('#acc-filter-start').val();
                        d.end_date = $('#acc-filter-end').val();
                    }
                },
                dom: 'rtip',
                pageLength: 20,
                columns: [
                    {data: 'id', name: 'id'},
                    {data: 'user_name', name: 'user_name', orderable: false},
                    {data: 'accessed_resource', name: 'accessed_resource'},
                    {data: 'resource_type', name: 'resource_type'},
                    {data: 'resource_id', name: 'resource_id'},
                    {data: 'access_time', name: 'access_time'},
                    {data: 'ip_address', name: 'ip_address'}
                ],
                order: [[5, 'desc']]
            });
        }
        if (target === '#subtab-audit' && !auditLogsTable) {
            auditLogsTable = $('#audit-logs-table').DataTable({
                processing: true,
                serverSide: true,
                language: typeof LanguageManager !== 'undefined' ? LanguageManager.getDataTableLang() : {},
                ajax: {
                    url: window.SystemMaintenanceConfig.logsAuditsUrl,
                    data: function(d) {
                        d.user_id = $('#aud-filter-user').val();
                        d.event = $('#aud-filter-event').val();
                        d.start_date = $('#aud-filter-start').val();
                        d.end_date = $('#aud-filter-end').val();
                    }
                },
                dom: 'rtip',
                pageLength: 20,
                columns: [
                    {data: 'id', name: 'id'},
                    {data: 'user_name', name: 'user_name', orderable: false},
                    {data: 'event', name: 'event'},
                    {data: 'auditable_type', name: 'auditable_type'},
                    {data: 'auditable_id', name: 'auditable_id'},
                    {data: 'old_values', name: 'old_values', orderable: false},
                    {data: 'new_values', name: 'new_values', orderable: false},
                    {data: 'created_at', name: 'created_at'}
                ],
                order: [[7, 'desc']]
            });
        }
    });

    $('#maintenanceTabs a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
        var target = $(e.target).attr('href');
        if (target === '#tab-logs' && operationLogsTable) {
            operationLogsTable.columns.adjust();
        }
    });
});

// Filter reset helpers
function resetOpFilters() {
    $('#op-filter-user, #op-filter-module').val('');
    $('#op-filter-start, #op-filter-end').val('');
    operationLogsTable.draw();
}
function resetAccFilters() {
    $('#acc-filter-user, #acc-filter-type').val('');
    $('#acc-filter-start, #acc-filter-end').val('');
    if (accessLogsTable) accessLogsTable.draw();
}
function resetAudFilters() {
    $('#aud-filter-user, #aud-filter-event').val('');
    $('#aud-filter-start, #aud-filter-end').val('');
    if (auditLogsTable) auditLogsTable.draw();
}
