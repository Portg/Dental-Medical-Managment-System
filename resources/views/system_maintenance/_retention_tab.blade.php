{{-- Retention Policy Tab --}}
<div style="padding: 20px 0;">
    {{-- Retention Rules Card --}}
    <div class="portlet light bordered">
        <div class="portlet-title">
            <div class="caption font-dark">
                <span class="caption-subject">{{ __('system_maintenance.retention_policy') }}</span>
            </div>
        </div>
        <div class="portlet-body">
            <table class="table table-bordered table-condensed">
                <thead>
                    <tr>
                        <th>{{ __('system_maintenance.retention_table') }}</th>
                        <th>{{ __('system_maintenance.retention_column') }}</th>
                        <th>{{ __('system_maintenance.retention_period') }}</th>
                        <th>{{ __('system_maintenance.retention_note') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($retentionConfig as $rule)
                    <tr>
                        <td><code>{{ $rule['table'] }}</code></td>
                        <td><code>{{ $rule['column'] }}</code></td>
                        <td>
                            <span class="label {{ $rule['years'] >= 15 ? 'label-danger' : 'label-warning' }}">
                                {{ __('system_maintenance.retention_years', ['years' => $rule['years']]) }}
                            </span>
                        </td>
                        <td>{{ __($rule['note_key']) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div style="margin: 20px 0; display: flex; gap: 12px;">
        <button class="btn btn-info" onclick="triggerRetention(true)">
            {{ __('system_maintenance.dry_run_preview') }}
        </button>
        <button class="btn btn-danger" onclick="triggerRetention(false)">
            {{ __('system_maintenance.execute_retention') }}
        </button>
    </div>

    {{-- Output Area --}}
    <div class="portlet light bordered">
        <div class="portlet-title">
            <div class="caption font-dark">
                <span class="caption-subject">{{ __('system_maintenance.execution_output') }}</span>
            </div>
        </div>
        <div class="portlet-body">
            <pre id="retention-output" style="max-height: 400px; overflow-y: auto; background: #f5f5f5; padding: 16px; border-radius: 4px; font-size: 13px; white-space: pre-wrap;">{{ __('system_maintenance.no_output') }}</pre>
        </div>
    </div>
</div>
