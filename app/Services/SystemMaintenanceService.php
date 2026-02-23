<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class SystemMaintenanceService
{
    /**
     * Get list of existing backup files from the spatie backup disk.
     */
    public function getBackupList(): Collection
    {
        $backupDir = config('app.name');
        if (!$backupDir || !Storage::exists($backupDir)) {
            return collect();
        }

        $files = Storage::files($backupDir);

        return collect($files)
            ->filter(fn ($f) => str_ends_with($f, '.zip'))
            ->map(fn ($f) => [
                'name'       => basename($f),
                'path'       => $f,
                'size'       => Storage::size($f),
                'size_human' => $this->humanFileSize(Storage::size($f)),
                'date'       => Storage::lastModified($f),
                'date_human' => date('Y-m-d H:i:s', Storage::lastModified($f)),
            ])
            ->sortByDesc('date')
            ->values();
    }

    /**
     * Get retention policy configuration for display.
     */
    public function getRetentionConfig(): array
    {
        return [
            ['table' => 'medical_cases',   'column' => 'deleted_at',     'years' => 15, 'note_key' => 'system_maintenance.medical_records_note'],
            ['table' => 'audits',          'column' => 'created_at',     'years' => 15, 'note_key' => 'system_maintenance.audit_records_note'],
            ['table' => 'operation_logs',  'column' => 'operation_time', 'years' => 5,  'note_key' => 'system_maintenance.operation_logs_note'],
            ['table' => 'access_logs',     'column' => 'access_time',    'years' => 5,  'note_key' => 'system_maintenance.access_logs_note'],
            ['table' => 'login_logs',      'column' => 'login_time',     'years' => 5,  'note_key' => 'system_maintenance.login_logs_note'],
            ['table' => 'exception_logs',  'column' => 'occurred_at',    'years' => 5,  'note_key' => 'system_maintenance.exception_logs_note'],
        ];
    }

    /**
     * Validate a backup filename (no path traversal).
     */
    public function isValidFilename(string $filename): bool
    {
        return !preg_match('/[\/\\\\]|\.\./', $filename) && str_ends_with($filename, '.zip');
    }

    /**
     * Delete a backup file.
     */
    public function deleteBackup(string $filename): bool
    {
        $path = config('app.name') . '/' . $filename;
        if (!Storage::exists($path)) {
            return false;
        }
        return Storage::delete($path);
    }

    /**
     * Get the storage path for downloading a backup.
     */
    public function getBackupPath(string $filename): ?string
    {
        $path = config('app.name') . '/' . $filename;
        if (!Storage::exists($path)) {
            return null;
        }
        return Storage::path($path);
    }

    /**
     * Convert bytes to human-readable size.
     */
    private function humanFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
