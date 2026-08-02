<?php

namespace App\Http\Resources\Concerns;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * 统一 Resource 层的日期输出格式（应用时区，非 ISO-8601 UTC）。
 *
 * Resource 拿到的数据来源并不统一：
 *   - Eloquent 模型：日期属性是 Carbon，可直接 ->format()；
 *   - DB::table() 查出的 stdClass（如 MedicalServiceService::getServiceForEdit）：
 *     日期属性是字符串，直接 ->format() 会抛
 *     "Call to a member function format() on string"。
 *
 * 这里统一做一次归一化：先折算到应用时区，再按目标格式输出。
 * 折算这一步不能省——带 Z 或偏移量的字符串（如 2026-07-31T16:00:00Z）
 * 直接 format() 出来的是 UTC 墙钟时间，在东八区会早 8 小时甚至跨天。
 */
trait FormatsDates
{
    /**
     * 日期时间字段 → 'Y-m-d H:i:s'；null / 空串 → null。
     */
    protected function dateTime($value): ?string
    {
        return $this->formatDateValue($value, 'Y-m-d H:i:s');
    }

    /**
     * 纯日期字段 → 'Y-m-d'；null / 空串 → null。
     */
    protected function dateOnly($value): ?string
    {
        return $this->formatDateValue($value, 'Y-m-d');
    }

    private function formatDateValue($value, string $format): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            // Carbon::instance() 会复制一份，不能直接对传入的 Carbon 调 setTimezone()——
            // Carbon 继承自可变的 DateTime，就地改会污染模型缓存的属性。
            $date = $value instanceof DateTimeInterface
                ? Carbon::instance($value)
                : Carbon::parse($value);

            return $date->setTimezone(config('app.timezone'))->format($format);
        } catch (\Throwable $e) {
            // 脏数据原样返回，不让一个字段炸掉整个响应；但要留痕，否则问题会一直潜伏。
            Log::warning('FormatsDates 无法解析日期值', [
                'value'  => is_scalar($value) ? (string) $value : gettype($value),
                'format' => $format,
                'error'  => $e->getMessage(),
            ]);

            return is_scalar($value) ? (string) $value : null;
        }
    }
}
