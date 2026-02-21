@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('title', __('system_maintenance.page_title'))

@section('css')
    @include('layouts.page_loader')
    <style>
        #maintenanceTabs > li > a { font-size: 14px; }
        #maintenanceTabs > li > a > i { margin-right: 4px; }
        .tab-content { min-height: 300px; }
        #logSubTabs { margin-bottom: 0; }
        /* Truncate long JSON values in audit table */
        #audit-logs-table td:nth-child(6),
        #audit-logs-table td:nth-child(7) {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 11px;
            cursor: pointer;
        }
    </style>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="portlet light bordered">
            <div class="portlet-title">
                <div class="caption font-dark">
                    <i class="icon-wrench font-dark"></i>
                    <span class="caption-subject bold uppercase">{{ __('system_maintenance.page_title') }}</span>
                </div>
            </div>
            <div class="portlet-body">
                {{-- Bootstrap Tabs --}}
                <ul class="nav nav-tabs" id="maintenanceTabs">
                    <li class="active">
                        <a href="#tab-backup" data-toggle="tab">
                            {{ __('system_maintenance.tab_backup') }}
                        </a>
                    </li>
                    <li>
                        <a href="#tab-retention" data-toggle="tab">
                            {{ __('system_maintenance.tab_retention') }}
                        </a>
                    </li>
                    <li>
                        <a href="#tab-logs" data-toggle="tab">
                            {{ __('system_maintenance.tab_logs') }}
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    {{-- Tab 1: Database Backup --}}
                    <div class="tab-pane active" id="tab-backup">
                        @include('system_maintenance._backup_tab')
                    </div>

                    {{-- Tab 2: Data Retention --}}
                    <div class="tab-pane" id="tab-retention">
                        @include('system_maintenance._retention_tab')
                    </div>

                    {{-- Tab 3: System Logs --}}
                    <div class="tab-pane" id="tab-logs">
                        @include('system_maintenance._logs_tab')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="loading">
    <i class="fa fa-refresh fa-spin fa-2x fa-fw"></i><br/>
    <span>{{ __('common.loading') }}</span>
</div>
@endsection

@section('js')
<script src="{{ asset('backend/assets/pages/scripts/page_loader.js') }}" type="text/javascript"></script>
<script type="text/javascript">
    // ========== Backup ==========
    function triggerBackup() {
        swal({
            title: "{{ __('system_maintenance.confirm_backup') }}",
            text: "{{ __('system_maintenance.confirm_backup_desc') }}",
            type: "info",
            showCancelButton: true,
            confirmButtonText: "{{ __('system_maintenance.run_backup') }}",
            cancelButtonText: "{{ __('common.cancel') }}",
            closeOnConfirm: false
        }, function() {
            $.LoadingOverlay("show");
            $.post("{{ url('system-maintenance/backup/run') }}", {_token: '{{ csrf_token() }}'}, function(data) {
                $.LoadingOverlay("hide");
                if (data.status) {
                    swal("{{ __('common.success') }}", data.message, "success");
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    swal("{{ __('common.error') }}", data.message, "error");
                }
            }).fail(function(xhr) {
                $.LoadingOverlay("hide");
                swal("{{ __('common.error') }}", "{{ __('system_maintenance.backup_failed') }}", "error");
            });
        });
    }

    function downloadBackup(filename) {
        window.location.href = "{{ url('system-maintenance/backup/download') }}/" + encodeURIComponent(filename);
    }

    function deleteBackup(filename) {
        swal({
            title: "{{ __('common.are_you_sure') }}",
            text: "{{ __('system_maintenance.confirm_delete_backup') }}",
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-danger",
            confirmButtonText: "{{ __('common.yes_delete_it') }}",
            cancelButtonText: "{{ __('common.cancel') }}",
            closeOnConfirm: false
        }, function() {
            $.ajax({
                type: 'DELETE',
                url: "{{ url('system-maintenance/backup') }}/" + encodeURIComponent(filename),
                data: {_token: '{{ csrf_token() }}'},
                success: function(data) {
                    if (data.status) {
                        swal("{{ __('common.deleted') }}", data.message, "success");
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        swal("{{ __('common.error') }}", data.message, "error");
                    }
                }
            });
        });
    }

    // ========== Retention ==========
    function triggerRetention(dryRun) {
        var confirmTitle = dryRun
            ? "{{ __('system_maintenance.confirm_dry_run') }}"
            : "{{ __('system_maintenance.confirm_retention_execute') }}";
        swal({
            title: confirmTitle,
            type: dryRun ? "info" : "warning",
            showCancelButton: true,
            confirmButtonClass: dryRun ? "" : "btn-danger",
            confirmButtonText: "{{ __('common.confirm') }}",
            cancelButtonText: "{{ __('common.cancel') }}",
            closeOnConfirm: true
        }, function() {
            $.LoadingOverlay("show");
            $.post("{{ url('system-maintenance/retention/run') }}", {
                _token: '{{ csrf_token() }}',
                dry_run: dryRun ? 1 : 0
            }, function(data) {
                $.LoadingOverlay("hide");
                $('#retention-output').text(data.output || data.message);
            }).fail(function() {
                $.LoadingOverlay("hide");
                swal("{{ __('common.error') }}", "{{ __('system_maintenance.retention_failed') }}", "error");
            });
        });
    }

    // ========== Logs ==========
    var operationLogsTable, accessLogsTable, auditLogsTable;

    $(function() {
        // Initialize Operation Logs DataTable immediately (active tab)
        operationLogsTable = $('#operation-logs-table').DataTable({
            processing: true,
            serverSide: true,
            language: typeof LanguageManager !== 'undefined' ? LanguageManager.getDataTableLang() : {},
            ajax: {
                url: "{{ url('system-maintenance/logs/operations') }}",
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

        // Lazy-load sub-tab DataTables
        $('#logSubTabs a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
            var target = $(e.target).attr('href');
            if (target === '#subtab-access' && !accessLogsTable) {
                accessLogsTable = $('#access-logs-table').DataTable({
                    processing: true,
                    serverSide: true,
                    language: typeof LanguageManager !== 'undefined' ? LanguageManager.getDataTableLang() : {},
                    ajax: {
                        url: "{{ url('system-maintenance/logs/access') }}",
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
                        url: "{{ url('system-maintenance/logs/audits') }}",
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

        // Also lazy-load operation logs when logs main tab is shown
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
</script>
@endsection
