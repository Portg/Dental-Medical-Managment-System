<?php

namespace Tests\Concerns;

use Throwable;

/**
 * 建库这一步失败时的重试。
 *
 * MySQL 8.4 上偶发：
 *
 *     SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying
 *     to get lock  (SQL: drop table `a`,`b`,… 共 60 张)
 *
 * migrate:fresh 一次性 drop 六十张表，InnoDB 取数据字典锁时偶尔撞上死锁。一旦这
 * 一步失败，库就停在半塌状态：后面每个测试类的 setUp 都在残缺的库上跑，报错散落
 * 各处、每次跑失败数还不一样（实测 39 / 19 / 6），看上去像业务代码坏了 ——
 * 排查成本远高于问题本身。
 *
 * 为什么是重试而不是消除成因：复现不出来。连跑四次全量都绿，开着 dev server 跑
 * 也绿（而且 serve 连的是开发库、测试用的是 test 库，两个库不共享表）。
 * 成因不明时，与其按猜测去改，不如让它可恢复 —— 死锁是瞬时的，重试就过去了。
 *
 * 抽成独立 trait 而不是写在 TestCase 里，是为了能直接测：PHPUnit 的 TestCase
 * 有不少 final 方法，继承它造替身反而绕。
 */
trait RetriesDatabaseSetup
{
    /**
     * 跑 $run，遇到死锁 / 锁等待超时就退避重试。
     *
     * 只对这两类错误重试。别的失败（迁移本身写错了）要当场炸出来 —— 重试只会把
     * 真正的错误拖到最后一次才报，而那时堆栈里全是重试的噪音。
     *
     * @param  callable  $run
     */
    protected function retryOnDatabaseLock(callable $run, int $attempts = 3, ?callable $sleeper = null): void
    {
        $sleeper = $sleeper ?: static fn (int $us) => usleep($us);

        for ($i = 1; ; $i++) {
            try {
                $run();
                return;
            } catch (Throwable $e) {
                if (!self::isLockFailure($e) || $i >= $attempts) {
                    throw $e;
                }

                $sleeper(300_000 * $i);   // 退避：0.3s、0.6s
            }
        }
    }

    public static function isLockFailure(Throwable $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, '1213 Deadlock')
            || str_contains($message, '1205 Lock wait timeout');
    }
}
