<?php

namespace Tests\Unit;

use App\Http\Resources\Concerns\FormatsDates;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * FormatsDates 是 20 个 API Resource 日期输出的唯一出口，
 * 这里锁住它的四条分支：Carbon 实例 / 字符串 / 空值 / 脏数据。
 */
class FormatsDatesTest extends TestCase
{
    private object $formatter;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.timezone' => 'Asia/Shanghai']);

        // trait 的方法是 protected，用一层公开壳子调用
        $this->formatter = new class {
            use FormatsDates;

            public function callDateTime($value): ?string
            {
                return $this->dateTime($value);
            }

            public function callDateOnly($value): ?string
            {
                return $this->dateOnly($value);
            }
        };
    }

    // ─── Carbon 实例 ────────────────────────────────────────────

    public function test_carbon_instance_is_formatted(): void
    {
        $carbon = Carbon::create(2026, 7, 31, 16, 0, 0, 'Asia/Shanghai');

        $this->assertSame('2026-07-31 16:00:00', $this->formatter->callDateTime($carbon));
        $this->assertSame('2026-07-31', $this->formatter->callDateOnly($carbon));
    }

    public function test_utc_carbon_instance_is_converted_to_app_timezone(): void
    {
        $carbon = Carbon::create(2026, 7, 31, 16, 0, 0, 'UTC');

        // 东八区应为次日 00:00
        $this->assertSame('2026-08-01 00:00:00', $this->formatter->callDateTime($carbon));
        $this->assertSame('2026-08-01', $this->formatter->callDateOnly($carbon));
    }

    public function test_passed_carbon_is_not_mutated(): void
    {
        // Carbon 继承可变的 DateTime，就地 setTimezone() 会污染模型缓存的属性
        $carbon = Carbon::create(2026, 7, 31, 16, 0, 0, 'UTC');

        $this->formatter->callDateTime($carbon);

        $this->assertSame('UTC', $carbon->timezone->getName());
        $this->assertSame('2026-07-31 16:00:00', $carbon->format('Y-m-d H:i:s'));
    }

    // ─── 字符串 ────────────────────────────────────────────────

    public function test_naive_string_is_treated_as_app_timezone(): void
    {
        // DB::table() 取回的就是这种不带时区的串，不应被平移
        $this->assertSame('2026-07-31 16:00:00', $this->formatter->callDateTime('2026-07-31 16:00:00'));
        $this->assertSame('2026-07-31', $this->formatter->callDateOnly('2026-07-31 16:00:00'));
    }

    public function test_utc_string_is_converted_to_app_timezone(): void
    {
        // 本次改造的核心：带 Z 的串不能原样输出 UTC 墙钟时间
        $this->assertSame('2026-08-01 00:00:00', $this->formatter->callDateTime('2026-07-31T16:00:00.000000Z'));
        $this->assertSame('2026-08-01', $this->formatter->callDateOnly('2026-07-31T16:00:00.000000Z'));
    }

    public function test_offset_string_is_converted_to_app_timezone(): void
    {
        $this->assertSame('2026-08-01 00:00:00', $this->formatter->callDateTime('2026-07-31T16:00:00+00:00'));
        $this->assertSame('2026-07-31 16:00:00', $this->formatter->callDateTime('2026-07-31T16:00:00+08:00'));
    }

    public function test_date_only_string_keeps_its_day(): void
    {
        $this->assertSame('1990-05-15', $this->formatter->callDateOnly('1990-05-15'));
        $this->assertSame('1990-05-15 00:00:00', $this->formatter->callDateTime('1990-05-15'));
    }

    // ─── 空值 ──────────────────────────────────────────────────

    public function test_null_and_empty_string_return_null(): void
    {
        $this->assertNull($this->formatter->callDateTime(null));
        $this->assertNull($this->formatter->callDateOnly(null));
        $this->assertNull($this->formatter->callDateTime(''));
        $this->assertNull($this->formatter->callDateOnly(''));
    }

    // ─── 脏数据 ────────────────────────────────────────────────

    public function test_unparsable_string_falls_back_to_raw_value_and_logs(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn ($message) => str_contains($message, 'FormatsDates'));

        $this->assertSame('not-a-date', $this->formatter->callDateTime('not-a-date'));
    }

    public function test_unparsable_non_scalar_returns_null_and_logs(): void
    {
        Log::shouldReceive('warning')->once();

        $this->assertNull($this->formatter->callDateTime(['unexpected' => 'array']));
    }
}
