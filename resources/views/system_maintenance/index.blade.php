@extends(\App\Http\Helper\FunctionsHelper::navigation())
@section('title', __('system_maintenance.page_title'))

@section('css')
    @include('layouts.page_loader')
    <link rel="stylesheet" href="{{ asset('css/system-maintenance.css') }}">
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
    LanguageManager.loadFromPHP(@json(__('system_maintenance')), 'system_maintenance');
    window.SystemMaintenanceConfig = {
        csrfToken:          '{{ csrf_token() }}',
        backupRunUrl:       "{{ url('system-maintenance/backup/run') }}",
        backupDownloadUrl:  "{{ url('system-maintenance/backup/download') }}",
        backupBaseUrl:      "{{ url('system-maintenance/backup') }}",
        retentionRunUrl:    "{{ url('system-maintenance/retention/run') }}",
        logsOperationsUrl:  "{{ url('system-maintenance/logs/operations') }}",
        logsAccessUrl:      "{{ url('system-maintenance/logs/access') }}",
        logsAuditsUrl:      "{{ url('system-maintenance/logs/audits') }}"
    };
</script>
<script src="{{ asset('include_js/system_maintenance_index.js') }}?v={{ filemtime(public_path('include_js/system_maintenance_index.js')) }}"></script>
@endsection
