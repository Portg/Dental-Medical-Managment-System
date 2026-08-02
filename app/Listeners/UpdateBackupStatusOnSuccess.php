<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Cache;
use Spatie\Backup\Events\BackupWasSuccessful;

class UpdateBackupStatusOnSuccess
{
    public function handle(BackupWasSuccessful $event): void
    {
        $newestBackup = $event->backupDestination->newestBackup();
        $filename = $newestBackup ? basename($newestBackup->path()) : null;

        $current = Cache::get('backup_status', []);

        Cache::put('backup_status', [
            'status'       => 'completed',
            'started_at'   => $current['started_at'] ?? now()->format('Y-m-d H:i:s'),
            'completed_at' => now()->format('Y-m-d H:i:s'),
            'file'         => $filename,
            'error'        => null,
        ], now()->addHours(24));
    }
}
