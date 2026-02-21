{{-- Logs Tab --}}
<div style="padding: 20px 0;">
    {{-- Sub-tabs --}}
    <ul class="nav nav-pills" id="logSubTabs">
        <li class="active">
            <a href="#subtab-operation" data-toggle="tab">{{ __('system_maintenance.operation_logs') }}</a>
        </li>
        <li>
            <a href="#subtab-access" data-toggle="tab">{{ __('system_maintenance.access_logs') }}</a>
        </li>
        <li>
            <a href="#subtab-audit" data-toggle="tab">{{ __('system_maintenance.audit_logs') }}</a>
        </li>
    </ul>

    <div class="tab-content" style="margin-top: 16px;">
        {{-- Operation Logs Sub-tab --}}
        <div class="tab-pane active" id="subtab-operation">
            {{-- Filters --}}
            <div class="row" style="margin-bottom: 16px;">
                <div class="col-md-2">
                    <label>{{ __('system_maintenance.user') }}</label>
                    <select id="op-filter-user" class="form-control input-sm">
                        <option value="">{{ __('system_maintenance.all_users') }}</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}">{{ $u->surname }} {{ $u->othername }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label>{{ __('system_maintenance.module') }}</label>
                    <select id="op-filter-module" class="form-control input-sm">
                        <option value="">{{ __('system_maintenance.all_modules') }}</option>
                        <option value="Patients">Patients</option>
                        <option value="Appointments">Appointments</option>
                        <option value="Invoices">Invoices</option>
                        <option value="Medical">Medical</option>
                        <option value="Finance">Finance</option>
                        <option value="Inventory">Inventory</option>
                        <option value="HR">HR</option>
                        <option value="Settings">Settings</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>{{ __('system_maintenance.start_date') }}</label>
                    <input type="date" id="op-filter-start" class="form-control input-sm">
                </div>
                <div class="col-md-2">
                    <label>{{ __('system_maintenance.end_date') }}</label>
                    <input type="date" id="op-filter-end" class="form-control input-sm">
                </div>
                <div class="col-md-2" style="padding-top: 24px;">
                    <button class="btn btn-sm btn-primary" onclick="operationLogsTable.draw()">
                        {{ __('system_maintenance.filter') }}
                    </button>
                    <button class="btn btn-sm btn-default" onclick="resetOpFilters()">
                        {{ __('system_maintenance.reset') }}
                    </button>
                </div>
            </div>
            <table id="operation-logs-table" class="table table-bordered table-hover" style="width: 100%;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>{{ __('system_maintenance.user') }}</th>
                        <th>{{ __('system_maintenance.operation_type') }}</th>
                        <th>{{ __('system_maintenance.module') }}</th>
                        <th>{{ __('system_maintenance.resource_type') }}</th>
                        <th>{{ __('system_maintenance.resource_id') }}</th>
                        <th>{{ __('system_maintenance.operation_time') }}</th>
                        <th>{{ __('system_maintenance.ip_address') }}</th>
                    </tr>
                </thead>
            </table>
        </div>

        {{-- Access Logs Sub-tab --}}
        <div class="tab-pane" id="subtab-access">
            {{-- Filters --}}
            <div class="row" style="margin-bottom: 16px;">
                <div class="col-md-2">
                    <label>{{ __('system_maintenance.user') }}</label>
                    <select id="acc-filter-user" class="form-control input-sm">
                        <option value="">{{ __('system_maintenance.all_users') }}</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}">{{ $u->surname }} {{ $u->othername }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label>{{ __('system_maintenance.resource_type') }}</label>
                    <select id="acc-filter-type" class="form-control input-sm">
                        <option value="">{{ __('system_maintenance.all_types') }}</option>
                        <option value="Patient">Patient</option>
                        <option value="MedicalCase">MedicalCase</option>
                        <option value="Invoice">Invoice</option>
                        <option value="Appointment">Appointment</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>{{ __('system_maintenance.start_date') }}</label>
                    <input type="date" id="acc-filter-start" class="form-control input-sm">
                </div>
                <div class="col-md-2">
                    <label>{{ __('system_maintenance.end_date') }}</label>
                    <input type="date" id="acc-filter-end" class="form-control input-sm">
                </div>
                <div class="col-md-2" style="padding-top: 24px;">
                    <button class="btn btn-sm btn-primary" onclick="accessLogsTable.draw()">
                        {{ __('system_maintenance.filter') }}
                    </button>
                    <button class="btn btn-sm btn-default" onclick="resetAccFilters()">
                        {{ __('system_maintenance.reset') }}
                    </button>
                </div>
            </div>
            <table id="access-logs-table" class="table table-bordered table-hover" style="width: 100%;">
                <thead>
                    <tr>
                        <th>{{ __('common.id') }}</th>
                        <th>{{ __('system_maintenance.user') }}</th>
                        <th>{{ __('system_maintenance.accessed_resource') }}</th>
                        <th>{{ __('system_maintenance.resource_type') }}</th>
                        <th>{{ __('system_maintenance.resource_id') }}</th>
                        <th>{{ __('system_maintenance.access_time') }}</th>
                        <th>{{ __('system_maintenance.ip_address') }}</th>
                    </tr>
                </thead>
            </table>
        </div>

        {{-- Audit Logs Sub-tab --}}
        <div class="tab-pane" id="subtab-audit">
            {{-- Filters --}}
            <div class="row" style="margin-bottom: 16px;">
                <div class="col-md-2">
                    <label>{{ __('system_maintenance.user') }}</label>
                    <select id="aud-filter-user" class="form-control input-sm">
                        <option value="">{{ __('system_maintenance.all_users') }}</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}">{{ $u->surname }} {{ $u->othername }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label>{{ __('system_maintenance.event') }}</label>
                    <select id="aud-filter-event" class="form-control input-sm">
                        <option value="">{{ __('system_maintenance.all_events') }}</option>
                        <option value="created">created</option>
                        <option value="updated">updated</option>
                        <option value="deleted">deleted</option>
                        <option value="restored">restored</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>{{ __('system_maintenance.start_date') }}</label>
                    <input type="date" id="aud-filter-start" class="form-control input-sm">
                </div>
                <div class="col-md-2">
                    <label>{{ __('system_maintenance.end_date') }}</label>
                    <input type="date" id="aud-filter-end" class="form-control input-sm">
                </div>
                <div class="col-md-2" style="padding-top: 24px;">
                    <button class="btn btn-sm btn-primary" onclick="auditLogsTable.draw()">
                        {{ __('system_maintenance.filter') }}
                    </button>
                    <button class="btn btn-sm btn-default" onclick="resetAudFilters()">
                        {{ __('system_maintenance.reset') }}
                    </button>
                </div>
            </div>
            <table id="audit-logs-table" class="table table-bordered table-hover" style="width: 100%;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>{{ __('system_maintenance.user') }}</th>
                        <th>{{ __('system_maintenance.event') }}</th>
                        <th>{{ __('system_maintenance.model_type') }}</th>
                        <th>{{ __('system_maintenance.model_id') }}</th>
                        <th>{{ __('system_maintenance.old_values') }}</th>
                        <th>{{ __('system_maintenance.new_values') }}</th>
                        <th>{{ __('system_maintenance.operation_time') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
