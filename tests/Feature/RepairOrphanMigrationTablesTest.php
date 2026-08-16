<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * upgrade:repair-orphan-tables 会删表，边界必须钉死。
 *
 * 它要解决的状态：上一次升级跑完迁移后回滚，而旧版本的备份是 mysqldump 不带
 * --databases 做的，导入只覆盖备份时已有的表 —— 迁移新建的表留了下来，
 * migrations 表却被恢复成没跑过。此后每次升级都死在 CREATE TABLE 上。
 *
 * 允许它动手的前提只有两条，缺一条就必须停：
 *   1. 表是**待执行迁移**声明要创建的（迁移没跑过 = 这版应用不该在用它）
 *   2. 表是**空的**（有数据就不是它能替人决定的事）
 */
class RepairOrphanMigrationTablesTest extends TestCase
{
    use RefreshDatabase;

    /** 本次实机事故里真正卡住升级的那张表 */
    private const ORPHAN = 'clinic_disinfection_records';
    private const ORPHAN_MIGRATION = '2026_08_11_000001_create_clinic_affairs_records';

    /**
     * 造出事故现场：表在，但 migrations 里没有这条记录。
     */
    private function makeOrphan(): void
    {
        DB::table('migrations')->where('migration', self::ORPHAN_MIGRATION)->delete();

        $this->assertTrue(Schema::hasTable(self::ORPHAN), '前提：迁移跑过，表是在的');
        $this->assertDatabaseMissing('migrations', ['migration' => self::ORPHAN_MIGRATION]);
    }

    /** @test */
    public function 空的孤立表会被删掉让迁移重新建(): void
    {
        $this->makeOrphan();

        $this->artisan('upgrade:repair-orphan-tables')
            ->expectsOutputToContain(self::ORPHAN)
            ->assertExitCode(0);

        $this->assertFalse(Schema::hasTable(self::ORPHAN), '空的孤立表应当被删掉');

        // 删掉之后迁移能重新跑完，这才是修复的目的
        $this->artisan('migrate', ['--force' => true])->assertExitCode(0);
        $this->assertTrue(Schema::hasTable(self::ORPHAN));
    }

    /**
     * 有数据就绝不删 —— 宁可升级失败，也不能替人决定一份数据能不能扔。
     */
    /** @test */
    public function 带数据的孤立表不会被删且升级会停下(): void
    {
        $this->makeOrphan();

        DB::table(self::ORPHAN)->insert([
            'area'         => '手术间',
            'check_type'   => 'housekeeping',
            'performed_at' => now(),
            'result'       => 'pass',
        ]);

        $this->artisan('upgrade:repair-orphan-tables')
            ->expectsOutputToContain('不删')
            ->assertExitCode(1);

        $this->assertTrue(Schema::hasTable(self::ORPHAN), '有数据的表不该被删');
        $this->assertSame(1, DB::table(self::ORPHAN)->count());
    }

    /**
     * 迁移记录还在时，表不是孤立的 —— 一行都不许动。
     * 这条防的是「把正常库里的业务表当孤立表删掉」这种最坏结果。
     */
    /** @test */
    public function 正常库里一张表都不动(): void
    {
        $this->artisan('upgrade:repair-orphan-tables')
            ->expectsOutputToContain('没有发现孤立表')
            ->assertExitCode(0);

        $this->assertTrue(Schema::hasTable(self::ORPHAN));
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('invoices'));
    }

    /**
     * 待执行迁移**没有**声明要建的表，即使库里有同名表也不碰。
     * 判据是「迁移里 Schema::create 的表名」，不是「库里多出来的表」。
     */
    /** @test */
    public function 不属于任何待执行迁移的表不碰(): void
    {
        Schema::create('some_manual_table', function (Blueprint $table) {
            $table->id();
        });

        $this->makeOrphan();

        $this->artisan('upgrade:repair-orphan-tables')->assertExitCode(0);

        $this->assertTrue(
            Schema::hasTable('some_manual_table'),
            '没有任何迁移声明要建它，就不该被当成孤立表删掉'
        );

        Schema::dropIfExists('some_manual_table');
    }

    /** @test */
    public function dry_run_只报告不删(): void
    {
        $this->makeOrphan();

        $this->artisan('upgrade:repair-orphan-tables', ['--dry-run' => true])
            ->expectsOutputToContain('将删除')
            ->assertExitCode(0);

        $this->assertTrue(Schema::hasTable(self::ORPHAN), '--dry-run 不该真删');
    }
}
