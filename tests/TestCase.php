<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Concerns\RetriesDatabaseSetup;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RetriesDatabaseSetup;

    /**
     * 建库失败时重试一次。
     *
     * 覆盖的是 RefreshDatabase trait 的同名方法（类方法优先于 trait），逻辑与原版
     * 一致，只是把 migrate:fresh 包进 retryOnDatabaseLock —— 原因见
     * Tests\Concerns\RetriesDatabaseSetup 的说明。
     *
     * 只重试建库，不重试测试本身：测试失败该红就红。
     */
    protected function refreshTestDatabase()
    {
        if (! RefreshDatabaseState::$migrated) {
            $this->retryOnDatabaseLock(function () {
                $this->artisan('migrate:fresh', $this->migrateFreshUsing());
            });

            $this->app[Kernel::class]->setArtisan(null);

            RefreshDatabaseState::$migrated = true;
        }

        $this->beginDatabaseTransaction();
    }
}
