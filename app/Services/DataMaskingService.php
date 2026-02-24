<?php

namespace App\Services;

class DataMaskingService
{
    /**
     * Mask a phone number: 138****8000
     * Handles +86/0086 prefixes: +86 138****8000
     */
    public static function maskPhone(?string $phone): ?string
    {
        if (!$phone || mb_strlen($phone) < 7) {
            return $phone;
        }

        // Strip country code prefix, mask the local number, then re-add prefix
        $prefix = '';
        $local = $phone;

        if (str_starts_with($local, '+86')) {
            $prefix = '+86 ';
            $local = ltrim(mb_substr($local, 3), ' -');
        } elseif (str_starts_with($local, '0086')) {
            $prefix = '0086 ';
            $local = ltrim(mb_substr($local, 4), ' -');
        }

        $len = mb_strlen($local);
        if ($len < 7) {
            return $phone;
        }

        $keepStart = 3;
        $keepEnd = 4;
        $maskLen = $len - $keepStart - $keepEnd;

        if ($maskLen <= 0) {
            return $phone;
        }

        return $prefix
            . mb_substr($local, 0, $keepStart)
            . str_repeat('*', $maskLen)
            . mb_substr($local, -$keepEnd);
    }

    /**
     * Mask a NIN (National ID Number): 110101****1234
     */
    public static function maskNin(?string $nin): ?string
    {
        if (!$nin || mb_strlen($nin) < 10) {
            return $nin;
        }

        $keepStart = 6;
        $keepEnd = 4;
        $len = mb_strlen($nin);
        $maskLen = $len - $keepStart - $keepEnd;

        if ($maskLen <= 0) {
            return $nin;
        }

        return mb_substr($nin, 0, $keepStart)
            . str_repeat('*', $maskLen)
            . mb_substr($nin, -$keepEnd);
    }

    /**
     * Mask a name: 张* / 张**
     */
    public static function maskName(?string $name): ?string
    {
        if (!$name || mb_strlen($name) < 1) {
            return $name;
        }

        $first = mb_substr($name, 0, 1);
        $restLen = mb_strlen($name) - 1;

        return $first . str_repeat('*', max($restLen, 1));
    }

    /**
     * Mask an address: 北京市海淀区******
     */
    public static function maskAddress(?string $address): ?string
    {
        if (!$address || mb_strlen($address) <= 6) {
            return $address;
        }

        return mb_substr($address, 0, 6) . str_repeat('*', 6);
    }

    /**
     * Mask an email: z***@example.com
     */
    public static function maskEmail(?string $email): ?string
    {
        if (!$email || !str_contains($email, '@')) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);

        if (mb_strlen($local) <= 1) {
            return $local . '***@' . $domain;
        }

        return mb_substr($local, 0, 1) . '***@' . $domain;
    }

    /**
     * Auto-detect field type and mask accordingly.
     */
    public static function maskField(string $fieldName, ?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (!config('data_security.display_masking.enabled', true)) {
            return $value;
        }

        return match ($fieldName) {
            'phone_no', 'alternative_no', 'next_of_kin_no', 'phone_number' => static::maskPhone($value),
            'nin' => static::maskNin($value),
            'email' => static::maskEmail($value),
            'address', 'next_of_kin_address' => static::maskAddress($value),
            'surname', 'othername', 'full_name', 'next_of_kin' => static::maskName($value),
            default => $value,
        };
    }

    /**
     * Check if export masking is enabled.
     */
    public static function isExportMaskingEnabled(): bool
    {
        return (bool) config('data_security.export_masking_enabled', true);
    }
}
