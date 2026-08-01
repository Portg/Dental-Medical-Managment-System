<?php

namespace App\Concerns;

use DateTimeInterface;

/**
 * 让模型的日期属性以应用时区、'Y-m-d H:i:s' 格式序列化。
 *
 * Eloquent 默认的 serializeDate() 走 Carbon::toJSON()，输出 ISO-8601 UTC
 * （如 2026-05-17T13:13:32.000000Z）。该路径覆盖 created_at / updated_at 以及
 * 模型 $dates 中声明的字段，且不受两处已有设置影响：
 *
 *   - $casts 里的格式（'datetime:Y-m-d H:i'）只作用于 addCastAttributesToArray，
 *     而时间戳先被 addDateAttributesToArray 处理掉了；
 *   - AppServiceProvider 中的 Carbon::serializeUsing() 只影响 jsonSerialize()，
 *     不影响 toJSON()。
 *
 * 注意不要改用 $casts 覆盖 created_at ——时间戳会先被序列化成 UTC 字符串，
 * cast 再在该字符串上二次解析，结果比实际早 8 小时。
 */
trait SerializesDatesInAppTimezone
{
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
