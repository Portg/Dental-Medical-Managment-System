<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase as PlainTestCase;
use RuntimeException;
use Tests\Concerns\RetriesDatabaseSetup;

/**
 * 建库失败时的重试（Tests\Concerns\RetriesDatabaseSetup）。
 *
 * 不能只写个重试就指望它以后不出问题 —— 尤其是「哪些错该重试」这条边界：
 * 迁移本身写错了必须当场炸出来，重试只会把真正的错误拖到最后一次才报。
 *
 * 继承 PHPUnit 的裸 TestCase 而不是 Tests\TestCase：这里测的是纯逻辑，
 * 不需要起应用，也不该因为「测重试」而真的去建一次库。
 */
class FreshDatabaseRetryTest extends PlainTestCase
{
    use RetriesDatabaseSetup;

    private array $slept = [];

    private function sleeper(): callable
    {
        return function (int $us) { $this->slept[] = $us; };
    }

    private function deadlock(): RuntimeException
    {
        return new RuntimeException(
            'SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock'
        );
    }

    /** 按序抛出给定的异常（null = 这次成功），并记下被调了几次 */
    private function sequence(array $seq, ?int &$calls): callable
    {
        $calls = 0;
        return function () use (&$seq, &$calls) {
            $calls++;
            $e = array_shift($seq);
            if ($e) {
                throw $e;
            }
        };
    }

    public function test_一次就成功时不重试(): void
    {
        $this->retryOnDatabaseLock($this->sequence([null], $calls), 3, $this->sleeper());

        $this->assertSame(1, $calls);
        $this->assertSame([], $this->slept, '没失败就不该等待');
    }

    public function test_死锁后重试并成功(): void
    {
        $this->retryOnDatabaseLock($this->sequence([$this->deadlock(), null], $calls), 3, $this->sleeper());

        $this->assertSame(2, $calls, '死锁应当重试一次就过去');
        $this->assertSame([300_000], $this->slept, '重试前应当退避 0.3s');
    }

    public function test_锁等待超时同样重试(): void
    {
        $seq = [new RuntimeException('SQLSTATE[HY000]: 1205 Lock wait timeout exceeded'), null];

        $this->retryOnDatabaseLock($this->sequence($seq, $calls), 3, $this->sleeper());

        $this->assertSame(2, $calls);
    }

    /**
     * 迁移本身写错了要当场炸出来 —— 重试只会把真正的错误拖到最后一次才报，
     * 而那时堆栈里全是重试的噪音。
     */
    public function test_不是锁冲突的错误不重试(): void
    {
        $run = $this->sequence([new RuntimeException('SQLSTATE[42S01]: Base table already exists')], $calls);

        try {
            $this->retryOnDatabaseLock($run, 3, $this->sleeper());
            $this->fail('非锁冲突错误应当直接抛出');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('already exists', $e->getMessage());
        }

        $this->assertSame(1, $calls, '只该试一次');
    }

    /** 一直死锁就得放弃，不能无限重试把整轮测试拖死 */
    public function test_连续死锁到上限后抛出(): void
    {
        $run = $this->sequence([$this->deadlock(), $this->deadlock(), $this->deadlock()], $calls);

        try {
            $this->retryOnDatabaseLock($run, 3, $this->sleeper());
            $this->fail('到上限应当抛出');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('1213 Deadlock', $e->getMessage());
        }

        $this->assertSame(3, $calls);
        $this->assertSame([300_000, 600_000], $this->slept, '退避应当递增');
    }
}
