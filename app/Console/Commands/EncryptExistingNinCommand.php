<?php

namespace App\Console\Commands;

use App\Patient;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

class EncryptExistingNinCommand extends Command
{
    protected $signature = 'data-security:encrypt-nin {--dry-run : Show what would be done without making changes}';

    protected $description = 'Encrypt existing plain-text NIN values and generate blind indexes';

    public function handle(): int
    {
        $key = config('data_security.nin_blind_index_key');
        if (!$key) {
            $this->error('NIN_BLIND_INDEX_KEY is not configured in .env');
            $this->line('Generate one with: php -r "echo bin2hex(random_bytes(32));"');
            return self::FAILURE;
        }

        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN — no changes will be made.');
        }

        $totalEncrypted = 0;
        $totalSkipped = 0;

        foreach (['patients' => Patient::class, 'users' => User::class] as $table => $model) {
            $this->info("Processing {$table}...");

            $model::whereNotNull('nin')
                ->where('nin', '!=', '')
                ->chunkById(100, function ($records) use ($key, $dryRun, &$totalEncrypted, &$totalSkipped) {
                    foreach ($records as $record) {
                        $rawNin = $record->getRawOriginal('nin');

                        // Skip if already encrypted (try to decrypt)
                        if ($this->isAlreadyEncrypted($rawNin)) {
                            $totalSkipped++;
                            continue;
                        }

                        if ($dryRun) {
                            $this->line("  Would encrypt NIN for {$record->getTable()} #{$record->id}");
                            $totalEncrypted++;
                            continue;
                        }

                        // Encrypt + compute blind index directly on attributes
                        // (bypass the accessor/mutator to avoid double encryption)
                        $record->forceFill([
                            'nin' => Crypt::encryptString($rawNin),
                            'nin_hash' => hash_hmac('sha256', $rawNin, config('data_security.nin_blind_index_key')),
                        ])->saveQuietly();

                        $totalEncrypted++;
                    }
                });
        }

        $this->newLine();
        $this->info("Done. Encrypted: {$totalEncrypted}, Skipped (already encrypted): {$totalSkipped}");

        return self::SUCCESS;
    }

    private function isAlreadyEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);
            return true;
        } catch (DecryptException) {
            return false;
        }
    }
}
