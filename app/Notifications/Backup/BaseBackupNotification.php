<?php

namespace App\Notifications\Backup;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Spatie\Backup\BackupDestination\BackupDestination;
use Spatie\Backup\Helpers\Format;

abstract class BaseBackupNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

    protected string $applicationName;
    protected string $diskName = '';
    protected array $destinationProperties = [];

    public function via($notifiable): array
    {
        return config('backup.notifications.notifications.' . static::class, ['mail']);
    }

    protected static function resolveApplicationName(): string
    {
        $name = config('app.name') ?? config('app.url') ?? 'Laravel';
        $env = app()->environment();

        return "{$name} ({$env})";
    }

    protected static function resolveDestinationProperties(?BackupDestination $backupDestination): array
    {
        if (! $backupDestination) {
            return [];
        }

        try {
            $backupDestination->fresh();

            $newestBackup = $backupDestination->newestBackup();
            $oldestBackup = $backupDestination->oldestBackup();
            $noBackupsText = trans('backup::notifications.no_backups_info');

            return array_filter([
                trans('backup::notifications.application_name') => static::resolveApplicationName(),
                trans('backup::notifications.backup_name') => $backupDestination->backupName(),
                trans('backup::notifications.disk') => $backupDestination->diskName(),
                trans('backup::notifications.newest_backup_size') => $newestBackup
                    ? Format::humanReadableSize($newestBackup->sizeInBytes())
                    : $noBackupsText,
                trans('backup::notifications.number_of_backups') => (string) $backupDestination->backups()->count(),
                trans('backup::notifications.total_storage_used') => Format::humanReadableSize($backupDestination->backups()->size()),
                trans('backup::notifications.newest_backup_date') => $newestBackup
                    ? $newestBackup->date()->format('Y/m/d H:i:s')
                    : $noBackupsText,
                trans('backup::notifications.oldest_backup_date') => $oldestBackup
                    ? $oldestBackup->date()->format('Y/m/d H:i:s')
                    : $noBackupsText,
            ]);
        } catch (\Throwable $e) {
            return [
                trans('backup::notifications.disk') => $backupDestination->diskName(),
            ];
        }
    }
}
