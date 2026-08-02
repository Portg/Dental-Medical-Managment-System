<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Cache;
use Spatie\Backup\Events\BackupHasFailed;

class UpdateBackupStatusOnFailure
{
    public function handle(BackupHasFailed $event): void
    {
        $current = Cache::get('backup_status', []);

        Cache::put('backup_status', [
            'status'       => 'failed',
            'started_at'   => $current['started_at'] ?? now()->format('Y-m-d H:i:s'),
            'completed_at' => now()->format('Y-m-d H:i:s'),
            'file'         => null,
            'error'        => $event->exception->getMessage(),
        ], now()->addHours(24));
    }
}
