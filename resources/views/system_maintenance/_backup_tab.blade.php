{{-- Backup Tab --}}
<div style="padding: 20px 0;">
    <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 16px;">
        <button class="btn btn-primary" onclick="triggerBackup()">
            {{ __('system_maintenance.run_backup') }}
        </button>
        <span class="text-muted" style="font-size: 12px;">
            {{ __('system_maintenance.backup_disk_info', ['path' => config('app.name')]) }}
        </span>
    </div>

    <table class="table table-hover table-bordered">
        <thead>
            <tr>
                <th>{{ __('system_maintenance.filename') }}</th>
                <th style="width: 120px;">{{ __('system_maintenance.file_size') }}</th>
                <th style="width: 180px;">{{ __('system_maintenance.created_date') }}</th>
                <th style="width: 200px;">{{ __('common.action') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($backups as $backup)
            <tr>
                <td><i class="fa fa-file-archive-o text-muted"></i> {{ $backup['name'] }}</td>
                <td>{{ $backup['size_human'] }}</td>
                <td>{{ $backup['date_human'] }}</td>
                <td>
                    <button class="btn btn-sm btn-default" onclick="downloadBackup('{{ $backup['name'] }}')">
                        {{ __('common.download') }}
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteBackup('{{ $backup['name'] }}')">
                        {{ __('common.delete') }}
                    </button>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center text-muted" style="padding: 40px;">
                    {{ __('system_maintenance.no_backups') }}
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
