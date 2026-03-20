<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RunBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 600;
    public int $retryAfter = 700;

    public function __construct()
    {
        $this->onQueue('backups');
    }

    public function handle(): void
    {
        $lock = Cache::lock('backup_running', 600);

        if (!$lock->get()) {
            Log::warning('RunBackupJob: another backup is already running, skipping.');
            return;
        }

        try {
            Cache::put('backup_status', [
                'status'       => 'running',
                'started_at'   => now()->toIso8601String(),
                'completed_at' => null,
                'file'         => null,
                'error'        => null,
            ], now()->addHours(24));

            $exitCode = Artisan::call('backup:run', ['--only-db' => true]);

            if ($exitCode !== 0) {
                Cache::put('backup_status', [
                    'status'       => 'failed',
                    'started_at'   => Cache::get('backup_status')['started_at'] ?? now()->toIso8601String(),
                    'completed_at' => now()->toIso8601String(),
                    'file'         => null,
                    'error'        => Artisan::output(),
                ], now()->addHours(24));
            }
        } finally {
            $lock->release();
        }
    }

    public function failed(\Throwable $exception): void
    {
        Cache::put('backup_status', [
            'status'       => 'failed',
            'started_at'   => Cache::get('backup_status')['started_at'] ?? now()->toIso8601String(),
            'completed_at' => now()->toIso8601String(),
            'file'         => null,
            'error'        => $exception->getMessage(),
        ], now()->addHours(24));

        Cache::forget('backup_running');

        Log::error('RunBackupJob failed: ' . $exception->getMessage());
    }
}
