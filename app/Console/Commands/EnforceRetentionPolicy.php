<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EnforceRetentionPolicy extends Command
{
    protected $signature = 'retention:enforce {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Enforce data retention policy: 15 years for medical records/audits, 5 years for operation/access logs';

    // Retention periods in years
    const MEDICAL_RETENTION_YEARS = 15;
    const AUDIT_RETENTION_YEARS = 15;
    const LOG_RETENTION_YEARS = 5;

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN - No data will be deleted.');
        }

        $this->info('Starting data retention policy enforcement...');

        $summary = [];

        // 1. Medical records (soft-deleted older than 15 years)
        $summary['medical_cases'] = $this->cleanTable(
            'medical_cases',
            'deleted_at',
            self::MEDICAL_RETENTION_YEARS,
            $dryRun,
            true
        );

        // 2. Audit logs (older than 15 years)
        $summary['audits'] = $this->cleanTable(
            'audits',
            'created_at',
            self::AUDIT_RETENTION_YEARS,
            $dryRun
        );

        // 3. Operation logs (older than 5 years)
        $summary['operation_logs'] = $this->cleanTable(
            'operation_logs',
            'operation_time',
            self::LOG_RETENTION_YEARS,
            $dryRun
        );

        // 4. Access logs (older than 5 years)
        $summary['access_logs'] = $this->cleanTable(
            'access_logs',
            'access_time',
            self::LOG_RETENTION_YEARS,
            $dryRun
        );

        // 5. Login logs (older than 5 years)
        $summary['login_logs'] = $this->cleanTable(
            'login_logs',
            'login_time',
            self::LOG_RETENTION_YEARS,
            $dryRun
        );

        // 6. Exception logs (older than 5 years)
        $summary['exception_logs'] = $this->cleanTable(
            'exception_logs',
            'occurred_at',
            self::LOG_RETENTION_YEARS,
            $dryRun
        );

        // Summary
        $this->info('');
        $this->info('=== Retention Policy Summary ===');
        foreach ($summary as $table => $count) {
            $action = $dryRun ? 'would be deleted' : 'deleted';
            $this->line("  {$table}: {$count} records {$action}");
        }

        Log::info('Retention policy enforcement completed', [
            'dry_run' => $dryRun,
            'summary' => $summary,
        ]);

        $this->info('Done.');
        return 0;
    }

    private function cleanTable(string $table, string $dateColumn, int $retentionYears, bool $dryRun, bool $onlySoftDeleted = false): int
    {
        $cutoff = now()->subYears($retentionYears);

        $query = DB::table($table)->where($dateColumn, '<', $cutoff);

        if ($onlySoftDeleted) {
            $query->whereNotNull('deleted_at');
        }

        $count = $query->count();

        if ($count > 0 && !$dryRun) {
            // Delete in batches to avoid memory issues
            $deleted = 0;
            do {
                $batch = DB::table($table)
                    ->where($dateColumn, '<', $cutoff);

                if ($onlySoftDeleted) {
                    $batch->whereNotNull('deleted_at');
                }

                $batchDeleted = $batch->limit(1000)->delete();
                $deleted += $batchDeleted;
            } while ($batchDeleted > 0);

            $this->line("  [{$table}] Permanently deleted {$deleted} records older than {$retentionYears} years");
            return $deleted;
        }

        if ($count > 0) {
            $this->line("  [{$table}] Found {$count} records older than {$retentionYears} years");
        }

        return $count;
    }
}
