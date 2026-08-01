<?php

namespace App\Traits;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

trait EncryptsNin
{
    /**
     * Decrypt NIN on read. Returns raw value if decryption fails
     * (backwards-compatible with unencrypted data).
     */
    public function getNinAttribute($value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            // Not yet encrypted — return as-is
            return $value;
        }
    }

    /**
     * Encrypt NIN on write + compute blind index.
     */
    public function setNinAttribute($value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['nin'] = $value;
            $this->attributes['nin_hash'] = null;
            return;
        }

        $this->attributes['nin'] = Crypt::encryptString($value);
        $this->attributes['nin_hash'] = static::computeNinHash($value);
    }

    /**
     * Query scope: find by plain-text NIN via blind index.
     */
    public function scopeWhereNin($query, string $plainNin)
    {
        return $query->where('nin_hash', static::computeNinHash($plainNin));
    }

    /**
     * Compute HMAC-SHA256 blind index for a plain-text NIN.
     */
    public static function computeNinHash(string $plainNin): string
    {
        $key = config('data_security.nin_blind_index_key');

        if (!$key) {
            throw new \RuntimeException(
                'NIN_BLIND_INDEX_KEY is not configured. '
                . 'Generate one with: php -r "echo bin2hex(random_bytes(32));"'
            );
        }

        return hash_hmac('sha256', $plainNin, $key);
    }
}
